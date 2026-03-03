<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PengurusController;
use App\Http\Controllers\RwInfoController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\BeritaController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\ExternalDbController;
use App\Http\Controllers\RtController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public routes (no auth required)
Route::prefix('public')->group(function () {
    // Pengurus
    Route::get('/pengurus', [PengurusController::class, 'index']);
    Route::get('/pengurus/by-bidang', [PengurusController::class, 'byBidang']);

    // RW Info
    Route::get('/rw-info', [RwInfoController::class, 'index']);
    Route::get('/rw-info/{key}', [RwInfoController::class, 'show']);

    // Gallery
    Route::get('/gallery', [GalleryController::class, 'index']);
    Route::get('/gallery/{gallery}', [GalleryController::class, 'show']);

    // Berita (published only)
    Route::get('/berita', [BeritaController::class, 'published']);
    Route::get('/berita/{slug}', [BeritaController::class, 'showBySlug']);

    // Dokumen (public only)
    Route::get('/dokumen', [DokumenController::class, 'publicDocs']);
});

// Protected routes (auth required)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Pengurus CRUD
    Route::apiResource('pengurus', PengurusController::class);
    Route::get('pengurus-by-bidang', [PengurusController::class, 'byBidang']);

    // RW Info CRUD
    Route::get('rw-info', [RwInfoController::class, 'index']);
    Route::get('rw-info/{key}', [RwInfoController::class, 'show']);
    Route::post('rw-info', [RwInfoController::class, 'store']);
    Route::put('rw-info/{key}', [RwInfoController::class, 'update']);
    Route::delete('rw-info/{key}', [RwInfoController::class, 'destroy']);
    Route::post('rw-info/bulk', [RwInfoController::class, 'bulkUpdate']);

    // Assets CRUD
    Route::apiResource('assets', AssetController::class);
    Route::get('assets-by-kategori', [AssetController::class, 'byKategori']);

    // Gallery CRUD
    Route::apiResource('gallery', GalleryController::class);
    Route::post('gallery/{gallery}/photos', [GalleryController::class, 'addPhoto']);
    Route::post('gallery/{gallery}/photos/bulk', [GalleryController::class, 'addPhotos']);
    Route::put('gallery-photos/{photo}', [GalleryController::class, 'updatePhoto']);
    Route::delete('gallery-photos/{photo}', [GalleryController::class, 'deletePhoto']);

    // Berita CRUD
    Route::apiResource('berita', BeritaController::class);
    Route::post('berita/{berita}/publish', [BeritaController::class, 'publish']);
    Route::post('berita/{berita}/unpublish', [BeritaController::class, 'unpublish']);

    // Dokumen CRUD
    Route::apiResource('dokumen', DokumenController::class);
    Route::post('dokumen/{dokumen}/toggle-public', [DokumenController::class, 'togglePublic']);

    // External Database - Dynamic table access (Query Builder)
    Route::prefix('external')->group(function () {
        Route::get('tables', [ExternalDbController::class, 'tables']);
        Route::get('structure/{table}', [ExternalDbController::class, 'structure']);
        Route::post('query', [ExternalDbController::class, 'query']);
        Route::get('{table}', [ExternalDbController::class, 'index']);
        Route::get('{table}/{id}', [ExternalDbController::class, 'show']);
        Route::post('{table}', [ExternalDbController::class, 'store']);
        Route::put('{table}/{id}', [ExternalDbController::class, 'update']);
        Route::delete('{table}/{id}', [ExternalDbController::class, 'destroy']);
    });

    // RT Database - Eloquent with relationships
    Route::prefix('rt/{rt}')->where(['rt' => '[0-9]+'])->group(function () {
        // Dashboard
        Route::get('dashboard', [RtController::class, 'dashboard']);

        // Houses
        Route::get('houses', [RtController::class, 'houses']);
        Route::get('houses/{id}', [RtController::class, 'house']);
        Route::get('houses/{id}/bills', [RtController::class, 'houseBills']);

        // Bills
        Route::get('bills/ipl', [RtController::class, 'billsIpl']);
        Route::get('bills/cash', [RtController::class, 'billsCash']);
        Route::get('bills/pkk', [RtController::class, 'billsPkk']);

        // Transactions & Balance
        Route::get('transactions', [RtController::class, 'transactions']);
        Route::get('balance', [RtController::class, 'balance']);

        // Votes
        Route::get('votes', [RtController::class, 'votes']);
        Route::get('votes/{id}', [RtController::class, 'vote']);

        // Information
        Route::get('information', [RtController::class, 'information']);

        // Contributions
        Route::get('contributions', [RtController::class, 'contributions']);

        // Complaints & Suggestions
        Route::get('complaints', [RtController::class, 'complaints']);
        Route::get('suggestions', [RtController::class, 'suggestions']);
    });
});
