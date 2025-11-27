<?php

use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'dashboard');

Route::view('dashboard', 'dashboard')
    ->name('dashboard');

Route::redirect('settings', 'settings/profile');

Route::livewire('version-control', 'pages::version-control')->name('version-control');
Route::livewire('planning', 'pages::planning')->name('planning');
Route::livewire('deployment', 'pages::deployment')->name('deployment');

Route::get('settings/profile', Profile::class)->name('profile.edit');
