<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\CheckUserActivation;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Laravel\Fortify\Fortify;
use Tests\TestCase;

class TwoFactorProfileTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([CheckUserActivation::class]);

        $this->admin = Admin::factory()->withoutTwoFactor()->create(['password' => 'secret-pass-123']);
        $this->admin->setAttribute('avatar', null);
    }

    private function asAdmin(bool $passwordConfirmed = true): static
    {
        $session = ['auth.password_confirmed_at' => $passwordConfirmed ? time() : 0];

        return $this->actingAs($this->admin, 'admin')->withSession($session);
    }

    /** The 2FA lock only applies in production (CSRF is skipped only in 'testing', so switch after any POST). */
    private function inProduction(): void
    {
        $this->app['env'] = 'production';
    }

    private function validCode(): string
    {
        $secret = Fortify::currentEncrypter()->decrypt($this->admin->fresh()->two_factor_secret);

        return app(Google2FA::class)->getCurrentOtp($secret);
    }

    public function test_disabled_state_shows_start_button_and_no_qr(): void
    {
        $this->asAdmin()->get(route('admin.profile.2fa.edit'))
            ->assertOk()
            ->assertSee('btnStart', false)
            ->assertDontSee('aria-label="QR code"', false);
    }

    public function test_enabling_does_not_activate_until_code_is_confirmed(): void
    {
        $this->asAdmin()->postJson(route('two-factor.enable'))->assertOk();

        $admin = $this->admin->fresh();
        $this->assertNotNull($admin->two_factor_secret);
        $this->assertNull($admin->two_factor_confirmed_at);
        $this->assertFalse($admin->twoFAStatus());
        $this->assertTrue($admin->twoFAPending());

        // pending: QR + manual key are rendered
        $this->asAdmin()->get(route('admin.profile.2fa.edit'))
            ->assertOk()
            ->assertSee('aria-label="QR code"', false)
            ->assertSee('btnCopyKey', false);
    }

    public function test_pending_state_hides_qr_when_password_not_recently_confirmed(): void
    {
        $this->asAdmin()->postJson(route('two-factor.enable'))->assertOk();

        $this->asAdmin(false)->get(route('admin.profile.2fa.edit'))
            ->assertOk()
            ->assertDontSee('aria-label="QR code"', false)
            ->assertSee('btnReveal', false);
    }

    public function test_wrong_code_is_rejected_and_correct_code_activates(): void
    {
        $this->asAdmin()->postJson(route('two-factor.enable'))->assertOk();

        $this->asAdmin()->postJson(route('two-factor.confirm'), ['code' => '000000'])->assertStatus(422);
        $this->assertFalse($this->admin->fresh()->twoFAStatus());

        $this->asAdmin()->postJson(route('two-factor.confirm'), ['code' => $this->validCode()])->assertOk();
        $this->assertTrue($this->admin->fresh()->twoFAStatus());
    }

    public function test_enabled_state_never_renders_qr_or_secret(): void
    {
        $this->asAdmin()->postJson(route('two-factor.enable'))->assertOk();
        $this->asAdmin()->postJson(route('two-factor.confirm'), ['code' => $this->validCode()])->assertOk();

        $secret = Fortify::currentEncrypter()->decrypt($this->admin->fresh()->two_factor_secret);

        $this->asAdmin()->get(route('admin.profile.2fa.edit'))
            ->assertOk()
            ->assertSee('recoveryCard', false)
            ->assertDontSee('aria-label="QR code"', false)
            ->assertDontSee($secret, false);
    }

    public function test_password_confirmation_does_not_regenerate_the_secret(): void
    {
        $this->asAdmin()->postJson(route('two-factor.enable'))->assertOk();
        $before = $this->admin->fresh()->two_factor_secret;

        $this->asAdmin(false)->postJson(route('password.confirm.store'), ['password' => 'secret-pass-123'])->assertStatus(201);

        $this->assertSame($before, $this->admin->fresh()->two_factor_secret);
    }

    public function test_sensitive_endpoints_require_password_confirmation(): void
    {
        $this->asAdmin()->postJson(route('two-factor.enable'))->assertOk();

        $this->asAdmin(false)->getJson(route('two-factor.recovery-codes'))->assertStatus(423);
        $this->asAdmin(false)->getJson(route('two-factor.secret-key'))->assertStatus(423);
        $this->asAdmin(false)->deleteJson(route('two-factor.disable'))->assertStatus(423);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->asAdmin(false)->postJson(route('password.confirm.store'), ['password' => 'nope'])->assertStatus(422);
    }

    public function test_recovery_codes_available_and_disable_resets_everything(): void
    {
        $this->asAdmin()->postJson(route('two-factor.enable'))->assertOk();
        $this->asAdmin()->postJson(route('two-factor.confirm'), ['code' => $this->validCode()])->assertOk();

        $this->asAdmin()->getJson(route('two-factor.recovery-codes'))->assertOk()->assertJsonCount(8);

        $this->asAdmin()->deleteJson(route('two-factor.disable'))->assertOk();

        $admin = $this->admin->fresh();
        $this->assertNull($admin->two_factor_secret);
        $this->assertNull($admin->two_factor_recovery_codes);
        $this->assertNull($admin->two_factor_confirmed_at);
        $this->assertFalse($admin->twoFAStatus());
    }

    public function test_admin_without_active_2fa_can_only_reach_profile_pages(): void
    {
        $this->inProduction();

        foreach (['admin.dashboard', 'admin.financial-dashboard', 'admin.admin.index', 'admin.user.index', 'admin.users.select.index'] as $name) {
            $this->asAdmin()->get(route($name))->assertRedirect(route('admin.profile.2fa.edit'));
        }

        foreach (['admin.profile.edit', 'admin.profile.password.edit', 'admin.profile.2fa.edit'] as $name) {
            $this->asAdmin()->get(route($name))->assertOk();
        }
    }

    public function test_2fa_lock_is_not_applied_outside_production(): void
    {
        $this->app['env'] = 'local';

        $this->asAdmin()->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('twofa-banner', false);
        $this->asAdmin()->get(route('admin.admin.index'))->assertStatus(403); // reaches the permission check
    }

    public function test_pending_2fa_is_still_blocked(): void
    {
        $this->asAdmin()->postJson(route('two-factor.enable'))->assertOk();

        $this->inProduction();
        $this->asAdmin()->get(route('admin.dashboard'))->assertRedirect(route('admin.profile.2fa.edit'));
    }

    public function test_blocked_ajax_request_gets_403_with_redirect_url(): void
    {
        $this->inProduction();

        $this->asAdmin()->getJson(route('admin.dashboard.ajax.kpi-stats'))
            ->assertForbidden()
            ->assertJson(['redirect' => route('admin.profile.2fa.edit')]);
    }

    public function test_banner_is_shown_without_2fa_and_menu_is_limited_to_profile(): void
    {
        $this->inProduction();

        $this->asAdmin()->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('twofa-banner', false)
            ->assertSee(route('admin.profile.2fa.edit'), false)
            ->assertDontSee(route('admin.financial-dashboard'), false);
    }

    public function test_admin_with_active_2fa_has_full_access_and_no_banner(): void
    {
        $this->asAdmin()->postJson(route('two-factor.enable'))->assertOk();
        $this->asAdmin()->postJson(route('two-factor.confirm'), ['code' => $this->validCode()])->assertOk();

        $this->inProduction();
        $this->asAdmin()->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('twofa-banner', false)
            ->assertSee(route('admin.financial-dashboard'), false);
    }

    public function test_impersonation_return_route_is_not_blocked(): void
    {
        $this->inProduction();

        // 404 (no impersonation session) proves the 2FA middleware did not redirect first.
        $this->asAdmin()->get(route('admin.admin.back_to_admin_panel'))->assertNotFound();
    }
}
