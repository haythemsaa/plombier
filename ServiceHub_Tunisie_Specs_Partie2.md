# CAHIER DES SPÉCIFICATIONS FONCTIONNELLES DÉTAILLÉES
## SUPER APP POUR SERVICES DU QUOTIDIEN - MARCHÉ TUNISIEN
## PARTIE 2 : SPÉCIFICATIONS TECHNIQUES

---

## 10. SPÉCIFICATIONS TECHNIQUES

### 10.1 Architecture Backend

#### 10.1.1 Stack Technologique

**Frontend Mobile** :
- Framework : **Flutter** (iOS + Android natif)
- Raisons :
  - Code unique pour iOS et Android
  - Performance native
  - UI/UX fluide
  - Support RTL pour arabe
  - Rich ecosystem

**Backend** :
- Framework : **Laravel 11** (PHP 8.2+)
- Raisons :
  - Écosystème mature
  - Excellent ORM (Eloquent)
  - Queue management intégré
  - Sécurité robuste
  - Communauté tunisienne forte

**Base de Données** :
- Principale : **PostgreSQL 15+**
- Cache : **Redis**
- Recherche : **Elasticsearch** (pour recherche avancée)

**Infrastructure** :
- Cloud : **AWS** (ou Google Cloud Platform)
- CDN : **CloudFront**
- Storage : **S3**
- Notifications : **Firebase Cloud Messaging**
- SMS : **Twilio** ou **Karix**

**Paiement** :
- Gateway principal : **PayTech** (Tunisie)
- Backup : **Clictopay**
- Internationaux : **Stripe** (pour touristes/expats)

#### 10.1.2 Architecture Microservices

```
┌─────────────────────────────────────────────────────┐
│              Load Balancer (AWS ALB)                │
└──────────────────┬──────────────────────────────────┘
                   │
      ┌────────────┼────────────┐
      │            │            │
┌─────▼─────┐ ┌───▼────┐ ┌────▼─────┐
│  Service  │ │Service │ │  Service │
│   User    │ │Booking │ │ Payment  │
│Management │ │        │ │          │
└───────────┘ └────────┘ └──────────┘
      │            │            │
      └────────────┼────────────┘
                   │
           ┌───────▼────────┐
           │   PostgreSQL   │
           │    Cluster     │
           └────────────────┘
```

**Services principaux** :
1. **User Service** : Gestion utilisateurs, authentification
2. **Booking Service** : Réservations, matching
3. **Payment Service** : Paiements, facturation
4. **Notification Service** : Push, SMS, Email
5. **Search Service** : Recherche et filtrage
6. **Review Service** : Avis et notations
7. **Analytics Service** : Tracking et reporting

### 10.2 Base de Données

#### 10.2.1 Schéma Principal

```sql
-- USERS (Clients et Prestataires)
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    type VARCHAR(20) NOT NULL CHECK (type IN ('client', 'provider', 'admin')),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar_url VARCHAR(500),
    language VARCHAR(2) DEFAULT 'fr' CHECK (language IN ('ar', 'fr')),
    email_verified_at TIMESTAMP,
    phone_verified_at TIMESTAMP,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'suspended', 'pending')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE INDEX idx_users_phone ON users(phone);
CREATE INDEX idx_users_type ON users(type);
CREATE INDEX idx_users_status ON users(status);

-- CLIENTS (Profil détaillé clients)
CREATE TABLE clients (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    subscription_type VARCHAR(20) DEFAULT 'free' CHECK (subscription_type IN ('free', 'plus')),
    subscription_expires_at TIMESTAMP,
    loyalty_points INTEGER DEFAULT 0,
    preferred_payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ADDRESSES (Adresses clients)
CREATE TABLE addresses (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    label VARCHAR(50) NOT NULL,
    street VARCHAR(255) NOT NULL,
    building VARCHAR(100),
    floor VARCHAR(50),
    governorate VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(10),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    is_default BOOLEAN DEFAULT FALSE,
    instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_addresses_user ON addresses(user_id);
CREATE INDEX idx_addresses_geo ON addresses(latitude, longitude);

-- PROVIDERS (Profil prestataires)
CREATE TABLE providers (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    business_name VARCHAR(255),
    cin VARCHAR(20) NOT NULL UNIQUE,
    business_license VARCHAR(100),
    subscription_type VARCHAR(20) DEFAULT 'starter' CHECK (subscription_type IN ('starter', 'pro', 'premium')),
    subscription_expires_at TIMESTAMP,
    rating_average DECIMAL(3, 2) DEFAULT 0.00,
    rating_count INTEGER DEFAULT 0,
    completion_rate DECIMAL(5, 2) DEFAULT 100.00,
    response_time_avg INTEGER DEFAULT 0,
    total_earnings DECIMAL(12, 2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'active', 'suspended')),
    verified_at TIMESTAMP,
    bio TEXT,
    years_experience INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_providers_rating ON providers(rating_average DESC, rating_count DESC);
CREATE INDEX idx_providers_status ON providers(status);

-- PROVIDER_DOCUMENTS
CREATE TABLE provider_documents (
    id SERIAL PRIMARY KEY,
    provider_id INTEGER NOT NULL REFERENCES providers(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL CHECK (type IN ('cin', 'license', 'certificate', 'insurance')),
    file_url VARCHAR(500) NOT NULL,
    verified_at TIMESTAMP,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- SERVICE_CATEGORIES
CREATE TABLE service_categories (
    id SERIAL PRIMARY KEY,
    parent_id INTEGER REFERENCES service_categories(id),
    name_ar VARCHAR(255) NOT NULL,
    name_fr VARCHAR(255) NOT NULL,
    icon VARCHAR(255),
    color VARCHAR(7),
    "order" INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- SERVICES (Catalogue services)
CREATE TABLE services (
    id SERIAL PRIMARY KEY,
    category_id INTEGER NOT NULL REFERENCES service_categories(id),
    name_ar VARCHAR(255) NOT NULL,
    name_fr VARCHAR(255) NOT NULL,
    description_ar TEXT,
    description_fr TEXT,
    icon VARCHAR(255),
    unit VARCHAR(20) CHECK (unit IN ('hour', 'task', 'sqm')),
    base_price DECIMAL(10, 2),
    commission_rate DECIMAL(5, 2) DEFAULT 18.00,
    is_active BOOLEAN DEFAULT TRUE,
    "order" INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_services_category ON services(category_id);
CREATE INDEX idx_services_active ON services(is_active);

-- PROVIDER_SERVICES
CREATE TABLE provider_services (
    id SERIAL PRIMARY KEY,
    provider_id INTEGER NOT NULL REFERENCES providers(id) ON DELETE CASCADE,
    service_id INTEGER NOT NULL REFERENCES services(id) ON DELETE CASCADE,
    price DECIMAL(10, 2) NOT NULL,
    min_price DECIMAL(10, 2),
    experience_years INTEGER,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(provider_id, service_id)
);

-- PROVIDER_ZONES
CREATE TABLE provider_zones (
    id SERIAL PRIMARY KEY,
    provider_id INTEGER NOT NULL REFERENCES providers(id) ON DELETE CASCADE,
    governorate VARCHAR(100) NOT NULL,
    cities JSONB,
    radius_km INTEGER DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_provider_zones_gov ON provider_zones(governorate);

-- BOOKINGS (Réservations)
CREATE TABLE bookings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    booking_number VARCHAR(50) NOT NULL UNIQUE,
    client_id UUID NOT NULL REFERENCES users(id),
    provider_id UUID NOT NULL REFERENCES users(id),
    service_id INTEGER NOT NULL REFERENCES services(id),
    address_id INTEGER NOT NULL REFERENCES addresses(id),
    scheduled_at TIMESTAMP NOT NULL,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    cancelled_at TIMESTAMP,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'confirmed', 'in_progress', 'completed', 'cancelled')),
    price DECIMAL(10, 2) NOT NULL,
    discount DECIMAL(10, 2) DEFAULT 0.00,
    total DECIMAL(10, 2) NOT NULL,
    commission DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50),
    payment_status VARCHAR(20) DEFAULT 'pending' CHECK (payment_status IN ('pending', 'paid', 'refunded')),
    instructions TEXT,
    cancellation_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_bookings_client ON bookings(client_id);
CREATE INDEX idx_bookings_provider ON bookings(provider_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_bookings_scheduled ON bookings(scheduled_at);
CREATE INDEX idx_bookings_number ON bookings(booking_number);

-- BOOKING_PHOTOS
CREATE TABLE booking_photos (
    id SERIAL PRIMARY KEY,
    booking_id UUID NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
    type VARCHAR(20) CHECK (type IN ('before', 'after', 'issue')),
    url VARCHAR(500) NOT NULL,
    uploaded_by UUID NOT NULL REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- REVIEWS
CREATE TABLE reviews (
    id SERIAL PRIMARY KEY,
    booking_id UUID NOT NULL UNIQUE REFERENCES bookings(id),
    client_id UUID NOT NULL REFERENCES users(id),
    provider_id UUID NOT NULL REFERENCES users(id),
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    professionalism_rating INTEGER CHECK (professionalism_rating >= 1 AND professionalism_rating <= 5),
    quality_rating INTEGER CHECK (quality_rating >= 1 AND quality_rating <= 5),
    value_rating INTEGER CHECK (value_rating >= 1 AND value_rating <= 5),
    comment TEXT,
    response TEXT,
    responded_at TIMESTAMP,
    is_verified BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_reviews_provider ON reviews(provider_id);
CREATE INDEX idx_reviews_rating ON reviews(rating);

-- PAYMENTS
CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    booking_id UUID NOT NULL REFERENCES bookings(id),
    amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    gateway VARCHAR(50),
    transaction_id VARCHAR(255),
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'failed', 'refunded')),
    metadata JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_payments_booking ON payments(booking_id);
CREATE INDEX idx_payments_transaction ON payments(transaction_id);

-- PAYOUTS
CREATE TABLE payouts (
    id SERIAL PRIMARY KEY,
    provider_id INTEGER NOT NULL REFERENCES providers(id),
    amount DECIMAL(10, 2) NOT NULL,
    bank_account VARCHAR(255),
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
    processed_at TIMESTAMP,
    reference VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- NOTIFICATIONS
CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    data JSONB,
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(read_at);
```

