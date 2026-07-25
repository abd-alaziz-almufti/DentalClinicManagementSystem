<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DentalChartController;
use App\Http\Controllers\Api\V1\InventoryItemController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\ServiceInventoryConsumptionController;
use App\Http\Controllers\Api\V1\VisitController;
use App\Http\Controllers\Api\V1\VisitServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/

// Public Authentication Route (Rate Limited)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Patients
    Route::get('/patients', [PatientController::class, 'index']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::get('/patients/{patient}', [PatientController::class, 'show']);

    // Appointments
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy']);

    // Check-In
    Route::post('/appointments/{appointment}/check-in', [VisitController::class, 'checkIn']);

    // Visits
    Route::get('/visits', [VisitController::class, 'index']);
    Route::get('/visits/{visit}', [VisitController::class, 'show']);

    // Visit Treatments (Services)
    Route::post('/visits/{visit}/services', [VisitServiceController::class, 'store']);
    Route::delete('/visits/{visit}/services/{visitService}', [VisitServiceController::class, 'destroy']);

    // Dental Chart
    Route::post('/visits/{visit}/teeth', [DentalChartController::class, 'store']);
    Route::delete('/visits/{visit}/teeth/{visitTooth}', [DentalChartController::class, 'destroy']);

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::post('/visits/{visit}/invoice', [InvoiceController::class, 'store']);
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);

    // Payments
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store']);

    // Inventory Items
    Route::get('/inventory/items', [InventoryItemController::class, 'index']);
    Route::get('/inventory/items/{inventoryItem}', [InventoryItemController::class, 'show']);

    // Purchases
    Route::get('/purchases', [PurchaseController::class, 'index']);
    Route::post('/purchases', [PurchaseController::class, 'store']);
    Route::get('/purchases/{purchase}', [PurchaseController::class, 'show']);
    Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive']);
    Route::delete('/purchases/{purchase}', [PurchaseController::class, 'destroy']);

    // Service Inventory Consumption Templates
    Route::get('/services/{service}/consumption', [ServiceInventoryConsumptionController::class, 'index']);
    Route::post('/services/{service}/consumption', [ServiceInventoryConsumptionController::class, 'store']);
    Route::delete('/services/{service}/consumption/{consumption}', [ServiceInventoryConsumptionController::class, 'destroy']);
});
