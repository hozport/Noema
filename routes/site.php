<?php

use App\Http\Controllers\Site\SiteController;
use Illuminate\Support\Facades\Route;

Route::name('site.')->group(function () {
    Route::get('/', [SiteController::class, 'home'])->name('home');
    Route::get('/about', [SiteController::class, 'about'])->name('about');
    Route::get('/documentation', [SiteController::class, 'documentation'])->name('documentation');
    Route::get('/roadmap', [SiteController::class, 'roadmap'])->name('roadmap');
    Route::get('/privacy', [SiteController::class, 'privacy'])->name('privacy');
    Route::get('/consent', [SiteController::class, 'consent'])->name('consent');
    Route::get('/legal', [SiteController::class, 'legal'])->name('legal');
    Route::get('/register', [SiteController::class, 'register'])->name('register');
    Route::get('/tools/svg-viewer', [SiteController::class, 'svgViewer'])->name('svg-viewer');
    Route::get('/tools/colors', [SiteController::class, 'colorTool'])->name('color-tool');
});