### 10.3 Services Backend (Laravel)

#### 10.3.1 BookingService.php

```php
<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Provider;
use App\Jobs\SendPushNotification;
use Illuminate\Support\Str;

class BookingService
{
    protected $matchingService;
    protected $notificationService;
    
    public function __construct(
        MatchingService $matchingService,
        NotificationService $notificationService
    ) {
        $this->matchingService = $matchingService;
        $this->notificationService = $notificationService;
    }
    
    /**
     * Créer une nouvelle réservation
     */
    public function createBooking(array $data)
    {
        // Générer numéro de réservation unique
        $bookingNumber = $this->generateBookingNumber();
        
        // Calculer le prix total
        $pricing = $this->calculatePricing($data);
        
        // Créer la réservation
        $booking = Booking::create([
            'booking_number' => $bookingNumber,
            'client_id' => $data['client_id'],
            'provider_id' => $data['provider_id'],
            'service_id' => $data['service_id'],
            'address_id' => $data['address_id'],
            'scheduled_at' => $data['scheduled_at'],
            'price' => $pricing['base_price'],
            'discount' => $pricing['discount'],
            'total' => $pricing['total'],
            'commission' => $pricing['commission'],
            'payment_method' => $data['payment_method'],
            'instructions' => $data['instructions'] ?? null,
            'status' => 'pending'
        ]);
        
        // Notifier le prestataire
        $this->notifyProvider($booking);
        
        return $booking;
    }
    
    /**
     * Trouver les prestataires disponibles
     */
    public function findAvailableProviders($serviceId, $location, $datetime, $filters = [])
    {
        $query = Provider::query()
            ->where('status', 'active')
            ->whereHas('services', function($q) use ($serviceId) {
                $q->where('service_id', $serviceId)
                  ->where('is_available', true);
            });
        
        // Filtre par zone géographique
        if (isset($location['governorate'])) {
            $query->whereHas('zones', function($q) use ($location) {
                $q->where('governorate', $location['governorate']);
            });
        }
        
        // Filtre par note minimale
        if (isset($filters['min_rating'])) {
            $query->where('rating_average', '>=', $filters['min_rating']);
        }
        
        // Filtre par genre (pour services sensibles)
        if (isset($filters['gender'])) {
            $query->whereHas('user', function($q) use ($filters) {
                $q->where('gender', $filters['gender']);
            });
        }
        
        // Prestataires vérifiés uniquement
        if (isset($filters['verified_only']) && $filters['verified_only']) {
            $query->whereNotNull('verified_at');
        }
        
        $providers = $query->with(['user', 'services', 'zones'])->get();
        
        // Calculer le score de pertinence pour chaque prestataire
        $scoredProviders = $providers->map(function($provider) use ($location, $serviceId) {
            $score = $this->matchingService->calculateRelevanceScore(
                $provider, 
                $location,
                $serviceId
            );
            
            $provider->relevance_score = $score;
            $provider->distance = $this->matchingService->calculateDistance(
                $provider->zones->first(),
                $location
            );
            
            return $provider;
        });
        
        // Trier par score de pertinence
        return $scoredProviders->sortByDesc('relevance_score')->values();
    }
    
    /**
     * Calculer le prix de la réservation
     */
    protected function calculatePricing(array $data)
    {
        $providerService = \App\Models\ProviderService::where([
            'provider_id' => $data['provider_id'],
            'service_id' => $data['service_id']
        ])->first();
        
        $basePrice = $providerService->price;
        
        // Appliquer les majorations
        $multiplier = 1.0;
        
        // Urgence
        if (isset($data['is_urgent']) && $data['is_urgent']) {
            $multiplier += 0.20; // +20%
        }
        
        // Horaire nuit
        $hour = \Carbon\Carbon::parse($data['scheduled_at'])->hour;
        if ($hour >= 22 || $hour < 6) {
            $multiplier += 0.30; // +30%
        }
        
        // Weekend
        $isWeekend = \Carbon\Carbon::parse($data['scheduled_at'])->isWeekend();
        if ($isWeekend) {
            $multiplier += 0.15; // +15%
        }
        
        $adjustedPrice = $basePrice * $multiplier;
        
        // Calculer la réduction si code promo
        $discount = 0;
        if (isset($data['promo_code'])) {
            $discount = $this->applyPromoCode($data['promo_code'], $adjustedPrice);
        }
        
        $total = $adjustedPrice - $discount;
        
        // Calculer la commission
        $service = \App\Models\Service::find($data['service_id']);
        $commissionRate = $service->commission_rate;
        
        // Réduction commission pour prestataires Premium
        $provider = Provider::find($data['provider_id']);
        if ($provider->subscription_type === 'premium') {
            $commissionRate = 15.00;
        } elseif ($provider->subscription_type === 'pro') {
            $commissionRate = 17.00;
        }
        
        $commission = $total * ($commissionRate / 100);
        
        return [
            'base_price' => $basePrice,
            'adjusted_price' => $adjustedPrice,
            'discount' => $discount,
            'total' => $total,
            'commission' => $commission,
            'commission_rate' => $commissionRate
        ];
    }
    
    /**
     * Compléter une réservation
     */
    public function completeBooking($bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        
        if ($booking->status !== 'in_progress') {
            throw new \Exception('La réservation doit être en cours');
        }
        
        $booking->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
        
        // Traiter le paiement si pas encore fait
        if ($booking->payment_status === 'pending') {
            app(PaymentService::class)->processPayment($booking);
        }
        
        // Notifier le client pour demander un avis
        SendPushNotification::dispatch(
            $booking->client_id,
            'Intervention terminée',
            'Comment s\'est passée votre intervention ? Notez votre prestataire',
            ['booking_id' => $booking->id, 'type' => 'request_review']
        )->delay(now()->addHours(2));
        
        // Mettre à jour les statistiques du prestataire
        $this->updateProviderStats($booking->provider_id);
        
        return $booking;
    }
    
    /**
     * Annuler une réservation
     */
    public function cancelBooking($bookingId, $cancelledBy, $reason)
    {
        $booking = Booking::findOrFail($bookingId);
        
        if (in_array($booking->status, ['completed', 'cancelled'])) {
            throw new \Exception('Cette réservation ne peut pas être annulée');
        }
        
        // Calculer les frais d'annulation
        $cancellationFees = $this->calculateCancellationFees($booking, $cancelledBy);
        
        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy
        ]);
        
        // Traiter le remboursement si nécessaire
        if ($booking->payment_status === 'paid' && $cancellationFees['refund_amount'] > 0) {
            app(PaymentService::class)->processRefund(
                $booking,
                $cancellationFees['refund_amount']
            );
        }
        
        // Appliquer les pénalités si applicable
        if ($cancelledBy === 'provider' && $cancellationFees['penalty'] > 0) {
            $this->applyProviderPenalty($booking->provider_id, $cancellationFees['penalty']);
        }
        
        // Notifier les parties
        $this->notifyCancellation($booking, $cancelledBy);
        
        return [
            'booking' => $booking,
            'cancellation_fees' => $cancellationFees
        ];
    }
    
    /**
     * Calculer les frais d'annulation
     */
    protected function calculateCancellationFees($booking, $cancelledBy)
    {
        $hoursUntilBooking = now()->diffInHours($booking->scheduled_at, false);
        
        $fees = [
            'cancellation_fee' => 0,
            'refund_amount' => $booking->total,
            'penalty' => 0
        ];
        
        if ($cancelledBy === 'client') {
            if ($hoursUntilBooking < 2) {
                // Moins de 2h : Frais 50%
                $fees['cancellation_fee'] = $booking->total * 0.50;
                $fees['refund_amount'] = $booking->total * 0.50;
            } elseif ($hoursUntilBooking < 24) {
                // 2-24h : Frais 20%
                $fees['cancellation_fee'] = $booking->total * 0.20;
                $fees['refund_amount'] = $booking->total * 0.80;
            }
            // Plus de 24h : Remboursement total
        } elseif ($cancelledBy === 'provider') {
            // Pénalité pour le prestataire
            if ($hoursUntilBooking < 24) {
                $fees['penalty'] = 50; // 50 TND de pénalité
            }
            $fees['refund_amount'] = $booking->total; // Remboursement total client
        }
        
        return $fees;
    }
    
    /**
     * Générer un numéro de réservation unique
     */
    protected function generateBookingNumber()
    {
        do {
            $number = 'BOOK-' . strtoupper(Str::random(5)) . rand(1000, 9999);
        } while (Booking::where('booking_number', $number)->exists());
        
        return $number;
    }
    
    /**
     * Notifier le prestataire d'une nouvelle demande
     */
    protected function notifyProvider($booking)
    {
        SendPushNotification::dispatch(
            $booking->provider_id,
            '🔔 Nouvelle demande !',
            "Réservation {$booking->service->name_fr} - {$booking->total} TND",
            [
                'booking_id' => $booking->id,
                'type' => 'new_booking',
                'scheduled_at' => $booking->scheduled_at->toIso8601String()
            ]
        );
    }
    
    /**
     * Mettre à jour les statistiques du prestataire
     */
    protected function updateProviderStats($providerId)
    {
        $provider = Provider::find($providerId);
        
        $completedBookings = Booking::where('provider_id', $providerId)
            ->where('status', 'completed')
            ->count();
        
        $totalBookings = Booking::where('provider_id', $providerId)
            ->whereIn('status', ['completed', 'cancelled'])
            ->count();
        
        $completionRate = $totalBookings > 0 
            ? ($completedBookings / $totalBookings) * 100 
            : 100;
        
        $provider->update([
            'completion_rate' => $completionRate
        ]);
    }
}
```

