<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApiSecurityTest extends TestCase
{
    public function test_unauthenticated_cannot_access_fdh_testtoken(): void
    {
        $response = $this->getJson('/api/fdh/testtoken');
        $response->assertStatus(401);
    }

    public function test_unauthenticated_cannot_access_nhso_pull_list(): void
    {
        $response = $this->getJson('/api/nhso/get-pull-list');
        $response->assertStatus(401);
    }

    public function test_unauthenticated_cannot_access_nhso_pull_chunk(): void
    {
        $response = $this->postJson('/api/nhso/pull-chunk', ['items' => []]);
        $response->assertStatus(401);
    }

    public function test_unauthenticated_external_cannot_access_amnosend(): void
    {
        $response = $this->call('POST', '/api/amnosend', [], [], [], ['REMOTE_ADDR' => '192.168.1.100']);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_localhost_can_access_amnosend(): void
    {
        $response = $this->call('POST', '/api/amnosend', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_unauthenticated_external_cannot_access_nhso_pull_yesterday(): void
    {
        $response = $this->call('POST', '/api/nhso_endpoint_pull_yesterday', [], [], [], ['REMOTE_ADDR' => '192.168.1.100']);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_authenticated_admin_can_access_fdh_testtoken(): void
    {
        $admin = User::where('status', 'admin')->first();
        if ($admin) {
            $response = $this->actingAs($admin)->getJson('/api/fdh/testtoken');
            $response->assertStatus(200);
            $response->assertJsonStructure(['token', 'status']);
        } else {
            $this->markTestSkipped('No admin user found');
        }
    }

    public function test_authenticated_admin_can_access_nhso_pull_list(): void
    {
        $admin = User::where('status', 'admin')->first();
        if ($admin) {
            $response = $this->actingAs($admin)->getJson('/api/nhso/get-pull-list');
            $response->assertStatus(200);
            $response->assertJsonStructure(['status', 'items']);
        } else {
            $this->markTestSkipped('No admin user found');
        }
    }

    public function test_registration_cannot_escalate_to_admin(): void
    {
        $testEmail = 'pentest_fake_admin_' . uniqid() . '@example.com';
        
        $response = $this->post('/register', [
            'name' => 'Fake Hacker Admin',
            'email' => $testEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'status' => 'admin',
            'active' => 'Y',
            'allow_home' => 'Y',
            'allow_debtor' => 'Y',
        ]);

        $user = User::where('email', $testEmail)->first();
        $this->assertNotNull($user);
        $this->assertEquals('user', $user->status, 'User status MUST remain user and never admin');
        $this->assertEquals('N', $user->active, 'User active state MUST remain N until manual admin approval');
        
        // Clean up
        $user->delete();
    }

    public function test_unauthenticated_cannot_access_debtor_adjust_log(): void
    {
        $response = $this->get('/debtor/adjust_log/1102050101_103?export_type=json');
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_external_cannot_access_notify_summary(): void
    {
        $response = $this->call('GET', '/notify_summary', [], [], [], ['REMOTE_ADDR' => '192.168.1.100']);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_localhost_can_access_notify_summary(): void
    {
        $response = $this->call('GET', '/notify_summary', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        // Returns 200 or 500 depending on hosxp connection, but definitely NOT 401 Unauthorized
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_external_with_valid_key_can_access_notify_summary(): void
    {
        $secretKey = config('app.schedule_secret_key');
        if (!$secretKey) {
            $secretKey = DB::table('main_setting')->where('name', 'schedule_secret_key')->value('value');
        }
        if (!$secretKey) {
            $hcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: 'hrims';
            $secretKey = substr(hash('sha256', $hcode . config('app.key', 'hrims_salt')), 0, 32);
        }

        $response = $this->call('GET', '/notify_summary?key=' . $secretKey, [], [], [], ['REMOTE_ADDR' => '192.168.1.100']);
        // With valid key, external IP is authorized (returns 200 or 500, NOT 401)
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_unauthenticated_cannot_access_clear_cache(): void
    {
        $response = $this->get('/admin/clear-cache');
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_cannot_access_f16_export(): void
    {
        $response = $this->postJson('/f16_eclaim_export/preview', []);
        $response->assertStatus(401);
    }
}
