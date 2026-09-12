<?php

use App\Http\Controllers\Api\AppSettingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\MerchantAddressController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\StaffController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);

// App Settings & Information Endpoints
Route::get('app-settings', [AppSettingController::class, 'index']);
Route::post('app-settings', [AppSettingController::class, 'update']);
Route::put('app-settings', [AppSettingController::class, 'update']);

// Merchant Authentication & Custom Endpoints
Route::post('merchants/login', [MerchantController::class, 'login']);
Route::post('upload-image', [MerchantController::class, 'uploadImage']);
Route::put('merchants/{id}/status', [MerchantController::class, 'updateStatus']);
Route::put('merchants/{id}/fcm-token', [MerchantController::class, 'updateFcmToken']);

// Driver Authentication & Custom Endpoints
Route::post('drivers/login', [DriverController::class, 'login']);

// Merchant Addresses Endpoints
Route::get('merchants/{merchantId}/addresses', [MerchantAddressController::class, 'index']);
Route::post('merchants/{merchantId}/addresses', [MerchantAddressController::class, 'store']);
Route::put('merchants/{merchantId}/addresses/{addressId}/default', [MerchantAddressController::class, 'setDefault']);
Route::delete('merchants/{merchantId}/addresses/{addressId}', [MerchantAddressController::class, 'destroy']);

// Orders System Endpoints
Route::post('orders', [OrderController::class, 'store']);
Route::get('orders/{id}', [OrderController::class, 'show']);
Route::put('orders/{id}/status', [OrderController::class, 'updateStatus']);
Route::get('merchants/{merchantId}/orders', [OrderController::class, 'merchantOrders']);
Route::get('admin/orders', [OrderController::class, 'adminOrders']);

Route::apiResource('staff', StaffController::class);
Route::apiResource('merchants', MerchantController::class);
Route::apiResource('drivers', DriverController::class);

