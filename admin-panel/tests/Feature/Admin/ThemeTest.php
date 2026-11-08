<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\CheckUserActivation;
use App\Models\Admin;
use App\Support\AdminTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    private function as(Admin $admin): static
    {
        $this->withoutMiddleware([CheckUserActivation::class]);

        return $this->actingAs($admin, 'admin')->withSession(['auth.password_confirmed_at' => time()]);
    }

    public function test_system_theme_is_stored_and_follows_the_os_preference(): void
    {
        $admin = Admin::factory()->create();
        $admin->setAttribute('avatar', null);

        $this->as($admin)
            ->post(route('admin.set-theme'), ['theme' => AdminTheme::SYSTEM])
            ->assertRedirect();

        $this->assertSame(AdminTheme::SYSTEM, session('theme'));

        $this->as($admin)
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('data-theme-preference="system"', false)
            ->assertSee('prefers-color-scheme: light', false)
            ->assertSee('prefers-color-scheme: dark', false)
            ->assertSee('value="system"', false)
            ->assertSee('fa-desktop', false);
    }

    public function test_dark_theme_still_loads_the_dark_stylesheet(): void
    {
        $admin = Admin::factory()->create();
        $admin->setAttribute('avatar', null);

        $this->as($admin)
            ->post(route('admin.set-theme'), ['theme' => AdminTheme::DARK])
            ->assertRedirect();

        $this->as($admin)
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('data-theme-preference="dark"', false)
            ->assertSee('class="dark-style ', false)
            ->assertSee('app-dark', false)
            ->assertDontSee('media="(prefers-color-scheme:', false);
    }

    public function test_unknown_theme_falls_back_to_light(): void
    {
        $admin = Admin::factory()->create();

        $this->as($admin)
            ->post(route('admin.set-theme'), ['theme' => 'nope'])
            ->assertRedirect();

        $this->assertSame(AdminTheme::LIGHT, session('theme'));
    }
}
