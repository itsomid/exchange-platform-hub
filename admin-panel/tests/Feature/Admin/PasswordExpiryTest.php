<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\CheckUserActivation;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordExpiryTest extends TestCase
{
    use RefreshDatabase;

    private const CURRENT = 'Current-pass-123!';

    private const STRONG = 'Zx9#kLm2$Qw8vB';

    private function adminWith(string $state = ''): Admin
    {
        $factory = Admin::factory();
        if ($state) {
            $factory = $factory->{$state}();
        }

        $admin = $factory->create(['password' => self::CURRENT]);
        $admin->setAttribute('avatar', null);

        return $admin;
    }

    private function as(Admin $admin): static
    {
        $this->withoutMiddleware([CheckUserActivation::class]);

        return $this->actingAs($admin, 'admin')->withSession(['auth.password_confirmed_at' => time()]);
    }

    public function test_fresh_password_shows_no_banner_and_no_restriction(): void
    {
        $admin = $this->adminWith();

        $this->as($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('password-banner', false);

        $this->assertNull($admin->fresh()->password_change_deadline_at);
    }

    public function test_never_changed_password_starts_a_four_day_countdown_without_restriction(): void
    {
        $admin = $this->adminWith('withoutPasswordChange');

        $this->as($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('password-banner', false)
            ->assertSee(route('admin.profile.password.edit'), false);

        $deadline = $admin->fresh()->password_change_deadline_at;
        $this->assertNotNull($deadline);
        $this->assertEqualsWithDelta(4 * 86400, now()->diffInSeconds($deadline, false), 10);
    }

    public function test_countdown_is_not_restarted_on_next_requests(): void
    {
        $admin = $this->adminWith('withoutPasswordChange');

        $this->as($admin)->get(route('admin.dashboard'))->assertOk();
        $first = $admin->fresh()->password_change_deadline_at;

        $this->travel(2)->days();
        $this->as($admin->fresh())->get(route('admin.dashboard'))->assertOk();

        $this->assertTrue($first->equalTo($admin->fresh()->password_change_deadline_at));
    }

    public function test_password_older_than_six_months_starts_countdown(): void
    {
        $admin = $this->adminWith('withExpiredPassword');

        $this->as($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('password-banner', false);

        $this->assertNotNull($admin->fresh()->password_change_deadline_at);
    }

    public function test_password_just_under_six_months_is_still_fine(): void
    {
        $admin = $this->adminWith();
        $admin->forceFill(['password_changed_at' => now()->subMonths(6)->addDay()])->save();

        $this->assertFalse($admin->fresh()->passwordChangeIsDue());
    }

    public function test_after_the_grace_period_everything_except_profile_is_locked(): void
    {
        $admin = $this->adminWith('withoutPasswordChange');
        $admin->forceFill(['password_change_deadline_at' => now()->subMinute()])->save();

        $this->as($admin)->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.profile.password.edit'));

        $this->as($admin)->getJson(route('admin.dashboard'))
            ->assertStatus(403)
            ->assertJsonPath('redirect', route('admin.profile.password.edit'));

        $this->as($admin)->get(route('admin.profile.password.edit'))
            ->assertOk()
            ->assertSee('password-banner', false)
            ->assertSee('security-banner--danger', false);

        $this->as($admin)->get(route('admin.profile.edit'))->assertOk();
    }

    public function test_countdown_banner_is_urgent_in_the_last_day(): void
    {
        $admin = $this->adminWith('withoutPasswordChange');
        $admin->forceFill(['password_change_deadline_at' => now()->addHours(5)])->save();

        $this->as($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('security-banner--urgent', false);
    }

    public function test_changing_password_records_date_and_lifts_the_lock(): void
    {
        $admin = $this->adminWith('withoutPasswordChange');
        $admin->forceFill(['password_change_deadline_at' => now()->subMinute()])->save();

        $this->as($admin)->patch(route('admin.profile.password.update'), [
            'current_password' => self::CURRENT,
            'password' => self::STRONG,
            'password_confirmation' => self::STRONG,
        ])->assertSessionHasNoErrors();

        $admin = $admin->fresh();
        $this->assertTrue(Hash::check(self::STRONG, $admin->password));
        $this->assertNotNull($admin->password_changed_at);
        $this->assertNull($admin->password_change_deadline_at);

        $this->as($admin)->get(route('admin.dashboard'))->assertOk()->assertDontSee('password-banner', false);
    }

    public function test_current_password_is_required_and_must_match(): void
    {
        $admin = $this->adminWith();

        $this->as($admin)->patch(route('admin.profile.password.update'), [
            'password' => self::STRONG,
            'password_confirmation' => self::STRONG,
        ])->assertSessionHasErrors('current_password');

        $this->as($admin)->patch(route('admin.profile.password.update'), [
            'current_password' => 'wrong-password',
            'password' => self::STRONG,
            'password_confirmation' => self::STRONG,
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check(self::CURRENT, $admin->fresh()->password));
    }

    /**
     * @dataProvider weakPasswords
     */
    public function test_weak_passwords_are_rejected(string $weak): void
    {
        $admin = $this->adminWith();

        $this->as($admin)->patch(route('admin.profile.password.update'), [
            'current_password' => self::CURRENT,
            'password' => $weak,
            'password_confirmation' => $weak,
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check(self::CURRENT, $admin->fresh()->password));
    }

    public static function weakPasswords(): array
    {
        return [
            'too short' => ['Ab1!xyZ'],
            '11 chars' => ['Zx9#kLm2$Qw'],
            'no uppercase' => ['zx9#klm2$qw8vb'],
            'no lowercase' => ['ZX9#KLM2$QW8VB'],
            'no digit' => ['Zx#kLmZ$QwvBtt'],
            'no special' => ['Zx9kLm2Qw8vBtt'],
            'common pattern' => ['Password-Xy#9876'],
            'sequence' => ['Zx#kLm$Qw8vB1234'],
            'repeated chars' => ['Zx#kLm$QwvBt8888'],
        ];
    }

    public function test_new_password_must_differ_from_current_and_not_contain_personal_info(): void
    {
        $admin = $this->adminWith();

        $this->as($admin)->patch(route('admin.profile.password.update'), [
            'current_password' => self::CURRENT,
            'password' => self::CURRENT,
            'password_confirmation' => self::CURRENT,
        ])->assertSessionHasErrors('password');

        $withMobile = 'Zx#'.$admin->mobile.'Qw!b';
        $this->as($admin)->patch(route('admin.profile.password.update'), [
            'current_password' => self::CURRENT,
            'password' => $withMobile,
            'password_confirmation' => $withMobile,
        ])->assertSessionHasErrors('password');
    }

    public function test_confirmation_must_match(): void
    {
        $admin = $this->adminWith();

        $this->as($admin)->patch(route('admin.profile.password.update'), [
            'current_password' => self::CURRENT,
            'password' => self::STRONG,
            'password_confirmation' => self::STRONG.'x',
        ])->assertSessionHasErrors('password');
    }

    public function test_password_page_renders_current_password_field_and_live_checklist(): void
    {
        $this->as($this->adminWith())->get(route('admin.profile.password.edit'))
            ->assertOk()
            ->assertSee('name="current_password"', false)
            ->assertSee('passwordChecklist', false)
            ->assertSee('id="passwordSummary"', false)
            ->assertSee('"key":"length"', false);
    }
}
