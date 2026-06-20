<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Customer\AppointmentBookingController;
use App\Http\Controllers\Customer\AppointmentController as CustomerAppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Management\AppointmentController;
use App\Http\Controllers\Management\CustomerProfileController;
use App\Http\Controllers\Management\ServiceController;
use App\Http\Controllers\Management\TherapistAvailabilityController;
use App\Http\Controllers\Management\TherapistCommissionController;
use App\Http\Controllers\Management\TherapistProfileController;
use App\Http\Controllers\Management\TransactionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Therapist\CommissionController as TherapistCommissionViewController;
use App\Http\Controllers\Therapist\ScheduleController;
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
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.read');

    Route::prefix('management')->name('management.')->middleware('role:management')->group(function () {
        Route::view('/', 'management.index')->name('index');

        Route::get('appointments', [AppointmentController::class, 'index'])
            ->name('appointments.index');
        Route::get('appointments/{appointment}', [AppointmentController::class, 'show'])
            ->name('appointments.show');
        Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])
            ->name('appointments.update-status');

        Route::resource('transactions', TransactionController::class)
            ->only(['index', 'create', 'store', 'show']);

        Route::get('commissions', [TherapistCommissionController::class, 'index'])
            ->name('commissions.index');
        Route::get('commissions/{commission}', [TherapistCommissionController::class, 'show'])
            ->name('commissions.show');
        Route::patch('commissions/{commission}/mark-paid', [TherapistCommissionController::class, 'markPaid'])
            ->name('commissions.mark-paid');

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
    Route::prefix('therapist')->name('therapist.')->middleware('role:therapist')->group(function () {
        Route::view('/', 'therapist.index')->name('index');
        Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule.index');
        Route::get('appointments/{appointment}', [ScheduleController::class, 'show'])
            ->name('appointments.show');
        Route::get('commissions', [TherapistCommissionViewController::class, 'index'])
            ->name('commissions.index');
        Route::get('commissions/{commission}', [TherapistCommissionViewController::class, 'show'])
            ->name('commissions.show');
    });
    Route::prefix('customer')->name('customer.')->middleware('role:customer')->group(function () {
        Route::view('/', 'customer.index')->name('index');
        Route::get('book-appointment', [AppointmentBookingController::class, 'create'])
            ->name('appointments.create');
        Route::post('book-appointment', [AppointmentBookingController::class, 'store'])
            ->name('appointments.store');
        Route::get('appointments', [CustomerAppointmentController::class, 'index'])
            ->name('appointments.index');
        Route::get('appointments/{appointment}', [CustomerAppointmentController::class, 'show'])
            ->name('appointments.show');
    });
});