#### 10.3.2 MatchingService.php

```php
<?php

namespace App\Services;

class MatchingService
{
    /**
     * Calculer le score de pertinence d'un prestataire
     */
    public function calculateRelevanceScore($provider, $location, $serviceId)
    {
        // Score distance (30%)
        $distanceScore = $this->calculateDistanceScore($provider, $location);
        
        // Score notation (25%)
        $ratingScore = $this->calculateRatingScore($provider);
        
        // Score prix (20%)
        $priceScore = $this->calculatePriceScore($provider, $serviceId);
        
        // Score disponibilité (15%)
        $availabilityScore = $this->calculateAvailabilityScore($provider);
        
        // Score historique (10%)
        $historyScore = $this->calculateHistoryScore($provider);
        
        $totalScore = ($distanceScore * 0.3) 
                    + ($ratingScore * 0.25)
                    + ($priceScore * 0.20)
                    + ($availabilityScore * 0.15)
                    + ($historyScore * 0.10);
        
        return round($totalScore, 2);
    }
    
    /**
     * Calculer le score basé sur la distance
     */
    protected function calculateDistanceScore($provider, $location)
    {
        $distance = $this->calculateDistance($provider->zones->first(), $location);
        
        if ($distance < 2) return 100;
        if ($distance < 5) return 80;
        if ($distance < 10) return 60;
        if ($distance < 15) return 40;
        return 20;
    }
    
    /**
     * Calculer la distance entre deux points (formule de Haversine)
     */
    public function calculateDistance($providerZone, $clientLocation)
    {
        if (!isset($clientLocation['latitude']) || !isset($clientLocation['longitude'])) {
            return 999; // Distance très grande si pas de coordonnées
        }
        
        // Utiliser le centre du gouvernorat du prestataire
        // (Dans une vraie implémentation, on aurait les coordonnées précises)
        $providerLat = $this->getGovernorateCoordinates($providerZone->governorate)['lat'];
        $providerLng = $this->getGovernorateCoordinates($providerZone->governorate)['lng'];
        
        $lat1 = deg2rad($providerLat);
        $lon1 = deg2rad($providerLng);
        $lat2 = deg2rad($clientLocation['latitude']);
        $lon2 = deg2rad($clientLocation['longitude']);
        
        $earthRadius = 6371; // km
        
        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos($lat1) * cos($lat2) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
    
    /**
     * Score basé sur la notation
     */
    protected function calculateRatingScore($provider)
    {
        if ($provider->rating_count === 0) {
            return 50; // Score neutre pour nouveaux prestataires
        }
        
        $baseScore = ($provider->rating_average / 5) * 100;
        
        // Bonus pour nombre d'avis élevé (confiance)
        $reviewBonus = min(($provider->rating_count / 100) * 10, 10);
        
        return min($baseScore + $reviewBonus, 100);
    }
    
    /**
     * Score basé sur la compétitivité du prix
     */
    protected function calculatePriceScore($provider, $serviceId)
    {
        $providerService = $provider->services->firstWhere('id', $serviceId);
        
        if (!$providerService) {
            return 0;
        }
        
        // Calculer le prix moyen du marché pour ce service
        $averagePrice = \App\Models\ProviderService::where('service_id', $serviceId)
            ->avg('price');
        
        $priceRatio = $providerService->pivot->price / $averagePrice;
        
        // Score optimal pour prix = moyenne du marché
        // Pénalité si trop cher ou trop bon marché (suspect)
        if ($priceRatio >= 0.8 && $priceRatio <= 1.2) {
            return 100;
        } elseif ($priceRatio < 0.8) {
            return 70; // Trop bon marché = suspect
        } else {
            return max(100 - (($priceRatio - 1.2) * 100), 0);
        }
    }
    
    /**
     * Score basé sur la disponibilité
     */
    protected function calculateAvailabilityScore($provider)
    {
        $score = 70; // Base
        
        // Bonus si temps de réponse rapide
        if ($provider->response_time_avg < 600) { // < 10 min
            $score += 30;
        } elseif ($provider->response_time_avg < 1800) { // < 30 min
            $score += 20;
        } elseif ($provider->response_time_avg < 3600) { // < 1h
            $score += 10;
        }
        
        return min($score, 100);
    }
    
    /**
     * Score basé sur l'historique
     */
    protected function calculateHistoryScore($provider)
    {
        return $provider->completion_rate;
    }
    
    /**
     * Obtenir les coordonnées d'un gouvernorat (centres approximatifs)
     */
    protected function getGovernorateCoordinates($governorate)
    {
        $coordinates = [
            'Tunis' => ['lat' => 36.8065, 'lng' => 10.1815],
            'Ariana' => ['lat' => 36.8625, 'lng' => 10.1956],
            'Ben Arous' => ['lat' => 36.7469, 'lng' => 10.2306],
            'Manouba' => ['lat' => 36.8080, 'lng' => 10.0965],
            'Sousse' => ['lat' => 35.8256, 'lng' => 10.6346],
            'Sfax' => ['lat' => 34.7406, 'lng' => 10.7603],
            // Ajouter tous les gouvernorats
        ];
        
        return $coordinates[$governorate] ?? ['lat' => 36.8065, 'lng' => 10.1815];
    }
}
```

