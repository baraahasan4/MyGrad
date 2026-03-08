# 🏨 Hotel Management System

A comprehensive backend system for managing hotel operations, built with **Laravel** and **MySQL**. The system supports multiple user roles and covers all hotel services from room bookings to restaurant orders and financial reporting.

---

## Features

### Role-Based Access Control
- **Admin** — Full control over staff, rooms, pricing, promotions, reports, and complaints
- **Receptionist** — Manage bookings, check-in/check-out, and service reservations
- **Restaurant Supervisor** — Handle orders, menu management, and restaurant invoices
- **Guest** — Book rooms and services, make payments, and track reservations

### Room Management
- Room booking with type-based availability checking
- Dynamic pricing engine with **day-by-day promotion calculation**
- Support for percentage and fixed discounts with date-range targeting
- Conflict detection to prevent double-booking across room types
- QR-based guest checkout with **expiring tokens (30 minutes)**

  ### Room Booking System
- Room type selection
- Booking availability checking
- Conflict detection for overlapping bookings
- Booking approval workflow
- Booking cancellation

  ### QR Checkout System
- Generate QR code for checkout
- Secure token-based verification
- Automatic room availability update after checkout

### Receptionist Features
- Create bookings on behalf of guests
- Assign rooms to confirmed bookings
- Manage guest checkouts

### Restaurant
- Menu management (add/remove items)
- Order creation by guest or on behalf of guest by supervisor
- Table management and reservation
- Order status tracking with real-time updates

### Additional Services
- massage session booking
- Pool reservation with availability checking
- Event hall booking with hospitality and decoration options

### Payment & Invoicing
- **Stripe** payment integration across all services
- Unified invoice system with full status lifecycle (unpaid / paid / cancelled)
- Card details storage (brand & last 4 digits)
- Per-guest payment history and billing records

### Reports & Analytics
- Monthly revenue reports
- Room occupancy statistics
- Invoice breakdowns exportable as PDF
- Current guest monitoring dashboard

### Security
- Token-based authentication using **Laravel Sanctum**
- Encrypted storage for sensitive guest data (National ID)
- OTP email verification on registration
- Role middleware for endpoint protection
- Secure token-based checkout verification

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 9 |
| Database | MySQL |
| Authentication | Laravel Sanctum |
| Payment | Stripe |
| Architecture | Service Layer Pattern |
| API | RESTful API |
| Tools | Postman, Git |

---

## 🚀 Local Setup

```bash
# 1. Clone the repository
git clone https://github.com/baraahasan4/MyGrad.git
cd MyGrad

# 2. Install dependencies
composer install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Set up database
# Update DB credentials in .env, then:
php artisan migrate

# 5. Set up Stripe (test mode)
# Add your Stripe test keys to .env:
# STRIPE_SECRET=sk_test_...
# STRIPE_KEY=pk_test_...

# 6. Serve the application
php artisan serve
```

---

## 📁 Architecture

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AdminController.php
│   │   ├── BookHallController.php
│   │   ├── BookRoomController.php
│   │   ├── MassageController.php
│   │   ├── PaymentController.php
│   │   ├── PoolController.php
│   │   ├── RestaurantOrderController.php
│   │   └── UserController.php
│   └── Middleware/
│       └── CheckRole.php
├── Services/
│   ├── BookingService.php              # Room booking business logic
│   ├── BookingPriceService.php         # Dynamic pricing & promotions
│   ├── BookingHallService.php          # Hall booking logic
│   ├── ConflictCheckerService.php      # Availability & overlap detection
│   ├── ConflictHandlerService.php      # Cancel conflicting bookings
│   ├── EmployeeAvailabilityService.php # Massage employee scheduling
│   ├── InvoiceService.php              # Unified invoicing system
│   ├── MassageService.php
│   └── RestaurantOrderService.php
└── Models/
```

---

## 🔑 API Overview

| Role | Base Endpoints |
|---|---|
| Guest | BookRoom, RequestMassage, RequestPoolReservation, BookHall, RestaurantOrder |
| Receptionist | ApproveBooking, CheckIn, CheckOut, AssignRoom |
| Restaurant Supervisor | ManageOrders, ManageMenu |
| Admin | ManageStaff, ManageRooms, Reports, Promotions, Pricing |

Full API documentation available via [Postman Collection](./postman/hotel.postman_collection.json)

---

## 👨‍💻 Author

**Baraa Hasan**
[GitHub](https://github.com/baraahasan4)
