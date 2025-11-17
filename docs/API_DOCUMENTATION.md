# ServiceHub Tunisie - API Documentation

## Base URL

```
Development: http://localhost:8000/api
Production: https://api.servicehub.tn/api
```

## Authentication

Most endpoints require authentication using Bearer tokens (Laravel Sanctum).

Include the token in the Authorization header:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## Authentication Endpoints

### Register

Create a new user account (client or provider).

**Endpoint:** `POST /auth/register`

**Body:**
```json
{
  "type": "client",  // or "provider"
  "first_name": "Ahmed",
  "last_name": "Ben Ali",
  "phone": "+216 98 123 456",
  "email": "ahmed@example.com",
  "password": "securepassword",
  "password_confirmation": "securepassword",
  "language": "fr",  // "ar" or "fr"

  // Required if type = "provider"
  "cin": "12345678",
  "years_experience": 5
}
```

**Response (201):**
```json
{
  "message": "Registration successful",
  "user": {
    "id": "uuid",
    "type": "client",
    "first_name": "Ahmed",
    "last_name": "Ben Ali",
    "phone": "+216 98 123 456",
    "email": "ahmed@example.com",
    "language": "fr",
    "status": "active"
  },
  "token": "1|abc123..."
}
```

---

### Login

Authenticate a user.

**Endpoint:** `POST /auth/login`

**Body:**
```json
{
  "phone": "+216 98 123 456",
  "password": "securepassword"
}
```

**Response (200):**
```json
{
  "message": "Login successful",
  "user": {
    "id": "uuid",
    "type": "client",
    "first_name": "Ahmed",
    "last_name": "Ben Ali",
    "phone": "+216 98 123 456",
    "client": {
      "subscription_type": "free",
      "loyalty_points": 150
    }
  },
  "token": "2|xyz789..."
}
```

---

### Get Current User

Get authenticated user's profile.

**Endpoint:** `GET /auth/user`

**Headers:** `Authorization: Bearer TOKEN`

**Response (200):**
```json
{
  "user": {
    "id": "uuid",
    "type": "client",
    "first_name": "Ahmed",
    "last_name": "Ben Ali",
    "phone": "+216 98 123 456",
    "email": "ahmed@example.com",
    "avatar_url": "https://...",
    "client": {
      "subscription_type": "plus",
      "loyalty_points": 350
    },
    "addresses": [...]
  }
}
```

---

### Logout

Revoke current access token.

**Endpoint:** `POST /auth/logout`

**Headers:** `Authorization: Bearer TOKEN`

**Response (200):**
```json
{
  "message": "Logout successful"
}
```

---

## Service Endpoints

### Get All Services

Get list of all active services.

**Endpoint:** `GET /services`

**Query Parameters:**
- `category_id` (optional): Filter by category
- `search` (optional): Search by name

**Response (200):**
```json
{
  "services": [
    {
      "id": 1,
      "category_id": 1,
      "name_ar": "سباكة",
      "name_fr": "Plomberie",
      "description_fr": "Réparation et installation de plomberie",
      "icon": "🚰",
      "unit": "hour",
      "base_price": "50.00",
      "commission_rate": "18.00",
      "category": {
        "id": 1,
        "name_fr": "Services de maintenance",
        "icon": "🔧"
      }
    }
  ]
}
```

---

### Get Service Details

Get detailed information about a specific service.

**Endpoint:** `GET /services/{id}`

**Response (200):**
```json
{
  "service": {
    "id": 1,
    "name_fr": "Plomberie",
    "description_fr": "Réparation et installation...",
    "base_price": "50.00",
    "average_price": "55.00",
    "category": {...}
  }
}
```

---

### Get Service Categories

Get all service categories with their services.

**Endpoint:** `GET /service-categories`

**Response (200):**
```json
{
  "categories": [
    {
      "id": 1,
      "name_ar": "خدمات الصيانة",
      "name_fr": "Services de maintenance",
      "icon": "🔧",
      "color": "#2196F3",
      "services": [...]
    }
  ]
}
```

---

## Provider Endpoints

### Search Providers

Search for providers based on service, location, and filters.

**Endpoint:** `POST /providers/search`

**Body:**
```json
{
  "service_id": 1,
  "location": {
    "governorate": "Tunis",
    "latitude": 36.8065,
    "longitude": 10.1815
  },
  "filters": {
    "min_rating": 4.0,
    "verified_only": true
  }
}
```

**Response (200):**
```json
{
  "providers": [
    {
      "id": 1,
      "user_id": "uuid",
      "business_name": "Plomberie Pro",
      "rating_average": "4.75",
      "rating_count": 120,
      "completion_rate": "98.50",
      "response_time_avg": 450,
      "verified_at": "2024-01-15T10:00:00Z",
      "relevance_score": 89.5,
      "distance": 3.2,
      "user": {
        "first_name": "Mohamed",
        "last_name": "Trabelsi",
        "avatar_url": "https://..."
      },
      "services": [
        {
          "id": 1,
          "name_fr": "Plomberie",
          "pivot": {
            "price": "55.00",
            "experience_years": 8,
            "is_available": true
          }
        }
      ]
    }
  ]
}
```

---

### Get Provider Details

Get detailed information about a provider.

**Endpoint:** `GET /providers/{id}`

