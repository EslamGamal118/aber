<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ProviderController;
use App\Http\Controllers\Admin\OrdersController;
use App\Http\Controllers\Admin\CategoriesController;
use App\Http\Controllers\Admin\OffersController;
use App\Http\Controllers\PaymentCallbackController;

Route::get('/', function () {
    return view('front.home');
});

Route::get('/terms', function () {
    return view('front.terms');
})->name('terms');

Route::get('/privacy', function () {
    return view('front.privacy');
})->name('privacy');

/*
|--------------------------------------------------------------------------
| Al Rajhi Bank payment callbacks (single / exclusive payment gateway)
|--------------------------------------------------------------------------
| The bank posts the encrypted "trandata" to responseURL / errorURL, which
| AlRajhiService::sendPayment() sets to payment.callback / payment.failed.
| CSRF is disabled for payment/* in bootstrap/app.php.
*/
Route::prefix('payment')->name('payment.')->group(function () {
    Route::match(['get', 'post'], '/callback', [PaymentCallbackController::class, 'callback'])->name('callback');
    Route::match(['get', 'post'], '/failed', [PaymentCallbackController::class, 'failed'])->name('failed');
    Route::get('/result/{order?}', [PaymentCallbackController::class, 'result'])->name('result')->middleware('signed');
});

// Admin Routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Guest Routes (for non-authenticated admins)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    });

    // Authenticated Routes (for authenticated admins)
    Route::middleware('auth:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
        
        // Client Management Routes
        Route::prefix('clients')->name('clients.')->group(function () {
            Route::get('/', [ClientController::class, 'index'])->name('index');
            Route::get('/{client}', [ClientController::class, 'show'])->name('show');
            Route::post('/{client}/block', [ClientController::class, 'block'])->name('block');
            Route::post('/{client}/unblock', [ClientController::class, 'unblock'])->name('unblock');
        });

        // Order Management Routes
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrdersController::class, 'index'])->name('index');
            Route::get('/{order}', [OrdersController::class, 'show'])->name('show');
        });

         // Profile routes
    Route::get('/profile/edit', [DashboardController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [DashboardController::class, 'update'])->name('profile.update');

    Route::prefix('offers')->name('offers.')->group(function () {
    Route::get('/', [OffersController::class, 'index'])->name('index');
    Route::get('/create', [OffersController::class, 'create'])->name('create');
    Route::post('/', [OffersController::class, 'store'])->name('store');
    Route::get('/{offer}', [OffersController::class, 'show'])->name('show');
    Route::get('/{offer}/edit', [OffersController::class, 'edit'])->name('edit');
    Route::put('/{offer}', [OffersController::class, 'update'])->name('update');
    Route::delete('/{offer}', [OffersController::class, 'destroy'])->name('destroy');
});


// Categories Management Routes
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [CategoriesController::class, 'index'])->name('index');
    Route::get('/create', [CategoriesController::class, 'create'])->name('create');
    Route::post('/', [CategoriesController::class, 'store'])->name('store');
    Route::get('/{category}', [CategoriesController::class, 'show'])->name('show');
    Route::get('/{category}/edit', [CategoriesController::class, 'edit'])->name('edit');
    Route::put('/{category}', [CategoriesController::class, 'update'])->name('update');
    Route::delete('/{category}', [CategoriesController::class, 'destroy'])->name('destroy');
});
        
        // Provider Management Routes
        Route::prefix('providers')->name('providers.')->group(function () {
            Route::get('/', [ProviderController::class, 'index'])->name('index');
            Route::get('/{provider}', [ProviderController::class, 'show'])->name('show');
            Route::post('/{provider}/approve', [ProviderController::class, 'approve'])->name('approve');
            Route::post('/{provider}/reject', [ProviderController::class, 'reject'])->name('reject');
            Route::post('/{provider}/block', [ProviderController::class, 'block'])->name('block');
            Route::post('/{provider}/unblock', [ProviderController::class, 'unblock'])->name('unblock');
            Route::get('/{provider}/document', [ProviderController::class, 'viewDocument'])->name('document');
        });
    });
});

