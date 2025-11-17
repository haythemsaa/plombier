<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BookingService;
use App\Models\User;
use App\Models\ProviderService;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = app(BookingService::class);
    }

    /** @test */
    public function it_calculates_base_price_correctly()
    {
        $providerService = ProviderService::factory()->create(['price' => 50.00]);

        $data = [
            'provider_service_id' => $providerService->id,
            'scheduled_at' => now()->addDays(1)->setHour(10)->toDateTimeString(),
        ];

        $result = $this->invokePrivateMethod($this->bookingService, 'calculatePricing', [$data]);

        $this->assertEquals(50.00, $result['base_price']);
    }

    /** @test */
    public function it_applies_urgent_surcharge_correctly()
    {
        $providerService = ProviderService::factory()->create(['price' => 100.00]);

        $data = [
            'provider_service_id' => $providerService->id,
            'scheduled_at' => now()->addDays(1)->setHour(10)->toDateTimeString(),
            'is_urgent' => true,
        ];

        $result = $this->invokePrivateMethod($this->bookingService, 'calculatePricing', [$data]);

        $this->assertEquals(120.00, $result['total_price']); // 100 + 20% urgent
    }

    /** @test */
    public function it_applies_night_surcharge_correctly()
    {
        $providerService = ProviderService::factory()->create(['price' => 100.00]);

        $data = [
            'provider_service_id' => $providerService->id,
            'scheduled_at' => now()->addDays(1)->setHour(23)->toDateTimeString(), // 11 PM
        ];

        $result = $this->invokePrivateMethod($this->bookingService, 'calculatePricing', [$data]);

        $this->assertEquals(130.00, $result['total_price']); // 100 + 30% night
    }

    /** @test */
    public function it_applies_weekend_surcharge_correctly()
    {
        $providerService = ProviderService::factory()->create(['price' => 100.00]);

        $saturday = now()->next('Saturday')->setHour(10);

        $data = [
            'provider_service_id' => $providerService->id,
            'scheduled_at' => $saturday->toDateTimeString(),
        ];

        $result = $this->invokePrivateMethod($this->bookingService, 'calculatePricing', [$data]);

        $this->assertEquals(115.00, $result['total_price']); // 100 + 15% weekend
    }

    /** @test */
    public function it_applies_multiple_surcharges_cumulatively()
    {
        $providerService = ProviderService::factory()->create(['price' => 100.00]);

        $saturday = now()->next('Saturday')->setHour(23); // Saturday night

        $data = [
            'provider_service_id' => $providerService->id,
            'scheduled_at' => $saturday->toDateTimeString(),
            'is_urgent' => true,
        ];

        $result = $this->invokePrivateMethod($this->bookingService, 'calculatePricing', [$data]);

        // 100 + 20% (urgent) + 30% (night) + 15% (weekend) = 165.00
        $this->assertEquals(165.00, $result['total_price']);
    }

    /**
     * Helper method to invoke private methods for testing
     */
    protected function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
