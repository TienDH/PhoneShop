<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Front\HomeController as FrontHomeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Front\ProfileController;
use App\Http\Controllers\Front\OrderController as CustomerOrderController;
use App\Http\Controllers\Front\ReviewController;
use App\Http\Controllers\Front\MomoController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// TRANG CHỦ
Route::get('/', [FrontHomeController::class, 'index'])->name('front.home');

// ĐĂNG NHẬP - ĐĂNG KÝ - XÁC MINH EMAIL
// ĐĂNG NHẬP - ĐĂNG KÝ - XÁC MINH EMAIL
Auth::routes();

// Product detail route
use App\Http\Controllers\Front\ProductController as FrontProductController;
Route::get('/products', [FrontProductController::class, 'index'])->name('products.index');
Route::get('/product/{slug}', [FrontProductController::class, 'show'])
    ->name('product.show');

// GIỎ HÀNG
use App\Http\Controllers\Front\CartController;
Route::get('/cart',              [CartController::class, 'index']) ->name('cart.index');
Route::post('/cart/add',         [CartController::class, 'add'])   ->name('cart.add');
Route::post('/cart/update',      [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove',      [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear',       [CartController::class, 'clear']) ->name('cart.clear');

// THANH TOÁN
use App\Http\Controllers\Front\CheckoutController;
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/success/{orderCode}', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [CustomerOrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/pay/momo', [MomoController::class, 'start'])->name('orders.momo.pay');
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
});
Route::middleware('auth')->group(function () {
    Route::get('/account/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/account/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/account/password', [ProfileController::class, 'password'])->name('profile.password');
});
Route::get('/payment/momo/callback', [MomoController::class, 'callback'])->name('payment.momo.callback');
Route::post('/payment/momo/ipn', [MomoController::class, 'ipn'])->name('payment.momo.ipn');

// GHN - ĐỊA CHỈ & PHÍ VẬN CHUYỂN
use App\Http\Controllers\Front\LocationController;
Route::prefix('locations')->name('locations.')->group(function () {
    Route::get('provinces',               [LocationController::class, 'provinces'])    ->name('provinces');
    Route::get('districts/{provinceId}',  [LocationController::class, 'districts'])    ->name('districts');
    Route::get('wards/{districtId}',      [LocationController::class, 'wards'])        ->name('wards');
    Route::post('calculate-fee',          [LocationController::class, 'calculateFee']) ->name('fee');
});


// Email verification routes (guest accessible)
Route::get('email/verify', [App\Http\Controllers\Auth\VerificationController::class, 'show'])
    ->name('verification.notice');

Route::get('email/verify/{id}/{hash}', [App\Http\Controllers\Auth\VerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('email/resend', [App\Http\Controllers\Auth\VerificationController::class, 'resend'])
    ->name('verification.resend')
    ->middleware(['throttle:6,1']);

// TRANG THÔNG BÁO XÁC MINH THÀNH CÔNG
Route::get('/email/verified', function () {
    return view('auth.verification-success');
})->name('verification.success');

// HOME SAU KHI ĐĂNG NHẬP
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])
    ->middleware('auth')
    ->name('home');

// ADMIN
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::resource('categories', CategoryController::class);
        Route::resource('products', ProductController::class);
        Route::resource('products.variants', ProductVariantController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->scoped();
        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::put('orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
        Route::post('orders/{order}/payment', [AdminOrderController::class, 'payment'])->name('orders.payment');
        Route::post('orders/{order}/shipment', [AdminOrderController::class, 'shipment'])->name('orders.shipment');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    });