### 10.4 Application Mobile Flutter

#### 10.4.1 Structure et Architecture

```dart
// lib/main.dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'config/routes.dart';
import 'config/theme.dart';
import 'core/services/api_service.dart';
import 'core/services/storage_service.dart';
import 'features/auth/providers/auth_provider.dart';
import 'features/booking/providers/booking_provider.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialiser les services
  final storageService = StorageService();
  await storageService.init();
  
  final apiService = ApiService();
  
  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(
          create: (_) => AuthProvider(apiService, storageService),
        ),
        ChangeNotifierProvider(
          create: (_) => BookingProvider(apiService),
        ),
        // Autres providers...
      ],
      child: ServiceHubApp(),
    ),
  );
}

class ServiceHubApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'ServiceHub',
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.darkTheme,
      initialRoute: Routes.splash,
      onGenerateRoute: Routes.generateRoute,
      debugShowCheckedModeBanner: false,
    );
  }
}
```

#### 10.4.2 Widgets Réutilisables

```dart
// lib/shared/widgets/provider_card.dart
import 'package:flutter/material.dart';
import '../../core/models/provider.dart';

class ProviderCard extends StatelessWidget {
  final Provider provider;
  final VoidCallback onTap;
  
  const ProviderCard({
    Key? key,
    required this.provider,
    required this.onTap,
  }) : super(key: key);
  
  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 2,
      margin: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: EdgeInsets.all(16),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Photo prestataire
              ClipRRect(
                borderRadius: BorderRadius.circular(35),
                child: Image.network(
                  provider.avatarUrl,
                  width: 70,
                  height: 70,
                  fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => Container(
                    width: 70,
                    height: 70,
                    color: Colors.grey[300],
                    child: Icon(Icons.person, size: 40),
                  ),
                ),
              ),
              
              SizedBox(width: 16),
              
              // Informations
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Nom et badge vérifié
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            provider.fullName,
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        if (provider.isVerified)
                          Icon(
                            Icons.verified,
                            color: Colors.blue,
                            size: 20,
                          ),
                      ],
                    ),
                    
                    SizedBox(height: 4),
                    
                    // Note et avis
                    Row(
                      children: [
                        Icon(Icons.star, color: Colors.amber, size: 16),
                        SizedBox(width: 4),
                        Text(
                          '${provider.rating.toStringAsFixed(1)}',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        Text(
                          ' (${provider.reviewCount})',
                          style: TextStyle(
                            fontSize: 14,
                            color: Colors.grey[600],
                          ),
                        ),
                        SizedBox(width: 12),
                        Icon(Icons.location_on, size: 16, color: Colors.grey[600]),
                        SizedBox(width: 4),
                        Text(
                          '${provider.distance.toStringAsFixed(1)} km',
                          style: TextStyle(
                            fontSize: 14,
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    ),
                    
                    SizedBox(height: 8),
                    
                    // Spécialité et expérience
                    Text(
                      '${provider.specialty} • ${provider.yearsExperience} ans d\'exp.',
                      style: TextStyle(
                        fontSize: 13,
                        color: Colors.grey[600],
                      ),
                    ),
                    
                    SizedBox(height: 12),
                    
                    // Prix et disponibilité
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'À partir de ${provider.minPrice.toInt()} TND',
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w600,
                            color: Theme.of(context).primaryColor,
                          ),
                        ),
                        
                        if (provider.isAvailableNow)
                          Container(
                            padding: EdgeInsets.symmetric(
                              horizontal: 10,
                              vertical: 5,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.green.shade100,
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Container(
                                  width: 6,
                                  height: 6,
                                  decoration: BoxDecoration(
                                    color: Colors.green,
                                    shape: BoxShape.circle,
                                  ),
                                ),
                                SizedBox(width: 6),
                                Text(
                                  'Disponible',
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.green.shade700,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                            ),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

---

## 11. SYSTÈME DE PAIEMENT ET TARIFICATION

### 11.1 Grille Tarifaire Détaillée

#### 11.1.1 Services de Maintenance

| Service | Type | Unité | Prix Min | Prix Moyen | Prix Max | Commission |
|---------|------|-------|----------|------------|----------|------------|
| **Plomberie** ||||||
| Dépannage urgent | Intervention | Forfait | 60 TND | 80 TND | 120 TND | 18% |
| Réparation fuite | Heure | Horaire | 40 TND | 50 TND | 70 TND | 18% |
| Installation lavabo | Tâche | Forfait | 80 TND | 100 TND | 150 TND | 18% |
| Installation douche | Tâche | Forfait | 150 TND | 200 TND | 300 TND | 18% |
| Débouchage canalisation | Intervention | Forfait | 50 TND | 70 TND | 100 TND | 18% |
| **Électricité** ||||||
| Dépannage | Intervention | Forfait | 50 TND | 70 TND | 100 TND | 18% |
| Installation prise/interrupteur | Tâche | Unité | 30 TND | 40 TND | 60 TND | 18% |
| Installation lustre | Tâche | Unité | 40 TND | 60 TND | 100 TND | 18% |
| Tableau électrique | Tâche | Forfait | 200 TND | 300 TND | 500 TND | 18% |
| **Climatisation** ||||||
| Entretien simple | Service | Unité | 40 TND | 50 TND | 70 TND | 18% |
| Réparation | Heure | Horaire | 50 TND | 70 TND | 100 TND | 18% |
| Installation split | Tâche | Forfait | 150 TND | 250 TND | 400 TND | 18% |
| Recharge gaz | Service | Forfait | 80 TND | 100 TND | 150 TND | 18% |

#### 11.1.2 Services de Nettoyage

| Service | Unité | Prix/Unité | Durée moy. | Commission |
|---------|-------|------------|------------|------------|
| Ménage standard (<80m²) | Heure | 25-30 TND | 3h | 20% |
| Ménage standard (80-120m²) | Heure | 25-30 TND | 4h | 20% |
| Ménage standard (>120m²) | Heure | 25-30 TND | 5h+ | 20% |
| Ménage approfondi | Heure | 35-45 TND | Variable | 20% |
| Nettoyage après travaux | m² | 4-6 TND | Variable | 20% |
| Nettoyage vitres | m² | 3-4 TND | Variable | 20% |
| Repassage | Heure | 18-22 TND | Variable | 20% |
| Nettoyage tapis/moquette | m² | 5-8 TND | Variable | 20% |

#### 11.1.3 Services de Garde

| Service | Unité | Prix Jour | Prix Nuit | Commission |
|---------|-------|-----------|-----------|------------|
| Babysitting (0-3 ans) | Heure | 15-18 TND | 20-25 TND | 15% |
| Babysitting (3-6 ans) | Heure | 12-15 TND | 18-22 TND | 15% |
| Babysitting (>6 ans) | Heure | 12-15 TND | 15-20 TND | 15% |
| Garde nuit complète | Nuit | 80-120 TND | - | 15% |
| Garde personne âgée | Heure | 18-25 TND | 25-35 TND | 15% |
| Aide aux devoirs (primaire) | Heure | 25-30 TND | - | 15% |
| Aide aux devoirs (collège) | Heure | 30-40 TND | - | 15% |

### 11.2 Suppléments et Majorations

#### 11.2.1 Majorations Temporelles

```
BASE_PRICE = Prix du service de base

