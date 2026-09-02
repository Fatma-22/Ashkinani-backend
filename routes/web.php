<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index']);

// SEO-friendly player profile routes
Route::get('/share/player/{id}', [App\Http\Controllers\Api\V1\PlayerController::class, 'shareProfile'])->where('id', '.*');
Route::get('/share/sponsor/{id}', [App\Http\Controllers\Api\V1\SponsorController::class, 'shareSponsor'])->where('id', '.*');
Route::get('/players/{id}', [App\Http\Controllers\Api\V1\PlayerController::class, 'shareProfile'])->where('id', '.*');
Route::get('/cv/{id}', [App\Http\Controllers\Api\V1\PlayerController::class, 'shareProfile'])->where('id', '.*');

// Catch-all for React SPA (excluding api and static files)
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api|storage|images|logo\.png).*$');
