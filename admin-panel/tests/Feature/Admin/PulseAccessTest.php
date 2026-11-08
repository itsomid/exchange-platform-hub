<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PulseAccessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->setAttribute('avatar', null);

        return $admin;
    }

    public function test_guest_cannot_open_pulse(): void
    {
        $this->get('/pulse')->assertStatus(401);
    }

    public function test_admin_without_permission_cannot_open_pulse(): void
    {
        $this->actingAs($this->admin(), 'admin')->get('/pulse')->assertStatus(401);
    }

    public function test_admin_with_view_pulse_permission_can_open_pulse(): void
    {
        $admin = $this->admin();
        Permission::findOrCreate('viewPulse', 'admin');
        $admin->givePermissionTo('viewPulse');

        $this->actingAs($admin, 'admin')->get('/pulse')->assertOk();
    }

    public function test_pulse_does_not_record_admin_panel_activity(): void
    {
        $this->assertFalse(config('pulse.enabled'));
    }
}