FINAL_PRICE = BASE_PRICE × MULTIPLIER

Où MULTIPLIER = 1 + Σ(majorations applicables)
```

**Grille de majorations** :

| Condition | Majoration | Cumul possible |
|-----------|------------|----------------|
| Urgence (intervention <2h) | +20% | Oui |
| Nuit (22h-6h) | +30% | Oui |
| Weekend (samedi) | +15% | Oui |
| Weekend (dimanche) | +20% | Oui |
| Jour férié | +25% | Oui |
| Période Ramadan/Aïd | +10% | Oui |

**Exemple de calcul** :
```
Service : Plomberie dépannage
Prix de base : 60 TND
Heure : Vendredi 23h30 (nuit + weekend)
Urgence : Oui

Calcul :
60 TND × (1 + 0.20 + 0.30 + 0.15) = 60 × 1.65 = 99 TND
```

#### 11.2.2 Frais Additionnels

**Frais de déplacement** :
```
SI distance <= 5 km : 5 TND
SI distance > 5 km ET <= 10 km : 10 TND
SI distance > 10 km : 10 TND + 1 TND/km supplémentaire
```

**Matériel et fournitures** :
- Petites fournitures (joints, vis, etc.) : Inclus
- Matériel spécifique : Coût réel + 10% de marge
- Équipement lourd : Sur devis préalable

**Stationnement** :
- Frais réels avec justificatif (ticket)
- Plafonné à 10 TND

### 11.3 Intégration Paiement

#### 11.3.1 PayTech (Gateway Tunisien)

```php
<?php

