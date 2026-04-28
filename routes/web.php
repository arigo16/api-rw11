<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'API RW 11 is running',
        'version' => '1.0.0',
    ]);
});
