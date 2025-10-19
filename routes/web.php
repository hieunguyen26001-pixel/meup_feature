<?php

use Illuminate\Support\Facades\Route;

// Redirect root to admin dashboard
Route::get('/', function () {
    return redirect('/admin/dashboard');
});

// Vue.js app route
Route::get('/vue', function () {
    return view('vue-app');
})->name('vue.app');


// API Explorer page
Route::get('/api-explorer', function () {
    return view('vue-app');
})->name('api.explorer');

// Catch all Vue.js routes
Route::get('/{any}', function () {
    return view('vue-app');
})->where('any', '.*');