namespace App\Integrations;

use Illuminate\Support\Facades\Http;

class PayTechGateway
{
    protected $apiUrl;
    protected $merchantId;
    protected $apiKey;
    
    public function __construct()
    {
        $this->apiUrl = config('services.paytech.api_url');
        $this->merchantId = config('services.paytech.merchant_id');
        $this->apiKey = config('services.paytech.api_key');
    }
    
    /**
     * Créer une demande de paiement
     */
    public function createPayment(array $data)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'API-KEY' => $this->apiKey,
        ])->post($this->apiUrl . '/api/payment/request', [
            'merchant_id' => $this->merchantId,
            'amount' => $data['amount'], // en millimes
            'currency' => 'TND',
            'order_id' => $data['order_id'],
            'success_url' => $data['success_url'],
            'cancel_url' => $data['cancel_url'],
            'ipn_url' => $data['ipn_url'],
            'custom_field' => json_encode([
                'booking_id' => $data['booking_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
            ]),
        ]);
        
        if ($response->successful()) {
            return [
                'token' => $response->json()['token'],
                'redirect_url' => $response->json()['redirect_url'],
            ];
        }
        
        throw new \Exception('Erreur PayTech: ' . $response->body());
    }
    
    /**
     * Vérifier le statut d'un paiement
     */
    public function checkPaymentStatus($token)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'API-KEY' => $this->apiKey,
        ])->get($this->apiUrl . '/api/payment/status', [
            'token' => $token,
        ]);
        
        if ($response->successful()) {
            return $response->json();
        }
        
        throw new \Exception('Erreur vérification paiement');
    }
    
    /**
     * Traiter un remboursement
     */
    public function processRefund($transactionId, $amount)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'API-KEY' => $this->apiKey,
        ])->post($this->apiUrl . '/api/payment/refund', [
            'merchant_id' => $this->merchantId,
            'transaction_id' => $transactionId,
            'amount' => $amount,
        ]);
        
        if ($response->successful()) {
            return $response->json();
        }
        
        throw new \Exception('Erreur remboursement');
    }
}
```

#### 11.3.2 Webhook PayTech

```php
<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayTechWebhookController extends Controller
{
    protected $paymentService;
    
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    
    /**
     * Recevoir les notifications IPN de PayTech
     */
    public function handleIPN(Request $request)
    {
        // Logger la requête pour debug
        Log::info('PayTech IPN received', $request->all());
        
        // Vérifier la signature (sécurité)
        if (!$this->verifySignature($request)) {
            Log::error('PayTech IPN: Invalid signature');
            return response()->json(['error' => 'Invalid signature'], 403);
        }
        
        $token = $request->input('token');
        $status = $request->input('status');
        $transactionId = $request->input('transaction_id');
        
        // Récupérer le paiement
        $payment = Payment::where('transaction_id', $token)->first();
        
        if (!$payment) {
            Log::error('PayTech IPN: Payment not found', ['token' => $token]);
            return response()->json(['error' => 'Payment not found'], 404);
        }
        
        // Éviter les doublons
        if ($payment->status === 'completed') {
            return response()->json(['message' => 'Already processed'], 200);
        }
        
        try {
            if ($status === 'success') {
                // Paiement réussi
                $payment->update([
                    'status' => 'completed',
                    'gateway_transaction_id' => $transactionId,
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'paytech_response' => $request->all(),
                        'completed_at' => now()->toIso8601String(),
                    ]),
                ]);
                
                // Mettre à jour la réservation
                $booking = $payment->booking;
                $booking->update([
                    'payment_status' => 'paid'
                ]);
                
                // Créer le payout pour le prestataire
                $this->paymentService->createProviderPayout($booking);
                
                // Notifier les parties
                event(new \App\Events\PaymentCompleted($payment));
                
                Log::info('Payment completed successfully', [
                    'payment_id' => $payment->id,
                    'booking_id' => $booking->id,
                ]);
                
            } else {
                // Paiement échoué
                $payment->update([
                    'status' => 'failed',
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'paytech_response' => $request->all(),
                        'failed_at' => now()->toIso8601String(),
                    ]),
                ]);
                
                Log::warning('Payment failed', [
                    'payment_id' => $payment->id,
                    'reason' => $request->input('error_message'),
                ]);
            }
            
            return response()->json(['message' => 'IPN processed'], 200);
            
        } catch (\Exception $e) {
            Log::error('Error processing PayTech IPN', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id,
            ]);
            
            return response()->json(['error' => 'Processing error'], 500);
        }
    }
    
    /**
     * Vérifier la signature de la requête
     */
    protected function verifySignature(Request $request)
    {
        $signature = $request->header('X-PayTech-Signature');
        $payload = $request->getContent();
        
        $expectedSignature = hash_hmac(
            'sha256',
            $payload,
            config('services.paytech.webhook_secret')
        );
        
        return hash_equals($expectedSignature, $signature);
    }
}
```

### 11.4 Gestion des Versements Prestataires

```php
<?php

namespace App\Console\Commands;

