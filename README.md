<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Abeer Laravel API

Laravel API backend for Food Truck (عبير) Flutter Applications. This project serves as a backend service that provides APIs for food truck management, user authentication, orders, payments, and more.

## Requirements

- PHP >= 8.1
- Composer
- MySQL
- Laravel 10.x

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd abeer_laravel
```

2. Install dependencies:
```bash
composer install
```

3. Copy the example environment file:
```bash
cp .env.example .env
```

4. Configure your database connection in the `.env` file:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=abeeer
DB_USERNAME=root
DB_PASSWORD=your_password
```

5. Generate an application key:
```bash
php artisan key:generate
```

6. Run the migrations to create the database tables:
```bash
php artisan migrate
```

7. Create a symbolic link for file storage:
```bash
php artisan storage:link
```

8. Start the development server:
```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api`.

## API Documentation

### User Management

- **POST** `/api/users/register` - Register a new user
- **POST** `/api/users/login` - Login a user
- **PUT** `/api/users/{userId}` - Update user information
- **DELETE** `/api/users` - Delete a user
- **POST** `/api/users/add-address` - Add an address for a user
- **POST** `/api/users/add-address2` - Add a detailed address for a user
- **GET** `/api/users/get-address/{userId}` - Get a user's address
- **GET** `/api/users/get-address2/{userId}` - Get a user's detailed address
- **PUT** `/api/users/update-address/{userId}` - Update a user's address
- **PUT** `/api/users/update-address2/{userId}` - Update a user's detailed address
- **POST** `/api/users/get-password` - Request password recovery
- **POST** `/api/users/change-password` - Change a user's password
- **GET** `/api/users` - Get all users

### Car Management

- **POST** `/api/cars` - Create a new car
- **PUT** `/api/cars/{carId}` - Update car information
- **GET** `/api/cars/user/{userId}` - Get cars belonging to a user
- **GET** `/api/cars/{carId}` - Get a specific car

### Order Management

- **POST** `/api/orders` - Create a new order
- **GET** `/api/orders/user/{userId}` - Get orders belonging to a user
- **PUT** `/api/orders/{orderId}/status` - Update an order's status

### Payment (Al Rajhi Bank - single / exclusive gateway)

All checkout payments go through the Al Rajhi Bank Hosted Payment Page (`App\Services\AlRajhiService`,
bound to `App\Interfaces\PaymentGatewayInterface` in `AppServiceProvider`).

- **POST** `/api/client/orders` - Create an order; the response contains `payment_url` (Al Rajhi hosted page)
- **POST** `/api/client/orders/{orderId}/pay` - (Re)request a hosted payment page URL for an unpaid order
- **GET** `/api/client/orders/{orderId}/payment-status` - Poll payment state after the hosted page closes
- **GET** `/client/orders/{order}/pay` - Web checkout: redirects the logged-in client to the hosted page
- **POST** `/payment/callback` (`payment.callback`) - Bank response URL, completes the order on `CAPTURED`
- **POST** `/payment/failed` (`payment.failed`) - Bank error URL
- **GET** `/payment/result/{order}` (`payment.result`, signed) - Customer facing result page (web + mobile WebView)

**Payment-first workflow:** orders are created as `pending_payment` and are hidden from the provider
(feed, dashboard, analytics, notifications) until Al Rajhi returns `CAPTURED`. Only
`PaymentService::completeOrderPayment()` releases an order (`status = pending`) and fires
`App\Events\OrderPaid` (private WebSocket channel `provider.{id}`) whose queued listener
`NotifyProviderOfPaidOrder` creates the provider's in-app notification (+ optional SMS via
`ORDER_NOTIFY_PROVIDER_SMS=true`). Failed / cancelled payments keep the order hidden and silent.

Required environment variables:

```
ALRAJHI_BASE_URL=https://securepayments.alrajhibank.com.sa
ALRAJHI_TRANSPORTAL_ID=
ALRAJHI_PASSWORD=
ALRAJHI_ENCRYPTION_KEY=
ALRAJHI_IV=PGKEYENCDECIVSPC
ALRAJHI_CURRENCY_CODE=682
ALRAJHI_CALLBACK_MODE=redirect_text
```

### Menu Management

- **POST** `/api/menus` - Create a new menu
- **GET** `/api/menus` - Get all menus
- **POST** `/api/menus/{menuId}/partitions` - Create a new menu partition
- **POST** `/api/menus/partitions/{partitionId}/elements` - Create a new menu element

### Notification Management

- **POST** `/api/notifications` - Create a new notification
- **GET** `/api/notifications/user/{userId}` - Get notifications for a user
- **PUT** `/api/notifications/{notificationId}/read` - Mark a notification as read

### Comments and Feedback

- **POST** `/api/comments` - Add a comment
- **POST** `/api/feedbacks` - Add feedback

### File Upload

- **POST** `/api/images/{userId}` - Upload an image
- **GET** `/api/images/{userId}/{filename}` - Get an image
- **POST** `/api/pdfs/{userId}` - Upload a PDF document
- **GET** `/api/pdfs/{userId}/{filename}` - Get a PDF document

## Model Relationships

The application includes the following key models with relationships:

- **User**: Has many Orders, Cars, Notifications, etc.
- **Car**: Belongs to a User, has many Orders
- **Order**: Belongs to a User and a Car
- **Payment**: Has many Orders
- **Menu**: Has many Menu Partitions
- **MenuPartition**: Belongs to a Menu, has many Elements

## Flutter Integration

This API is designed to work with Flutter applications for food trucks. The API endpoints are compatible with the existing Flutter application's data structure, ensuring smooth integration.

## Security

- User passwords are hashed using Laravel's built-in security features
- APIs are protected against common vulnerabilities
- Input validation is implemented for all endpoints

## License

The Abeer Laravel API project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
