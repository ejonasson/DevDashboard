<?php

use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'dashboard');

Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

Route::livewire('version-control', 'pages::version-control')->name('version-control');
Route::livewire('planning', 'pages::planning')->name('planning');
Route::livewire('deployment', 'pages::deployment')->name('deployment');
Route::livewire('settings', 'pages::settings')->name('settings');
