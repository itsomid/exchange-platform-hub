<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\CheckUserActivation;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    private function as(Admin $admin): static
    {
        $this->withoutMiddleware([CheckUserActivation::class]);

        return $this->actingAs($admin, 'admin')->withSession(['auth.password_confirmed_at' => time()]);
    }

    public function test_search_lists_open_pages_and_hides_restricted_ones(): void
    {
        $admin = Admin::factory()->create();
        $admin->setAttribute('avatar', null);

        $items = $this->catalog($this->as($admin)->get(route('admin.profile.edit')));
        $ids = array_column($items, 'id');

        $this->assertContains('dashboard', $ids);
        $this->assertContains('financial-dashboard', $ids);
        $this->assertContains('profile', $ids);
        $this->assertContains('profile-password', $ids);
        $this->assertNotContains('users', $ids);
        $this->assertNotContains('otc-orders', $ids);
        $this->assertNotContains('deposits', $ids);
        $this->assertNotContains('api-systems', $ids);
        $this->assertNotContains('currencies', $ids);
    }

    public function test_a_permission_reveals_only_the_pages_it_opens(): void
    {
        $admin = Admin::factory()->create();
        $admin->setAttribute('avatar', null);
        Permission::findOrCreate('user.index', 'admin');
        Permission::findOrCreate('market', 'admin');
        $admin->givePermissionTo(['user.index', 'market']);

        $items = $this->catalog($this->as($admin)->get(route('admin.profile.edit')));
        $ids = array_column($items, 'id');
        $users = collect($items)->firstWhere('id', 'users');

        $this->assertContains('users', $ids);
        $this->assertContains('blocked-users', $ids);
        $this->assertContains('inquiry', $ids);
        $this->assertContains('markets', $ids);
        $this->assertContains('ref-binance', $ids);
        $this->assertNotContains('currencies', $ids);
        $this->assertNotContains('otc-orders', $ids);
        $this->assertNotContains('deposits', $ids);
        $this->assertSame(route('admin.user.index'), $users['url']);
    }

    public function test_a_locked_panel_only_offers_account_pages(): void
    {
        $admin = Admin::factory()->withExpiredPassword()->create();
        $admin->forceFill(['password_change_deadline_at' => now()->subMinute()])->save();
        $admin->setAttribute('avatar', null);
        Permission::findOrCreate('user.index', 'admin');
        $admin->givePermissionTo('user.index');

        $items = $this->catalog($this->as($admin)->get(route('admin.profile.edit')));
        $ids = array_column($items, 'id');

        $this->assertSame(['profile', 'profile-password', 'profile-2fa'], $ids);
    }

    private function catalog($response): array
    {
        $response->assertOk();
        $response->assertSee('id="admin-search-open"', false);
        $response->assertSee('جستجو در پنل', false);
        $response->assertSee('id="admin-search"', false);

        $content = $response->getContent();
        $start = strpos($content, 'id="admin-search-catalog">');
        $this->assertNotFalse($start);
        $start += strlen('id="admin-search-catalog">');
        $end = strpos($content, '</script>', $start);

        return json_decode(substr($content, $start, $end - $start), true, 512, JSON_THROW_ON_ERROR);
    }
}
