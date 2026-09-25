<p align="center">
  <img src="https://capsule-render.vercel.app/api?type=waving&height=300&color=gradient&text=Hello%20Everyone&animation=fadeIn&section=header&reversal=false"/>
</p>

# 🏨 Hotel Booking API

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Stripe](https://img.shields.io/badge/Stripe-Payments-008CDD?style=for-the-badge&logo=stripe&logoColor=white)](https://stripe.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

> A fully-featured, production-ready **RESTful API** for hotel room booking,
> built with **Laravel 11**. Supports multi-role authentication, real-time
> availability search, Stripe payments, and a complete booking lifecycle.

---

## 📑 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Architecture](#-architecture)
- [Prerequisites](#-prerequisites)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Database Schema](#-database-schema)
- [API Documentation](#-api-documentation)
- [Authentication](#-authentication)
- [Roles & Permissions](#-roles--permissions)
- [Payment Flow](#-payment-flow)
- [Notifications](#-notifications)
- [Testing](#-testing)
- [Project Structure](#-project-structure)
- [Environment Variables](#-environment-variables)
- [Contributing](#-contributing)
- [License](#-license)

---

## ✨ Features

### 🔍 Search & Discovery

- Full-text hotel search by city, country, and star rating
- Real-time room availability checking with date overlap detection
- Price range and guest capacity filters
- Sort by price (ascending/descending) or rating

### 📅 Booking Management

- Complete booking lifecycle: `pending → confirmed → checked_in → checked_out`
- Automatic booking reference generation (e.g., `BK-A8Kx9mPq`)
- Date overlap prevention (no double bookings)
- Cancellation with reason tracking
- Special requests support

### 💳 Payments

- Stripe integration with PaymentIntent flow
- Secure webhook handling with signature verification
- Automatic booking confirmation on successful payment
- Refund processing
- Payment status tracking

### 👥 Multi-Role System

- **Guest** — Search, book, pay, and review
- **Hotel Owner** — Manage hotels, room types, and bookings
- **Admin** — Full system oversight and analytics

### 📧 Notifications

- Email confirmations for bookings, cancellations, and payments
- In-app database notifications
- Queued notification delivery (non-blocking)

### 📸 Image Uploads (Hotel Owner)

| Method | Endpoint                             | Description             |
| ------ | ------------------------------------ | ----------------------- |
| POST   | `/manage/hotels/{id}/images`         | Upload hotel images     |
| PUT    | `/manage/hotels/{id}/images`         | Replace all images      |
| DELETE | `/manage/hotels/{id}/images`         | Delete a single image   |
| PUT    | `/manage/hotels/{id}/images/reorder` | Reorder images          |
| POST   | `/manage/room-types/{id}/images`     | Upload room type images |
| DELETE | `/manage/room-types/{id}/images`     | Delete room type image  |

**Upload Images (multipart/form-data):**

````bash
curl -X POST http://localhost:8000/api/v1/manage/hotels/1/images \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -F "images[]=@/path/to/photo1.jpg" \
  -F "images[]=@/path/to/photo2.jpg"

### 🏗️ Architecture

- Service layer pattern (thin controllers, fat services)
- API Resources for controlled JSON responses
- Event-driven architecture (Events → Listeners)
- Database transactions for data integrity
- Eager loading to prevent N+1 queries

---

## 🛠 Tech Stack

| Layer         | Technology                            |
| ------------- | ------------------------------------- |
| **Framework** | Laravel 11                            |
| **Language**  | PHP 8.2+                              |
| **Database**  | MySQL 8.0                             |
| **Auth**      | Laravel Sanctum (Token-based)         |
| **Payments**  | Stripe API                            |
| **Roles**     | Spatie Laravel Permission             |
| **PDF**       | Laravel DomPDF                        |
| **Testing**   | Pest PHP                              |
| **Queue**     | Laravel Queue (Database/Redis driver) |
| **Mail**      | Laravel Mail (SMTP / Mailtrap)        |

---

## 🏗 Architecture

┌─────────────────────────────────────────────────────┐
│ CLIENT │
│ (Mobile App / SPA / Postman) │
└──────────────────────┬──────────────────────────────┘
│ HTTP Request
▼
┌─────────────────────────────────────────────────────┐
│ ROUTES (api.php) │
│ Public │ Auth │ Hotel-Owner │ Admin │
└──────────────────────┬──────────────────────────────┘
│
▼
┌─────────────────────────────────────────────────────┐
│ MIDDLEWARE LAYER │
│ Sanctum Auth │ Role Check │ Throttle │ CORS │
└──────────────────────┬──────────────────────────────┘
│
▼
┌─────────────────────────────────────────────────────┐
│ CONTROLLERS (Thin) │
│ Validate Input → Call Service → Return Response │
└──────────────────────┬──────────────────────────────┘
│
▼
┌─────────────────────────────────────────────────────┐
│ SERVICES (Business Logic) │
│ AvailabilityService │ BookingService │ PricingSvc │
│ PaymentService │
└──────────┬───────────────────────┬──────────────────┘
│ │
▼ ▼
┌──────────────────────┐ ┌───────────────────────────┐
│ MODELS (Eloquent) │ │ EVENTS & LISTENERS │
│ Hotel │ Room │ ... │ │ BookingCreated → Email │
└──────────┬───────────┘ │ PaymentDone → Receipt │
│ └───────────────────────────┘
▼
┌──────────────────────┐
│ DATABASE (MySQL) │
└──────────────────────┘

text

---

## 📋 Prerequisites

Before you begin, ensure you have the following installed:

- **PHP** >= 8.2
- **Composer** >= 2.5
- **MySQL** >= 8.0 (or MariaDB >= 10.6)
- **Node.js** >= 18 (optional, for frontend assets)
- **Git**

---

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/tushar786940/hotel-booking-api.git
cd hotel-booking-api
2. Install Dependencies
Bash

composer install
3. Environment Setup
Bash

cp .env.example .env
php artisan key:generate
4. Configure Database
Edit .env and update your database credentials:

env

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hotel_booking
DB_USERNAME=root
DB_PASSWORD=your_secure_password
Create the database:

Bash

mysql -u root -p -e "CREATE DATABASE hotel_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
5. Run Migrations & Seeders
Bash

# Run migrations and seed sample data
php artisan migrate:fresh --seed
This creates all tables and seeds:

3 users (Admin, Hotel Owner, Guest)
3 hotels with room types and rooms
Role assignments
6. Configure Stripe (Optional)
Add your Stripe keys to .env:

env

STRIPE_KEY=pk_test_xxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxx
7. Start the Server
Bash

php artisan serve
The API will be available at http://localhost:8000.

⚙️ Configuration
Mail Configuration (Development)
For testing emails locally, use Mailtrap:

env

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM_ADDRESS="noreply@hotelbooking.com"
MAIL_FROM_NAME="Hotel Booking API"
Queue Configuration
For processing notifications in the background:

env

QUEUE_CONNECTION=database
Run the queue worker:

Bash

php artisan queue:work
Stripe Webhook (Local Development)
Use Stripe CLI to forward webhooks:

Bash

stripe listen --forward-to localhost:8000/api/v1/webhooks/stripe
🗄 Database Schema
text

users
├── id, name, email, password, phone, avatar
│
├── hotels (1:N)
│   ├── id, user_id, name, slug, description
│   ├── address, city, state, country, zip_code
│   ├── latitude, longitude, star_rating
│   ├── check_in_time, check_out_time
│   ├── images (JSON), amenities (JSON), is_active
│   │
│   ├── room_types (1:N)
│   │   ├── id, hotel_id, name, description
│   │   ├── price_per_night, capacity, total_rooms
│   │   ├── amenities (JSON), images (JSON)
│   │   │
│   │   └── rooms (1:N)
│   │       ├── id, room_type_id, room_number
│   │       ├── floor, status, is_available
│   │       │
│   │       └── bookings (1:N)
│   │
│   └── reviews (1:N)
│
├── bookings (1:N)
│   ├── id, booking_reference, user_id
│   ├── room_id, hotel_id
│   ├── check_in, check_out, guests_count
│   ├── total_price, status, special_requests
│   │
│   ├── payments (1:1)
│   │   ├── id, booking_id, amount, currency
│   │   ├── method, transaction_id, status, paid_at
│   │
│   └── reviews (1:1)
│       ├── id, user_id, hotel_id, booking_id
│       ├── rating, comment
📡 API Documentation
Base URL
text

http://localhost:8000/api/v1
All responses follow this format:

JSON

{
  "message": "Success message",
  "data": { ... },
  "meta": { ... }
}
🔓 Public Endpoints
Authentication
Method	Endpoint	Description
POST	/register	Register a new user
POST	/login	Login & get token
Register:

Bash

POST /api/v1/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+1555000000"
}
Login:

Bash

POST /api/v1/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
Response:

JSON

{
  "message": "Login successful!",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "roles": ["guest"]
    },
    "token": "1|abc123def456..."
  }
}
Hotels & Search
Method	Endpoint	Description
GET	/hotels	List all active hotels
GET	/hotels/{slug}	Hotel details
GET	/search	Search with availability
GET	/hotels/{id}/availability	Check room availability
GET	/hotels/{id}/reviews	Get hotel reviews
List Hotels (with filters):

Bash

GET /api/v1/hotels?city=New York&star_rating=4&sort_by=price&sort_order=asc&per_page=10
Search Available Hotels:

Bash

GET /api/v1/search?city=Miami&check_in=2025-03-01&check_out=2025-03-05&guests=2&max_price=300
Check Room Availability:

Bash

GET /api/v1/hotels/1/availability?check_in=2025-03-01&check_out=2025-03-05&guests=2
Response:

JSON

{
  "data": [
    {
      "room_type": {
        "id": 1,
        "name": "Deluxe",
        "capacity": 3,
        "amenities": ["wifi", "tv", "ac", "balcony"]
      },
      "available_rooms": 4,
      "pricing": {
        "price_per_night": 299.99,
        "nights": 4,
        "base_price": 1199.96,
        "extra_guest_charge": 0,
        "subtotal": 1199.96,
        "tax_rate": "12%",
        "tax": 143.99,
        "total": 1343.95
      }
    }
  ]
}
🔒 Authenticated Endpoints
Include the token in every request:

text

Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
Profile
Method	Endpoint	Description
GET	/profile	Get user profile
PUT	/profile	Update profile
POST	/logout	Revoke token
Bookings (Guest)
Method	Endpoint	Description
GET	/bookings	List my bookings
POST	/bookings	Create booking
GET	/bookings/{id}	Booking details
POST	/bookings/{id}/cancel	Cancel booking
Create Booking:

Bash

POST /api/v1/bookings
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "room_type_id": 1,
  "check_in": "2025-03-01",
  "check_out": "2025-03-05",
  "guests_count": 2,
  "special_requests": "Late check-in, arriving around 11 PM"
}
Response:

JSON

{
  "message": "Booking created successfully! Please complete payment.",
  "data": {
    "id": 1,
    "booking_reference": "BK-A8Kx9mPq",
    "hotel": {
      "id": 1,
      "name": "Grand Palace Hotel",
      "city": "New York"
    },
    "room": {
      "room_number": "D003",
      "room_type": "Deluxe",
      "floor": 1,
      "price_per_night": 299.99
    },
    "check_in": "2025-03-01",
    "check_out": "2025-03-05",
    "nights": 4,
    "guests_count": 2,
    "total_price": 1343.95,
    "status": "pending",
    "is_cancellable": true
  }
}
Payments
Method	Endpoint	Description
POST	/bookings/{id}/pay	Initiate payment
GET	/bookings/{id}/payment-status	Check payment status
Initiate Payment:

Bash

POST /api/v1/bookings/1/pay
Authorization: Bearer YOUR_TOKEN
Response:

JSON

{
  "message": "Payment intent created. Use client_secret to complete payment.",
  "data": {
    "client_secret": "pi_xxx_secret_xxx",
    "payment_id": "pi_xxx",
    "amount": 1343.95
  }
}
Reviews
Method	Endpoint	Description
POST	/bookings/{id}/review	Submit a review
Submit Review:

Bash

POST /api/v1/bookings/1/review
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "rating": 5,
  "comment": "Absolutely amazing stay! The room was spotless and the staff was incredibly friendly."
}
🏢 Hotel Owner Endpoints
Requires hotel-owner role.

Method	Endpoint	Description
GET	/manage/hotels	List my hotels
POST	/manage/hotels	Create hotel
GET	/manage/hotels/{id}	Hotel details
PUT	/manage/hotels/{id}	Update hotel
DELETE	/manage/hotels/{id}	Delete hotel
GET	/manage/hotels/{id}/room-types	List room types
POST	/manage/hotels/{id}/room-types	Create room type
PUT	/manage/room-types/{id}	Update room type
DELETE	/manage/room-types/{id}	Delete room type
GET	/manage/hotels/{id}/bookings	View hotel bookings
PUT	/manage/bookings/{id}/status	Update booking status
Create Hotel:

Bash

POST /api/v1/manage/hotels
Authorization: Bearer OWNER_TOKEN
Content-Type: application/json

{
  "name": "My Boutique Hotel",
  "description": "A charming boutique hotel in downtown",
  "address": "100 Main Street",
  "city": "Chicago",
  "state": "IL",
  "country": "USA",
  "star_rating": 4,
  "amenities": ["wifi", "restaurant", "gym"]
}
Create Room Type (auto-generates rooms):

Bash

POST /api/v1/manage/hotels/1/room-types
Authorization: Bearer OWNER_TOKEN
Content-Type: application/json

{
  "name": "Deluxe",
  "description": "Spacious deluxe room with city view",
  "price_per_night": 179.99,
  "capacity": 3,
  "total_rooms": 5,
  "amenities": ["wifi", "tv", "ac", "minibar", "balcony"]
}
Update Booking Status (Check-in/Check-out):

Bash

PUT /api/v1/manage/bookings/1/status
Authorization: Bearer OWNER_TOKEN
Content-Type: application/json

{
  "status": "checked_in"
}
Allowed transitions:

text

pending     → confirmed, cancelled
confirmed   → checked_in, cancelled
checked_in  → checked_out
checked_out → (none)
cancelled   → (none)
🔐 Authentication
This API uses Laravel Sanctum for token-based authentication.

How It Works
text

1. User registers/logs in
2. Server creates a token (stored in personal_access_tokens table)
3. Token is returned to the client
4. Client includes token in every subsequent request:
   Authorization: Bearer 1|abc123def456...
5. Server validates the token via Sanctum middleware
Token Management
Tokens never expire by default (configurable in config/sanctum.php)
Users can have multiple tokens (e.g., one per device)
Logout revokes the current token
Tokens are hashed in the database (only the prefix is stored in plain text)
👥 Roles & Permissions
Role	Capabilities
guest	Search, book, pay, review, manage own bookings
hotel-owner	All guest features + manage hotels & rooms
admin	Full system access + analytics dashboard
Roles are managed via Spatie Laravel Permission.

💳 Payment Flow
text

┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│  Client  │     │  Laravel │     │  Stripe  │     │ Database │
└────┬─────┘     └────┬─────┘     └────┬─────┘     └────┬─────┘
     │                │                │                │
     │  1. POST /pay  │                │                │
     │───────────────>│                │                │
     │                │  2. Create     │                │
     │                │  PaymentIntent │                │
     │                │───────────────>│                │
     │                │  3. Return     │                │
     │                │  client_secret │                │
     │                │<───────────────│                │
     │  4. Return     │                │                │
     │  client_secret │                │                │
     │<───────────────│                │                │
     │                │                │                │
     │  5. Show Stripe│                │                │
     │  Payment Form  │                │                │
     │  (card details │                │                │
     │  go to Stripe) │                │                │
     │───────────────────────────────>│                │
     │                │                │                │
     │                │  6. Webhook:   │                │
     │                │  payment_intent│                │
     │                │  .succeeded    │                │
     │                │<───────────────│                │
     │                │                │   7. Update    │
     │                │                │   payment &    │
     │                │                │   booking      │
     │                │───────────────────────────────>│
     │                │                │                │
     │  8. Email      │                │                │
     │  receipt sent  │                │                │
     │<───────────────│                │                │
📧 Notifications
Event	Notification	Channels
Booking Created	Booking Confirmation	Email, Database
Booking Cancelled	Cancellation Notice	Email, Database
Payment Completed	Payment Receipt	Email, Database
All notifications are queued for non-blocking delivery.

Run the queue worker:

Bash

php artisan queue:work
🧪 Testing
Run All Tests
Bash

php artisan test
Run Specific Test Suite
Bash

# Feature tests only
php artisan test --testsuite=Feature

# With coverage
php artisan test --coverage
Run with Pest
Bash

./vendor/bin/pest
📁 Project Structure
text

hotel-booking-api/
├── app/
│   ├── Events/                          # Application events
│   │   ├── BookingCreated.php
│   │   ├── BookingCancelled.php
│   │   └── PaymentCompleted.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/V1/                  # API v1 controllers
│   │   │       ├── AuthController.php
│   │   │       ├── HotelController.php
│   │   │       ├── AvailabilityController.php
│   │   │       ├── BookingController.php
│   │   │       ├── PaymentController.php
│   │   │       ├── ReviewController.php
│   │   │       ├── RoomTypeController.php
│   │   │       ├── WebhookController.php
│   │   │       └── Admin/
│   │   │           ├── AdminHotelController.php
│   │   │           └── AdminBookingController.php
│   │   │
│   │   └── Resources/                   # API response transformers
│   │       ├── HotelResource.php
│   │       ├── HotelDetailResource.php
│   │       ├── RoomTypeResource.php
│   │       └── BookingResource.php
│   │
│   ├── Listeners/                       # Event listeners
│   │   ├── SendBookingConfirmation.php
│   │   ├── SendCancellationNotification.php
│   │   └── SendPaymentReceipt.php
│   │
│   ├── Models/                          # Eloquent models
│   │   ├── User.php
│   │   ├── Hotel.php
│   │   ├── RoomType.php
│   │   ├── Room.php
│   │   ├── Booking.php
│   │   ├── Payment.php
│   │   └── Review.php
│   │
│   ├── Notifications/                   # Notification classes
│   │   ├── BookingConfirmationNotification.php
│   │   ├── BookingCancelledNotification.php
│   │   └── PaymentReceiptNotification.php
│   │
│   └── Services/                        # Business logic layer
│       ├── AvailabilityService.php
│       ├── BookingService.php
│       ├── PricingService.php
│       └── PaymentService.php
│
├── database/
│   ├── migrations/                      # Database schema
│   ├── seeders/                         # Sample data
│   └── factories/                       # Test data factories
│
├── routes/
│   └── api.php                          # API route definitions
│
├── tests/
│   ├── Feature/                         # Integration tests
│   └── Unit/                            # Unit tests
│
├── .env.example                         # Environment template
├── composer.json                        # PHP dependencies
└── README.md                            # This file
🔧 Environment Variables
Variable	Description	Default
APP_NAME	Application name	Hotel Booking
APP_URL	Application URL	localhost:8000
DB_DATABASE	Database name	hotel_booking
DB_USERNAME	Database user	root
DB_PASSWORD	Database password	(empty)
STRIPE_KEY	Stripe publishable key	(empty)
STRIPE_SECRET	Stripe secret key	(empty)
STRIPE_WEBHOOK_SECRET	Stripe webhook secret	(empty)
MAIL_MAILER	Mail driver	smtp
MAIL_HOST	SMTP host	mailtrap.io
QUEUE_CONNECTION	Queue driver	database
🤝 Contributing
Contributions are welcome! Please follow these steps:

Fork the repository
Create a feature branch (git checkout -b feature/amazing-feature)
Commit your changes (git commit -m 'Add amazing feature')
Push to the branch (git push origin feature/amazing-feature)
Open a Pull Request
Coding Standards
Follow PSR-12 coding style
Write tests for new features
Keep controllers thin — use services for business logic
Use meaningful commit messages
📝 API Status Codes
Code	Meaning	When Used
200	OK	Successful GET, PUT requests
201	Created	Successful POST (new resource)
401	Unauthorized	Missing or invalid token
403	Forbidden	Insufficient role/permissions
404	Not Found	Resource doesn't exist
422	Unprocessable Entity	Validation errors
500	Server Error	Unexpected server-side error
📄 License
This project is open-sourced software licensed under the MIT License.

🙏 Acknowledgments
Laravel — The PHP framework for web artisans
Stripe — Payment processing
Spatie — Laravel Permission package
Mailtrap — Email testing
📬 Contact
Author: Tushar Thakur
Email: tusharthakur06958@gmail.com
GitHub: @tushar786940
⭐ If you found this project helpful, please give it a star on GitHub!
````
