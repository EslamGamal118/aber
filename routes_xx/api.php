<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*===============================================================================*/
/*============================= Shared Controllers ==============================*/
/*===============================================================================*/
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CitiesController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AllProductsController;
use App\Http\Controllers\Api\PaymobCallbackController;
use App\Http\Controllers\Api\ClientCarController;
use App\Http\Controllers\Api\FilesController;
use App\Http\Controllers\Api\ImageController;
/*===============================================================================*/

/*===============================================================================*/
/*============================= Client Controllers ==============================*/
/*===============================================================================*/
use App\Http\Controllers\Api\Client\ClientUserController;
use App\Http\Controllers\Api\Client\FavoriteController;
use App\Http\Controllers\Api\Client\ResetPasswordController;
use App\Http\Controllers\Api\Client\ClientsController;
use App\Http\Controllers\Api\Client\ClientProfileController;
use App\Http\Controllers\Api\Client\ProvidersController;
use App\Http\Controllers\Api\Client\ClientOffersController;
use App\Http\Controllers\Api\Client\ClientNotificationsController;
use App\Http\Controllers\Api\Client\OrderController;
use App\Http\Controllers\Api\Client\RatingsController;
use App\Http\Controllers\Api\Client\PaymentCardsController;
use App\Http\Controllers\Api\Client\DeleteAccountController;
/*===============================================================================*/

/*===============================================================================*/
/*============================= Provider Controllers ============================*/
/*===============================================================================*/
use App\Http\Controllers\Api\Vendor\VendorProfileController;
use App\Http\Controllers\Api\Vendor\VendorSalesController;
use App\Http\Controllers\Api\Vendor\ProductController;
use App\Http\Controllers\Api\Vendor\ForgetPasswordController;
use App\Http\Controllers\Api\Vendor\AnalyticsController;
use App\Http\Controllers\Api\Vendor\NotificationsController;
use App\Http\Controllers\Api\Vendor\VendorOrdersController;
use App\Http\Controllers\Api\Vendor\VendorMenuController;
use App\Http\Controllers\Api\Vendor\PartitionsController;
use App\Http\Controllers\Api\Vendor\TimetablesController;
use App\Http\Controllers\Api\Vendor\VendorAuthController;
use App\Http\Controllers\Api\Vendor\VendorCarController;
use App\Http\Controllers\Api\Vendor\OffersController;
/*===============================================================================*/

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*==================================================================================*/
//================================= Shared Routes ====================================
/*==================================================================================*/

// Auth Routes
Route::controller(AuthController::class)->group(function () {
    Route::post('/auth/send-otp', 'sendOtp');
    Route::post('/auth/verify-otp', 'verifyOtp');
});

// Files Routes
Route::controller(FilesController::class)->group(function () {
    Route::post('/upload', 'upload');
    Route::delete('/delete', 'deleteFile');
});

