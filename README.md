# Tocaan — Extendable Order & Payment Management API

A Laravel 12 REST API for managing orders and payments, designed so that **adding a new
payment gateway requires minimal code changes** (Strategy pattern + config-driven factory).
Secured with JWT, fully validated, paginated, Dockerized, and covered by unit + feature tests.

---

## Tech stack

| Concern        | Choice                                   |
| -------------- | ---------------------------------------- |
| Framework      | Laravel 12 (PHP 8.4)                      |
| Auth           | JWT (`php-open-source-saver/jwt-auth`)   |
| Database (run) | MySQL 8 (Docker container)               |
| Database (test)| In-memory SQLite                         |
| Container      | docker-compose: `app` (php-fpm), `nginx`, `mysql` |
| Docs           | Postman collection (`docs/`)             |
| Tests          | PHPUnit (unit + feature)                 |

---

## Quick start (Docker)

> Requires Docker Desktop. No local PHP, Composer, or MySQL needed — everything runs in containers.

```bash
# 1. Copy environment file (a working .env is already included for the task;
#    if missing, copy the example and generate keys as shown below)
cp .env.example .env   # only if .env is not present

# 2. Build and start the containers (app + nginx + mysql)
docker compose up -d --build

# 3. Install PHP dependencies (first run only)
docker compose exec app composer install

# 4. Generate application + JWT keys (only if .env has empty APP_KEY / JWT_SECRET)
docker compose exec app php artisan key:generate
docker compose exec app php artisan jwt:secret

# 5. Run migrations and seed demo data
docker compose exec app php artisan migrate --seed
```

The API is now available at **http://localhost:8000/api**.

A demo user is seeded for you:

```
email:    demo@tocaan.test
password: password
```

### Run the tests

```bash
docker compose exec app php artisan test
```

Tests run against an in-memory SQLite database (configured in `phpunit.xml`), so they are
fast and never touch the MySQL data.

---

## API overview

All endpoints are prefixed with `/api`. Every route except `register` and `login` requires an
`Authorization: Bearer <token>` header.

### Authentication

| Method | Endpoint          | Description                  |
| ------ | ----------------- | ---------------------------- |
| POST   | `/auth/register`  | Register, returns a JWT      |
| POST   | `/auth/login`     | Login, returns a JWT         |
| GET    | `/auth/me`        | Current authenticated user   |
| POST   | `/auth/refresh`   | Issue a fresh token          |
| POST   | `/auth/logout`    | Invalidate the current token |

### Orders

| Method | Endpoint                | Description                                   |
| ------ | ----------------------- | --------------------------------------------- |
| GET    | `/orders?status=&per_page=` | List orders (paginated, filter by status) |
| POST   | `/orders`               | Create an order with items (total computed server-side) |
| GET    | `/orders/{order}`       | Show a single order                           |
| PUT    | `/orders/{order}`       | Update an order (recomputes total if items sent) |
| DELETE | `/orders/{order}`       | Delete an order (**blocked** if it has payments) |

### Payments

| Method | Endpoint                         | Description                          |
| ------ | -------------------------------- | ------------------------------------ |
| POST   | `/orders/{order}/payments`       | Process a payment via its gateway    |
| GET    | `/orders/{order}/payments`       | List payments for an order           |
| GET    | `/payments`                      | List all payments (paginated)        |

### Business rules

- **Payments require a confirmed order.** Processing a payment for a `pending` or `cancelled`
  order returns **409 Conflict**.
- **Orders with payments cannot be deleted.** Returns **409 Conflict** (also enforced at the DB
  level via a restricted foreign key).
- **Totals are always computed server-side** from the order items — a client-supplied total is
  ignored.

### Status codes used

`200` OK · `201` Created · `204` No Content · `401` Unauthenticated · `403` Forbidden ·
`404` Not Found · `409` Conflict (business rule) · `422` Validation error · `429` Too Many Requests.

---

## Security & conventions

- **JWT bearer auth** on every route except `register`/`login`.
- **Passwords hashed** with bcrypt (cost 12) via the model's `hashed` cast — never stored in plaintext.
- **Brute-force protection** — `login` and `register` are rate-limited (`429` when exceeded).
- **Ownership enforced** — `OrderPolicy` ensures a user can only access their own orders (`403` otherwise).
- **Pagination capped** — `per_page` is clamped to a maximum of 100 to prevent oversized queries.
- **JSON everywhere** — all `/api/*` responses are JSON, even errors and requests without an `Accept` header.

---

## API documentation (Postman)

Import **`docs/Tocaan-Orders-API.postman_collection.json`** into Postman.

- Endpoints are organised into **Authentication**, **Orders**, and **Payments** folders.
- Run **Authentication → Login** first — a test script captures the returned token into the
  `token` collection variable, and every other request uses it automatically.
- Each request includes example **success and error** responses (201/200/409/422).
- `base_url` defaults to `http://localhost:8000/api`.

---

## Payment gateway extensibility

The payment system uses the **Strategy pattern** behind a **config-driven factory**, so the rest
of the application never knows which gateway runs.

