<?php

use Illuminate\Support\Facades\Route;

// Redirect root to Vue app
Route::get('/', function () {
    return redirect()->route('vue.app');
});

// Vue.js app route
Route::get('/vue', function () {
    return view('vue-app');
})->name('vue.app');