// Categories Routes (Public)
Route::controller(CategoryController::class)->group(function () {
    Route::get('/categories', 'index');
    Route::get('/categories/{id}','show');
});
/*==================================================================================*/
// Client Authentication Routes
Route::prefix('client')->group(function () {

    Route::controller(ClientsController::class)->group(function () {
        Route::post('/register', 'register');
        Route::post('/login', 'login');
        Route::post('/logout', 'logout')->middleware('auth:sanctum');
    });

    /*==============================================================================*/
    // Public Routes (Guest Accessible - No Auth Required)
    // These endpoints allow users to browse without registration (Apple Guideline 5.1.1)
    /*==============================================================================*/

    // Browse Providers & Products (Public)
    Route::controller(ProvidersController::class)->group(function () {
        Route::get('/providers', 'index'); // Get All Providers and search by name
        Route::get('/providers/nearby', 'nearby'); // Get All nearby Providers
        Route::get('/highestRatedProducts', 'highestRated');  // Get All highest rated products
        Route::get('/providers/{provider_id}', 'showProvider'); // Get Specific Provider
        Route::get('/products/{product_id}', 'showProduct'); // Get Specific Product
        Route::get('/providers/{provider_id}/menus', 'providerMenus'); // Get Specific Provider Menus
        Route::get('/providers/menus/{menu_id}/partitions', 'menuPartitions'); // Get Specific Menu Partitions
        Route::get('/providers/menus/{menu_id}/partitions/{partition_id}/products', 'partitionProducts'); // Get Specific Partition Products
    });

    // Browse Offers (Public)
    Route::controller(ClientOffersController::class)->group(function () {
        Route::get('/offers', 'getAdminOffers');
        Route::get('/offers/{provider_id}', 'getProviderOffers');
    });

    /*==============================================================================*/
    // Protected Routes (Auth Required)
    /*==============================================================================*/

    Route::middleware('auth:sanctum')->group(function () {
        // Client Profile Routes
        Route::controller(ClientProfileController::class)->group(function () {
            Route::get('/profile', 'getProfile');
            Route::put('/profile', 'update');
        });

        // Apply Promo Code (Protected - modifies data)
        Route::controller(ClientOffersController::class)->group(function () {
            Route::post('/promocode', 'applyPromoCode');
        });

         // Notifications Routes
        Route::controller(ClientNotificationsController::class)->group(function () {
            Route::get('/notifications','index'); // Get All Notifications
            Route::put('/notifications/{notificationId}/read','markAsRead'); // Update Notification
            Route::put('/notifications/isRead','markAllAsRead'); // Update Notification
            Route::post('/notifications', 'store');
        });

        // Orders Routes
        Route::controller(OrderController::class)->group(function () {
            Route::post('/orders', 'store');
            Route::put('/orders/{orderId}', 'update'); // Update Order
            Route::get('/orders', 'getByUser');
            Route::get('/orders/{orderId}', 'show');
            Route::put('/orders/{orderId}/cancel', 'cancelOrder');
        });

        // Ratings Routes
        Route::controller(RatingsController::class)->group(function () {
            Route::post('/rating', 'store');
        });

        // Favorites Routes
        Route::controller(FavoriteController::class)->group(function () {
            Route::get('/favorites', 'getFavorites');
            Route::post('/favorites/{provider_id}', 'addToFavorites');
            Route::delete('/favorites/{provider_id}', 'removeFromFavorites');
            Route::post('/favorites/check/{provider_id}', 'checkFavorite');
        });

        // Payment Cards
        Route::controller(PaymentCardsController::class)->group(function () {
            Route::get('/payment-cards', 'index'); // List all cards
            Route::post('/payment-cards', 'store'); // Create a card
            Route::get('/payment-cards/{id}', 'show'); // Show a specific card
            Route::put('/payment-cards/{id}','update'); // Update a card
            Route::delete('/payment-cards/{id}', 'destroy'); // Delete a card
        });

        // Delete Account Routes (Apple Guideline 5.1.1(v))
        Route::controller(DeleteAccountController::class)->group(function () {
            Route::delete('/delete-account', 'deleteAccount');
            Route::post('/delete-account', 'deleteAccount'); // Deprecated: kept for backward compatibility
        });
    });

    // Client Reset Password Routes
    Route::controller(ResetPasswordController::class)->group(function () {
        Route::post('/reset-password/otp', 'resetPasswordOtp'); // Send OTP to the user's phone
        Route::post('/reset-password/verify-otp', 'verifyResetPasswordOtp'); // Verify the OTP sent to the user's phone
        Route::put('/reset-password', 'resetPassword'); // Reset Password
    });
});

/*==================================================================================*/
//================================= Provider Routes =================================
/*==================================================================================*/

