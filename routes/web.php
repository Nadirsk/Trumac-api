<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Override the Swagger docs route to ensure it works correctly
Route::get('/api/docs', function () {
    $filePath = storage_path('api-docs/api-docs.json');
    
    if (!File::exists($filePath)) {
        abort(404, 'Swagger documentation file not found');
    }
    
    $content = File::get($filePath);
    
    return response($content, 200)
        ->header('Content-Type', 'application/json')
        ->header('Access-Control-Allow-Origin', '*');
});

// Add the L5 Swagger docs route manually
Route::group(['prefix' => '', 'middleware' => [\L5Swagger\Http\Middleware\Config::class]], function () {
    Route::get('docs', [\L5Swagger\Http\Controllers\SwaggerController::class, 'docs'])
        ->name('l5-swagger.default.docs');
});
