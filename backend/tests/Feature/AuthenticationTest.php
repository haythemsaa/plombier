<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register_as_client()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'phone' => '+216 98 123 456',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'type' => 'client',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'phone',
                        'email',
                        'type',
                    ],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'phone' => '+216 98 123 456',
            'type' => 'client',
        ]);

        $this->assertDatabaseHas('clients', [
            'user_id' => User::where('phone', '+216 98 123 456')->first()->id,
        ]);
    }

    /** @test */
    public function user_can_login_with_phone_and_password()
    {
        $user = User::factory()->create([
            'phone' => '+216 98 123 456',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'phone' => '+216 98 123 456',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'token',
                ],
            ]);
    }

    /** @test */
    public function login_fails_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'phone' => '+216 98 123 456',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'phone' => '+216 98 123 456',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    /** @test */
    public function authenticated_user_can_access_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'phone',
                    'email',
                    'type',
                ],
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_profile()
    {
        $response = $this->getJson('/api/profile');

        $response->assertStatus(401);
    }

    /** @test */
    public function user_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
    }

    /** @test */
    public function registration_validates_required_fields()
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone', 'password', 'type']);
    }

    /** @test */
    public function registration_validates_unique_phone()
    {
        User::factory()->create(['phone' => '+216 98 123 456']);

        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'phone' => '+216 98 123 456',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'type' => 'client',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }
}
