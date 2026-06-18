<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/management', 'management.index')->name('management.index');
Route::view('/therapist', 'therapist.index')->name('therapist.index');
Route::view('/customer', 'customer.index')->name('customer.index');
