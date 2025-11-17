<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Provider;
use App\Models\ProviderService;
use App\Models\Service;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $client;
    protected User $provider;
    protected ProviderService $providerService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create client
        $this->client = User::factory()->create(['type' => 'client']);
        Client::factory()->create(['user_id' => $this->client->id]);

        // Create provider
        $this->provider = User::factory()->create(['type' => 'provider']);
        $provider = Provider::factory()->create(['user_id' => $this->provider->id]);

        // Create service
        $service = Service::factory()->create();
        $this->providerService = ProviderService::factory()->create([
            'provider_id' => $provider->id,
            'service_id' => $service->id,
            'price' => 100.00,
        ]);
    }

    /** @test */
    public function client_can_create_booking()
    {
        $response = $this->actingAs($this->client, 'sanctum')
            ->postJson('/api/bookings', [
                'provider_service_id' => $this->providerService->id,
                'scheduled_at' => now()->addDays(2)->toDateTimeString(),
                'description' => 'Need plumbing repair',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'reference_number',
                    'status',
                    'base_price',
                    'total_price',
                ],
            ]);

        $this->assertDatabaseHas('bookings', [
            'client_id' => $this->client->client->id,
            'provider_id' => $this->providerService->provider_id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function client_can_view_their_bookings()
    {
        Booking::factory()->count(3)->create([
            'client_id' => $this->client->client->id,
        ]);

        $response = $this->actingAs($this->client, 'sanctum')
            ->getJson('/api/bookings');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function client_can_view_specific_booking()
    {
        $booking = Booking::factory()->create([
            'client_id' => $this->client->client->id,
        ]);

        $response = $this->actingAs($this->client, 'sanctum')
            ->getJson("/api/bookings/{$booking->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $booking->id,
                ],
            ]);
    }

    /** @test */
    public function client_cannot_view_other_client_booking()
    {
        $otherClient = User::factory()->create(['type' => 'client']);
        Client::factory()->create(['user_id' => $otherClient->id]);

        $booking = Booking::factory()->create([
            'client_id' => $otherClient->client->id,
        ]);

        $response = $this->actingAs($this->client, 'sanctum')
            ->getJson("/api/bookings/{$booking->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function client_can_cancel_pending_booking()
    {
        $booking = Booking::factory()->create([
            'client_id' => $this->client->client->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->client, 'sanctum')
            ->postJson("/api/bookings/{$booking->id}/cancel", [
                'cancellation_reason' => 'Changed my mind',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function provider_can_view_their_bookings()
    {
        $provider = Provider::factory()->create(['user_id' => $this->provider->id]);

        Booking::factory()->count(2)->create([
            'provider_id' => $provider->id,
        ]);

        $response = $this->actingAs($this->provider, 'sanctum')
            ->getJson('/api/bookings');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function booking_validates_scheduled_at_is_in_future()
    {
        $response = $this->actingAs($this->client, 'sanctum')
            ->postJson('/api/bookings', [
                'provider_service_id' => $this->providerService->id,
                'scheduled_at' => now()->subDay()->toDateTimeString(), // Past date
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at']);
    }

    /** @test */
    public function booking_calculates_urgent_surcharge()
    {
        $response = $this->actingAs($this->client, 'sanctum')
            ->postJson('/api/bookings', [
                'provider_service_id' => $this->providerService->id,
                'scheduled_at' => now()->addDays(1)->toDateTimeString(),
                'is_urgent' => true,
            ]);

        $response->assertStatus(201);

        $booking = Booking::latest()->first();
        $this->assertEquals(120.00, $booking->total_price); // 100 + 20%
    }
}
