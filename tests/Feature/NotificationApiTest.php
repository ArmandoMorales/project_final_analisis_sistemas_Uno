<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_own_and_broadcast_notifications(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $otherUser = User::factory()->create(['tenant_id' => $tenant->id]);

        // Visible: broadcast for this tenant.
        Notification::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => null,
            'title' => 'Aviso general',
        ]);

        // Visible: personal notification for this user.
        Notification::factory()->forUser($user)->create([
            'title' => 'Aviso personal',
        ]);

        // Not visible: personal notification for another user in the same tenant.
        Notification::factory()->forUser($otherUser)->create([
            'title' => 'No debe verse',
        ]);

        // Not visible: broadcast for another tenant.
        Notification::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => null,
            'title' => 'Otro tenant',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Tenant-ID' => $tenant->id,
        ])->getJson('/api/v1/notifications');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertContains('Aviso general', $titles);
        $this->assertContains('Aviso personal', $titles);
        $this->assertNotContains('No debe verse', $titles);
        $this->assertNotContains('Otro tenant', $titles);
    }

    public function test_unread_filter_only_returns_unread_notifications(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        Notification::factory()->forUser($user)->create(['title' => 'Leída', 'read_at' => now()]);
        Notification::factory()->forUser($user)->create(['title' => 'No leída']);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Tenant-ID' => $tenant->id,
        ])->getJson('/api/v1/notifications?status=unread');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertSame('No leída', $response->json('data.0.title'));
    }

    public function test_listing_notifications_requires_tenant_header(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/notifications');

        $response->assertStatus(400);
    }

    public function test_unread_count_reflects_only_visible_unread_notifications(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        Notification::factory()->create(['tenant_id' => $tenant->id, 'user_id' => null]);
        Notification::factory()->forUser($user)->create(['read_at' => now()]);
        Notification::factory()->forUser($user)->create();

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Tenant-ID' => $tenant->id,
        ])->getJson('/api/v1/notifications/unread-count');

        $response->assertOk();
        $response->assertJson(['unread_count' => 2]);
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $notification = Notification::factory()->forUser($user)->create();

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Tenant-ID' => $tenant->id,
        ])->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk();
        $this->assertNotNull($response->json('data.read_at'));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $otherUser = User::factory()->create(['tenant_id' => $tenant->id]);

        $notification = Notification::factory()->forUser($otherUser)->create();

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Tenant-ID' => $tenant->id,
        ])->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(403);
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_visible_notifications_as_read(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        Notification::factory()->create(['tenant_id' => $tenant->id, 'user_id' => null]);
        Notification::factory()->forUser($user)->create();

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Tenant-ID' => $tenant->id,
        ])->patchJson('/api/v1/notifications/read-all');

        $response->assertOk();

        $unreadResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Tenant-ID' => $tenant->id,
        ])->getJson('/api/v1/notifications/unread-count');

        $unreadResponse->assertJson(['unread_count' => 0]);
    }
}
