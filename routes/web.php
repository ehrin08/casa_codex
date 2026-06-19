<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Customer\AppointmentBookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Management\AppointmentController;
use App\Http\Controllers\Management\CustomerProfileController;
use App\Http\Controllers\Management\ServiceController;
use App\Http\Controllers\Management\TherapistAvailabilityController;
use App\Http\Controllers\Management\TherapistProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('management')->name('management.')->middleware('role:management')->group(function () {
        Route::view('/', 'management.index')->name('index');

        Route::get('appointments', [AppointmentController::class, 'index'])
            ->name('appointments.index');
        Route::get('appointments/{appointment}', [AppointmentController::class, 'show'])
            ->name('appointments.show');
        Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])
            ->name('appointments.update-status');

        Route::patch('services/{service}/toggle-status', [ServiceController::class, 'toggleStatus'])
            ->name('services.toggle-status');
        Route::resource('services', ServiceController::class)->except(['show', 'destroy']);

        Route::patch('therapists/{therapist}/toggle-status', [TherapistProfileController::class, 'toggleStatus'])
            ->name('therapists.toggle-status');
        Route::resource('therapists', TherapistProfileController::class)->except(['show', 'destroy']);

        Route::patch('customers/{customer}/toggle-status', [CustomerProfileController::class, 'toggleStatus'])
            ->name('customers.toggle-status');
        Route::resource('customers', CustomerProfileController::class)->except(['show', 'destroy']);

        Route::patch('availability/{availability}/toggle-status', [TherapistAvailabilityController::class, 'toggleStatus'])
            ->name('availability.toggle-status');
        Route::resource('availability', TherapistAvailabilityController::class)
            ->except(['show', 'destroy']);
    });
    Route::view('/therapist', 'therapist.index')
        ->middleware('role:therapist')
        ->name('therapist.index');
    Route::prefix('customer')->name('customer.')->middleware('role:customer')->group(function () {
        Route::view('/', 'customer.index')->name('index');
        Route::get('book-appointment', [AppointmentBookingController::class, 'create'])
            ->name('appointments.create');
        Route::post('book-appointment', [AppointmentBookingController::class, 'store'])
            ->name('appointments.store');
        Route::get('appointments/{appointment}', [AppointmentBookingController::class, 'show'])
            ->name('appointments.show');
    });
});
