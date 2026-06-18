<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'dashboard')->name('home');
Route::view('/dashboard', 'dashboard')->name('dashboard');
Route::view('/accounts', 'dashboard');
Route::view('/categories', 'dashboard');
Route::view('/transactions', 'dashboard');
Route::view('/budgets', 'dashboard');