```
PaymentGatewayInterface  ──  process(Payment): PaymentResult
        ▲                         ▲
CreditCardGateway           PaypalGateway          (your new gateway…)

PaymentGatewayFactory  ──reads──>  config/payments.php  ──>  resolves the right strategy
```

Key files:

- `app/Services/Payments/Contracts/PaymentGatewayInterface.php` — the strategy contract.
- `app/Services/Payments/PaymentResult.php` — uniform result returned by every gateway.
- `app/Services/Payments/Gateways/` — concrete gateways (`CreditCardGateway`, `PaypalGateway`).
- `app/Services/Payments/PaymentGatewayFactory.php` — maps a method to its gateway.
- `config/payments.php` — the gateway registry + credentials (from `.env`).

### How to add a new payment gateway (e.g. Stripe)

**No controller, service, route, or migration changes are required.** Three steps:

**1. Create the gateway class** implementing `PaymentGatewayInterface`. For a simulated gateway
you can extend `AbstractSimulatedGateway` and just supply a label/prefix:

```php
// app/Services/Payments/Gateways/StripeGateway.php
namespace App\Services\Payments\Gateways;

class StripeGateway extends AbstractSimulatedGateway
{
    public function identifier(): string { return 'stripe'; }
    protected function label(): string { return 'Stripe gateway'; }
    protected function referencePrefix(): string { return 'STR-'; }
}
```

For a *real* gateway, implement `process(Payment $payment): PaymentResult` directly and call the
provider's SDK, returning `PaymentResult::success(...)` or `PaymentResult::failure(...)`.

**2. Register it** in `config/payments.php`:

```php
'stripe' => [
    'driver' => StripeGateway::class,
    'key'    => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
],
```

**3. Add credentials** to `.env`:

```env
STRIPE_KEY=sk_test_xxx
STRIPE_SECRET=sk_secret_xxx
```

That's it. `stripe` is now automatically:
- accepted by the payment validation (`ProcessPaymentRequest` reads supported methods from the registry),
- resolvable by `PaymentGatewayFactory`,
- usable via `POST /api/orders/{order}/payments` with `{ "method": "stripe" }`.

### Simulating payment outcomes

The bundled gateways **simulate** processing (no real network calls). By default every charge
succeeds. To exercise the failure path on demand, set in `.env`:

```env
PAYMENT_FAKE_OUTCOME=failed
```

The payment is still recorded, with `status = failed`. Leave the variable empty for the default
(always-successful) behaviour.

---

## Configuration reference (`.env`)

```env
AUTH_GUARD=api                 # JWT guard is the default

DB_CONNECTION=mysql
DB_HOST=mysql                  # docker-compose service name
DB_DATABASE=tocaan
DB_USERNAME=tocaan
DB_PASSWORD=secret

JWT_SECRET=...                 # generated via `php artisan jwt:secret`

PAYMENT_FAKE_OUTCOME=          # empty | failed  (simulation switch)
CREDIT_CARD_API_KEY=...
CREDIT_CARD_API_SECRET=...
PAYPAL_CLIENT_ID=...
PAYPAL_SECRET=...
```

---

## Project structure (key parts)

```
app/
├─ Enums/                       OrderStatus, PaymentStatus
├─ Exceptions/                  ApiExceptionHandler (JSON errors), BusinessRuleException (409)
├─ Http/
│  ├─ Controllers/Api/          Auth, Order, Payment controllers (thin)
│  ├─ Middleware/               ForceJsonResponse (API always responds in JSON)
│  ├─ Requests/                 FormRequest validation per write endpoint
│  └─ Resources/                Order/OrderItem/Payment API resources
├─ Models/                      User, Order, OrderItem, Payment
├─ Policies/                    OrderPolicy (ownership)
└─ Services/
   ├─ Contracts/                OrderServiceInterface, PaymentServiceInterface (DIP)
   ├─ Orders/                   OrderTotalCalculator (pricing — single responsibility)
   ├─ OrderService.php          order use-cases + delete guard
   ├─ PaymentService.php        payment use-case + confirmed-order guard
   └─ Payments/                 ← extensibility lives here (strategy + factory)
config/payments.php             gateway registry
routes/api.php                  all API routes
docs/                           Postman collection
tests/Unit, tests/Feature       39 tests
```

---

## Assumptions & notes

- **Orders are owned by the authenticated user.** Listing returns only the caller's orders, and a
  policy prevents accessing another user's order (403).
- **One payment charges the full order total.** Partial/multiple payments were out of scope per the
  task; the amount is taken from the order's computed total, not the client.
- **A simulated decline is a recorded outcome, not an error.** `POST .../payments` returns `201`
  with `status: failed` when the gateway declines; `409` is reserved for the business-rule
  violation (non-confirmed order). This keeps "the attempt was processed" distinct from "the action
  was not allowed".
- **PHP 8.4** is used in the container because the locked dependencies require `>= 8.4`.
- `.env` is git-ignored (Laravel default); a ready-to-use **`.env.example`** with working local
  defaults is committed, so `cp .env.example .env` plus the key-generation step gets you running.