// Vendor Authentication Routes
Route::prefix('vendor')->group(function () {

        Route::controller(VendorAuthController::class)->group(function () {
            Route::post('/register','register');
            Route::post('/login',  'login');
        });

        Route::controller(ForgetPasswordController::class)->group(function () {
            Route::post('/reset-password/otp', 'resetPasswordOtp'); // Send OTP to the user's phone
            Route::post('/reset-password/verify-otp', 'verifyResetPasswordOtp'); // Verify the OTP sent to the user's phone
            Route::put('/reset-password', 'resetPassword'); // Reset Password
        });

    Route::middleware('auth:sanctum')->group(function () {
        // Vendor Profile Routes
        Route::controller(VendorAuthController::class)->group(function () {
            Route::get('/profile', 'getVendorProfile'); 
            Route::put('/profile/{id}', 'updateProfile');
            Route::post('/logout', 'logout')->middleware('auth:sanctum');
        });

        // Vendor Analytics Routes
        Route::controller(AnalyticsController::class)->group(function () {
            Route::get('/analytics/summary', 'summary');
            Route::get('/analytics/chart', 'chart');
            Route::get('/analytics/mostOrderedProducts', 'getMostOrderedProducts');
        });

         // Notifications Routes 
        Route::controller(NotificationsController::class)->group(function () {
            Route::get('/notifications','index'); // Get All Notifications
            Route::post('/notifications', 'store'); // Store New Notification
            Route::put('/notifications/{notificationId}/read','markAsRead'); // Update Notification
            Route::put('/notifications/isRead','markAllAsRead'); // Update Notification 
        });

        // Vendor Orders Routes
        Route::controller(VendorOrdersController::class)->group(function () {
            // Vendor Orders Routes
            Route::get('/orders', 'getVendorOrders');
            Route::get('/orders/{orderId}', 'showVendorOrder');
            Route::put('/orders/{orderId}/status', 'updateVendorOrderStatus');
        });

        // Vendor Menu Routes
        Route::controller(VendorMenuController::class)->group(function () {
            Route::post('/menus', 'store');
            Route::get('/menus','index');
            Route::get('/menus/{menuId}', 'show');
            Route::put('/menus/{menuId}', 'update');
            Route::delete('/menus/{menuId}', 'destroy');
        });

        // Vendor Menu Partitions Routes
        Route::controller(PartitionsController::class)->group(function () {
            Route::post('/menus/{menu_id}/partitions', 'createPartition');
            Route::get('/menus/{menuId}/partitions', 'getPartitions');
            Route::put('/menus/{menuId}/partitions/{partitionId}', 'updatePartition');
            Route::delete('/menus/{menuId}/partitions/{partitionId}', 'deletePartition');
        });

        // Vendor Products Routes
        Route::controller(ProductController::class)->group(function () {
            Route::post('/products', 'store');
            Route::delete('/products/{productId}', 'destroy');
        });

         // Seller Timetable Routes
        Route::controller(TimetablesController::class)->group(function () {
            Route::get('timetables', 'index'); // Show All Timetables
            Route::get('timetables/show/{id}', 'show'); // Show Timetable
            Route::post('timetables/store', 'store'); // Store New Timetable
            Route::put('timetables/update/{id}', 'update');// Update Timetable
            Route::delete('timetables/destroy/{id}', 'destroy');// Delete Timetable
        });

        // Vendor Offers Routes
        Route::controller(OffersController::class)->group(function () {
            Route::get('/offers', 'index');
            Route::post('/offers', 'store');
            Route::put('/offers/{offerId}', 'update');
            Route::delete('/offers/{offerId}', 'destroy');
        });
    });
});

/*==================================================================================*/

// File Upload Routes
Route::post('/upload/image/{id}', [ImageController::class, 'uploadImage']);
Route::post('/upload/{id}/image', [ImageController::class, 'uploadImage']);
Route::get('/images/{type}/{id}', [ImageController::class, 'getImagesByTypeAndId']);
Route::post('/upload/document', [ImageController::class, 'uploadPdf']);
Route::post('/upload/document/{id}', [ImageController::class, 'uploadPdf']);
Route::get('/documents/{type}/{id}', [ImageController::class, 'getPdfByTypeAndId']);



// Cities Routes
Route::get('/cities', [CitiesController::class, 'index']);

// Get All Products
Route::get('/products', [AllProductsController::class, 'index']);


// routes/api.php
Route::get('/payment/redirect', [PaymobCallbackController::class, 'handleGetRedirect']);

// Callback Routes
Route::match(['get', 'post'], '/payment/callback', [PaymobCallbackController::class, 'handleCallback']);