use App\Models\Payout;
use App\Models\Provider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessPayouts extends Command
{
    protected $signature = 'payouts:process';
    protected $description = 'Traiter les versements en attente pour les prestataires';
    
    public function handle()
    {
        $this->info('Début du traitement des versements...');
        
        // Récupérer les payouts en attente et planifiés pour aujourd'hui
        $payouts = Payout::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->get();
        
        $this->info("Nombre de versements à traiter : {$payouts->count()}");
        
        $processed = 0;
        $failed = 0;
        
        foreach ($payouts as $payout) {
            try {
                $this->processPayoutPayout($payout);
                $processed++;
                $this->info("✓ Payout #{$payout->id} traité");
            } catch (\Exception $e) {
                $failed++;
                $this->error("✗ Erreur payout #{$payout->id}: {$e->getMessage()}");
                
                $payout->update([
                    'status' => 'failed',
                    'metadata' => [
                        'error' => $e->getMessage(),
                        'failed_at' => now()->toIso8601String(),
                    ],
                ]);
            }
        }
        
        $this->info("\nRésumé:");
        $this->info("✓ Traités : $processed");
        $this->error("✗ Échoués : $failed");
        
        return 0;
    }
    
    protected function processPayout($payout)
    {
        // Marquer comme en cours
        $payout->update(['status' => 'processing']);
        
        // Ici, intégration avec système bancaire ou service de virement
        // Pour la Tunisie : API bancaire ou traitement manuel
        
        // Simulation du processus
        // Dans la réalité, appel API bancaire ou génération fichier SEPA
        
        $reference = $this->generatePayoutReference();
        
        // Mettre à jour le statut
        $payout->update([
            'status' => 'completed',
            'processed_at' => now(),
            'reference' => $reference,
        ]);
        
        // Mettre à jour les statistiques du prestataire
        $provider = $payout->provider;
        $provider->increment('total_earnings', $payout->amount);
        
        // Notifier le prestataire
        \App\Jobs\SendPushNotification::dispatch(
            $provider->user_id,
            'Versement effectué',
            "Vous avez reçu un versement de {$payout->amount} TND",
            ['payout_id' => $payout->id, 'type' => 'payout_completed']
        );
    }
    
    protected function generatePayoutReference()
    {
        return 'PAY-' . strtoupper(\Str::random(10)) . '-' . now()->timestamp;
    }
}
```

---

## 12. GESTION DE LA QUALITÉ ET SÉCURITÉ

### 12.1 Processus de Vérification Prestataires

#### 12.1.1 Étapes de Vérification

**Étape 1 : Vérification Documents (Automatique + Manuelle)**

```php
<?php

namespace App\Services;

use App\Models\Provider;
use App\Models\ProviderDocument;
use Illuminate\Support\Facades\Http;

class ProviderVerificationService
{
    /**
     * Vérifier les documents d'un prestataire
     */
    public function verifyDocuments($providerId)
    {
        $provider = Provider::find($providerId);
        $documents = $provider->documents;
        
        $verificationResults = [];
        
        foreach ($documents as $document) {
            $result = $this->verifyDocument($document);
            $verificationResults[$document->type] = $result;
            
            if ($result['verified']) {
                $document->update(['verified_at' => now()]);
            }
        }
        
        // Si tous les documents sont vérifiés
        $allVerified = collect($verificationResults)->every(fn($r) => $r['verified']);
        
        if ($allVerified) {
            $provider->update([
                'status' => 'active',
                'verified_at' => now(),
            ]);
        }
        
        return $verificationResults;
    }
    
    /**
     * Vérifier un document individuel
     */
    protected function verifyDocument($document)
    {
        switch ($document->type) {
            case 'cin':
                return $this->verifyCIN($document);
            case 'license':
                return $this->verifyBusinessLicense($document);
            case 'certificate':
                return $this->verifyCertificate($document);
            default:
                return ['verified' => false, 'message' => 'Type inconnu'];
        }
    }
    
    /**
     * Vérifier la CIN (OCR + validation format)
     */
    protected function verifyCIN($document)
    {
        // 1. Extraction texte avec OCR
        $ocrResult = $this->performOCR($document->file_url);
        
        // 2. Validation du format CIN tunisien (8 chiffres)
        if (!preg_match('/^\d{8}$/', $ocrResult['cin_number'])) {
            return [
                'verified' => false,
                'message' => 'Format CIN invalide'
            ];
        }
        
        // 3. Vérification nom/prénom correspond
        $provider = $document->provider;
        $user = $provider->user;
        
        $nameMatch = similar_text(
            strtolower($ocrResult['full_name']),
            strtolower($user->first_name . ' ' . $user->last_name)
        ) / strlen($user->first_name . ' ' . $user->last_name) * 100;
        
        if ($nameMatch < 70) {
            return [
                'verified' => false,
                'message' => 'Nom ne correspond pas'
            ];
        }
        
        return [
            'verified' => true,
            'message' => 'CIN vérifiée',
            'data' => $ocrResult
        ];
    }
    
    /**
     * OCR pour extraction de texte
     */
    protected function performOCR($imageUrl)
    {
        // Utilisation d'un service OCR (Google Vision, AWS Textract, etc.)
        // Ici exemple simplifié
        
        $response = Http::post('https://vision.googleapis.com/v1/images:annotate', [
            'requests' => [
                [
                    'image' => ['source' => ['imageUri' => $imageUrl]],
                    'features' => [['type' => 'TEXT_DETECTION']]
                ]
            ]
        ]);
        
        $text = $response->json()['responses'][0]['fullTextAnnotation']['text'] ?? '';
        
        // Parsing du texte pour extraire informations
        return $this->parseCINText($text);
    }
    
    /**
     * Parser le texte OCR de la CIN
     */
    protected function parseCINText($text)
    {
        // Logique d'extraction spécifique au format CIN tunisien
        // Retourne un tableau avec : cin_number, full_name, birth_date, etc.
        
        return [
            'cin_number' => '12345678', // Extrait du texte
            'full_name' => 'Ahmed Ben Ali',
            'birth_date' => '1985-03-15',
        ];
    }
}
```

**Étape 2 : Entretien Téléphonique**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderInterview extends Model
{
    protected $fillable = [
        'provider_id',
        'interviewer_id',
        'scheduled_at',
        'completed_at',
        'duration_minutes',
        'questions',
        'answers',
        'score',
        'notes',
        'recommendation',
        'status',
    ];
    
    protected $casts = [
        'questions' => 'array',
        'answers' => 'array',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
    
    /**
     * Questions standard pour l'entretien
     */
    public static function getStandardQuestions($serviceType)
    {
        $baseQuestions = [
            'experience' => 'Parlez-nous de votre expérience professionnelle',
            'motivation' => 'Pourquoi souhaitez-vous rejoindre ServiceHub ?',
            'disponibilité' => 'Quelles sont vos disponibilités ?',
            'zone' => 'Dans quelles zones pouvez-vous intervenir ?',
            'tarifs' => 'Quels sont vos tarifs habituels ?',
        ];
        
        $technicalQuestions = [
            'plomberie' => [
                'Quels types de réparations maîtrisez-vous ?',
                'Possédez-vous vos propres outils ?',
                'Avez-vous des références clients ?',
            ],
            'electricite' => [
                'Êtes-vous formé aux normes électriques ?',
                'Quels types d\'installations réalisez-vous ?',
                'Possédez-vous une assurance professionnelle ?',
            ],
            // etc.
        ];
        
        return array_merge(
            $baseQuestions,
            $technicalQuestions[$serviceType] ?? []
        );
    }
}
```

**Étape 3 : Test Pratique (Optionnel)**

Pour certains services critiques (électricité, plomberie), un test pratique peut être organisé :