// Provider Routes
Route::prefix('provider')->name('provider.')->group(function () {
    // Phone Verification Routes (for non-registered providers)
    Route::middleware('guest')->group(function () {
        Route::get('/phone', [\App\Http\Controllers\Provider\AuthController::class, 'showPhoneForm'])->name('phone');
        Route::post('/phone', [\App\Http\Controllers\Provider\AuthController::class, 'sendOtp'])->name('send-otp');
        Route::get('/verify', [\App\Http\Controllers\Provider\AuthController::class, 'showVerifyForm'])->name('verify');
        Route::post('/verify', [\App\Http\Controllers\Provider\AuthController::class, 'verifyOtp'])->name('verify-otp');
        Route::post('/resend-otp', [\App\Http\Controllers\Provider\AuthController::class, 'resendOtp'])->name('resend-otp');
    });
    
    // Registration Route (requires phone verification)
    Route::get('/register', [\App\Http\Controllers\Provider\AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Provider\AuthController::class, 'register'])->name('register.post');
    
    // Login Routes
    Route::get('/login', [\App\Http\Controllers\Provider\AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Provider\AuthController::class, 'login'])->name('login.post');
    
    // Authenticated Routes (for authenticated providers)
    Route::middleware('auth:provider')->group(function () {
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Provider\DashboardController::class, 'index'])->name('dashboard');
        
        // Profile Management
        Route::get('/profile', [\App\Http\Controllers\Provider\DashboardController::class, 'showProfile'])->name('profile');
        Route::put('/profile/update', [\App\Http\Controllers\Provider\DashboardController::class, 'updateProfile'])->name('update.profile');
        Route::post('/profile/avatar', [\App\Http\Controllers\Provider\DashboardController::class, 'updateAvatar'])->name('update.avatar');
        Route::put('/profile/documents', [\App\Http\Controllers\Provider\DashboardController::class, 'updateDocuments'])->name('update.documents');
        Route::put('/profile/password', [\App\Http\Controllers\Provider\DashboardController::class, 'updatePassword'])->name('update.password');
        
        // Logout
        Route::post('/logout', [\App\Http\Controllers\Provider\AuthController::class, 'logout'])->name('logout');
    });
});

// Client Routes
Route::prefix('client')->name('client.')->group(function () {
    // Phone Verification Routes (for non-registered clients)
    Route::middleware('guest')->group(function () {
        Route::get('/phone', [\App\Http\Controllers\Client\AuthController::class, 'showPhoneForm'])->name('phone');
        Route::post('/phone', [\App\Http\Controllers\Client\AuthController::class, 'sendOtp'])->name('send-otp');
        Route::post('/send-otp', [\App\Http\Controllers\Client\AuthController::class, 'sendOtp']); // Direct send-otp endpoint
        Route::get('/verify', [\App\Http\Controllers\Client\AuthController::class, 'showVerifyForm'])->name('verify');
        Route::post('/verify', [\App\Http\Controllers\Client\AuthController::class, 'verifyOtp'])->name('verify-otp');
    });
    
    // Registration Route (requires phone verification)
    Route::get('/register', [\App\Http\Controllers\Client\AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Client\AuthController::class, 'register'])->name('register.post');
    
    // Login Routes
    Route::get('/login', [\App\Http\Controllers\Client\AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Client\AuthController::class, 'login'])->name('login.post');
    
    // Authenticated Routes (for authenticated clients)
    Route::middleware('auth:client')->group(function () {
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Client\DashboardController::class, 'index'])->name('dashboard');
        
        // Profile Management
        Route::get('/profile', [\App\Http\Controllers\Client\DashboardController::class, 'showProfile'])->name('profile');
        Route::put('/profile/update', [\App\Http\Controllers\Client\DashboardController::class, 'updateProfile'])->name('update.profile');
        Route::post('/profile/avatar', [\App\Http\Controllers\Client\DashboardController::class, 'updateAvatar'])->name('update.avatar');
        Route::put('/profile/password', [\App\Http\Controllers\Client\DashboardController::class, 'updatePassword'])->name('update.password');

        // Checkout: redirect to the Al Rajhi Bank hosted payment page
        Route::get('/orders/{order}/pay', [\App\Http\Controllers\Client\OrderPaymentController::class, 'pay'])->name('orders.pay');
        
        // Logout
        Route::post('/logout', [\App\Http\Controllers\Client\AuthController::class, 'logout'])->name('logout');
    });
});