**Response (200):**
```json
{
  "provider": {
    "id": 1,
    "user_id": "uuid",
    "business_name": "Plomberie Pro",
    "rating_average": "4.75",
    "rating_count": 120,
    "bio": "Expert en plomberie depuis 10 ans...",
    "years_experience": 10,
    "verified_at": "2024-01-15T10:00:00Z",
    "user": {...},
    "services": [...],
    "zones": [...],
    "reviews": [...]
  }
}
```

---

## Booking Endpoints

### Create Booking

Create a new service booking.

**Endpoint:** `POST /bookings`

**Headers:** `Authorization: Bearer TOKEN`

**Body:**
```json
{
  "provider_id": "uuid",
  "service_id": 1,
  "address_id": 1,
  "scheduled_at": "2024-12-25 10:00:00",
  "payment_method": "card",
  "instructions": "Appeler 30 minutes avant",
  "is_urgent": false,
  "promo_code": "WELCOME20"
}
```

**Response (201):**
```json
{
  "message": "Booking created successfully",
  "booking": {
    "id": "uuid",
    "booking_number": "BOOK-ABC12345",
    "client_id": "uuid",
    "provider_id": "uuid",
    "service_id": 1,
    "scheduled_at": "2024-12-25 10:00:00",
    "status": "pending",
    "price": "50.00",
    "discount": "10.00",
    "total": "40.00",
    "commission": "7.20",
    "service": {...},
    "provider": {...},
    "address": {...}
  }
}
```

---

### Get My Bookings

Get list of user's bookings.

**Endpoint:** `GET /bookings`

**Headers:** `Authorization: Bearer TOKEN`

**Query Parameters:**
- `status` (optional): Filter by status (pending, confirmed, in_progress, completed, cancelled)

**Response (200):**
```json
{
  "bookings": {
    "data": [
      {
        "id": "uuid",
        "booking_number": "BOOK-ABC12345",
        "scheduled_at": "2024-12-25 10:00:00",
        "status": "confirmed",
        "total": "50.00",
        "service": {...},
        "provider": {...}
      }
    ],
    "current_page": 1,
    "per_page": 20,
    "total": 5
  }
}
```

---

### Get Booking Details

Get detailed information about a booking.

**Endpoint:** `GET /bookings/{id}`

**Headers:** `Authorization: Bearer TOKEN`

**Response (200):**
```json
{
  "booking": {
    "id": "uuid",
    "booking_number": "BOOK-ABC12345",
    "scheduled_at": "2024-12-25 10:00:00",
    "started_at": null,
    "completed_at": null,
    "status": "confirmed",
    "price": "50.00",
    "total": "50.00",
    "commission": "9.00",
    "payment_status": "pending",
    "service": {...},
    "provider": {...},
    "address": {...},
    "photos": [],
    "review": null
  }
}
```

---

### Cancel Booking

Cancel a booking.

**Endpoint:** `POST /bookings/{id}/cancel`

**Headers:** `Authorization: Bearer TOKEN`

**Body:**
```json
{
  "reason": "Changement de plans"
}
```

**Response (200):**
```json
{
  "message": "Booking cancelled successfully",
  "booking": {...},
  "cancellation_fees": {
    "cancellation_fee": "10.00",
    "refund_amount": "40.00",
    "penalty": 0
  }
}
```

---

### Confirm Booking (Provider Only)

Confirm a pending booking.

**Endpoint:** `POST /bookings/{id}/confirm`

**Headers:** `Authorization: Bearer TOKEN`

**Response (200):**
```json
{
  "message": "Booking confirmed successfully",
  "booking": {...}
}
```

---

### Start Booking (Provider Only)

Mark booking as in progress.

**Endpoint:** `POST /bookings/{id}/start`

**Headers:** `Authorization: Bearer TOKEN`

**Response (200):**
```json
{
  "message": "Booking started successfully",
  "booking": {...}
}
```

---

### Complete Booking (Provider Only)

Mark booking as completed.

**Endpoint:** `POST /bookings/{id}/complete`

**Headers:** `Authorization: Bearer TOKEN`

**Response (200):**
```json
{
  "message": "Booking completed successfully",
  "booking": {...}
}
```

---

## Pricing & Surcharges

### Base Pricing

Each service has a base price set by the provider.

### Surcharges

- **Urgent (< 2h):** +20%
- **Night time (22h-6h):** +30%
- **Weekend:** +15%

Surcharges are cumulative.

### Commission Rates

Default commission rates by service category:
- Maintenance: 18%
- Cleaning: 20%
- Care: 15%
- Transport: 18%
- Beauty: 20%

Provider subscription discounts:
- **Starter:** Standard rate
- **Pro:** -1% commission
- **Premium:** -3% commission

---

## Error Responses

### Validation Error (422)

```json
{
  "message": "Validation failed",
  "errors": {
    "phone": ["The phone field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### Unauthorized (401)

```json
{
  "message": "Unauthenticated."
}
```

### Not Found (404)

```json
{
  "message": "Resource not found."
}
```

### Server Error (500)

```json
{
  "message": "Failed to process request",
  "error": "Error details..."
}
```

---

## Rate Limiting

API requests are rate limited to:
- **Authenticated:** 60 requests per minute
- **Unauthenticated:** 30 requests per minute

---

## Postman Collection

Import the Postman collection for easy testing:
[Download Collection](#)

---

**Last updated:** November 2025
**API Version:** 1.0.0