```php
class PracticalTest extends Model
{
    protected $fillable = [
        'provider_id',
        'service_type',
        'test_date',
        'location',
        'evaluator_id',
        'scenario',
        'result_score',
        'observations',
        'passed',
    ];
    
    /**
     * Scénarios de test par type de service
     */
    public static function getTestScenario($serviceType)
    {
        $scenarios = [
            'plomberie' => [
                'name' => 'Réparation fuite',
                'description' => 'Diagnostiquer et réparer une fuite simulée',
                'duration' => 30, // minutes
                'criteria' => [
                    'diagnostic' => 'Identification correcte du problème',
                    'outils' => 'Utilisation appropriée des outils',
                    'execution' => 'Qualité de la réparation',
                    'proprete' => 'Propreté du chantier',
                    'temps' => 'Respect du temps alloué',
                ],
            ],
            // Autres scénarios...
        ];
        
        return $scenarios[$serviceType] ?? null;
    }
}
```

#### 12.1.2 Système de Notation Continue

```php
<?php

namespace App\Services;

class ProviderScoringService
{
    /**
     * Calculer le score global d'un prestataire
     */
    public function calculateProviderScore($providerId)
    {
        $provider = Provider::with(['reviews', 'bookings'])->find($providerId);
        
        $scores = [
            'quality' => $this->calculateQualityScore($provider),
            'reliability' => $this->calculateReliabilityScore($provider),
            'responsiveness' => $this->calculateResponsivenessScore($provider),
            'professionalism' => $this->calculateProfessionalismScore($provider),
        ];
        
        // Score global (moyenne pondérée)
        $globalScore = (
            $scores['quality'] * 0.35 +
            $scores['reliability'] * 0.30 +
            $scores['responsiveness'] * 0.20 +
            $scores['professionalism'] * 0.15
        );
        
        // Déterminer le niveau
        $level = $this->determineProviderLevel($globalScore);
        
        return [
            'global_score' => round($globalScore, 2),
            'level' => $level,
            'details' => $scores,
        ];
    }
    
    /**
     * Score qualité (basé sur les avis)
     */
    protected function calculateQualityScore($provider)
    {
        $avgQualityRating = $provider->reviews()
            ->avg('quality_rating');
        
        return ($avgQualityRating / 5) * 100;
    }
    
    /**
     * Score fiabilité (taux de complétion, annulations)
     */
    protected function calculateReliabilityScore($provider)
    {
        $totalBookings = $provider->bookings()->count();
        $completedBookings = $provider->bookings()
            ->where('status', 'completed')
            ->count();
        $cancelledByProvider = $provider->bookings()
            ->where('status', 'cancelled')
            ->where('cancelled_by', 'provider')
            ->count();
        
        if ($totalBookings === 0) return 100;
        
        $completionRate = ($completedBookings / $totalBookings) * 100;
        $cancellationPenalty = ($cancelledByProvider / $totalBookings) * 30;
        
        return max(0, $completionRate - $cancellationPenalty);
    }
    
    /**
     * Score réactivité (temps de réponse)
     */
    protected function calculateResponsivenessScore($provider)
    {
        $avgResponseTime = $provider->response_time_avg; // en secondes
        
        if ($avgResponseTime < 300) return 100; // <5 min
        if ($avgResponseTime < 900) return 80;  // <15 min
        if ($avgResponseTime < 1800) return 60; // <30 min
        if ($avgResponseTime < 3600) return 40; // <1h
        return 20;
    }
    
    /**
     * Score professionnalisme (basé sur avis)
     */
    protected function calculateProfessionalismScore($provider)
    {
        $avgProfRating = $provider->reviews()
            ->avg('professionalism_rating');
        
        return ($avgProfRating / 5) * 100;
    }
    
    /**
     * Déterminer le niveau du prestataire
     */
    protected function determineProviderLevel($score)
    {
        if ($score >= 90) return 'Elite';
        if ($score >= 80) return 'Expert';
        if ($score >= 70) return 'Confirmé';
        if ($score >= 60) return 'Qualifié';
        return 'Débutant';
    }
}
```

### 12.2 Sécurité et Conformité

#### 12.2.1 Protection des Données (RGPD/INPDP)

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class DataPrivacyService
{
    /**
     * Exporter toutes les données d'un utilisateur (RGPD Article 20)
     */
    public function exportUserData($userId)
    {
        $user = User::with([
            'client',
            'provider',
            'bookings',
            'reviews',
            'addresses',
            'payments',
            'notifications'
        ])->findOrFail($userId);
        
        $data = [
            'user_info' => $user->only([
                'first_name', 'last_name', 'email', 'phone',
                'created_at', 'language'
            ]),
            'addresses' => $user->addresses->toArray(),
            'bookings' => $user->bookings->map(function($booking) {
                return $booking->only([
                    'booking_number', 'service', 'scheduled_at',
                    'status', 'total', 'created_at'
                ]);
            }),
            'reviews_given' => $user->reviewsGiven->toArray(),
            'reviews_received' => $user->reviewsReceived->toArray(),
        ];
        
        // Générer un fichier JSON
        $filename = "user_data_{$userId}_" . now()->format('Y-m-d') . ".json";
        Storage::put("exports/{$filename}", json_encode($data, JSON_PRETTY_PRINT));
        
        // Notifier l'utilisateur
        \Mail::to($user->email)->send(new \App\Mail\DataExportReady($filename));
        
        return $filename;
    }
    
    /**
     * Supprimer toutes les données d'un utilisateur (RGPD Article 17)
     */
    public function deleteUserData($userId)
    {
        $user = User::findOrFail($userId);
        
        // Vérifier qu'il n'y a pas de réservations en cours
        $activeBookings = $user->bookings()
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->count();
        
        if ($activeBookings > 0) {
            throw new \Exception('Impossible de supprimer : réservations en cours');
        }
        
        \DB::transaction(function() use ($user) {
            // Anonymiser les données au lieu de supprimer complètement
            // (pour conserver l'intégrité des historiques)
            
            $user->update([
                'first_name' => 'Utilisateur',
                'last_name' => 'Supprimé',
                'email' => 'deleted_' . $user->id . '@servicehub.tn',
                'phone' => null,
                'avatar_url' => null,
                'deleted_at' => now(),
            ]);
            
            // Supprimer les données sensibles
            $user->addresses()->delete();
            
            // Garder les réservations/avis mais anonymiser
            $user->reviews()->update(['comment' => '[Commentaire supprimé]']);
            
            // Notifier l'équipe
            \Log::info("User data deleted: {$user->id}");
        });
        
        return true;
    }
    
    /**
     * Audit trail pour conformité
     */
    public function logDataAccess($userId, $action, $context = [])
    {
        \App\Models\DataAccessLog::create([
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'context' => $context,
            'timestamp' => now(),
        ]);
    }
}
```

---

**CONTINUER AVEC PARTIE 3 →**