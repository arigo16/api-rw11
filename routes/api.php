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
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ContributorController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public routes (no auth required)
Route::prefix('public')->group(function () {
    // Pengurus
    Route::get('/pengurus', [PengurusController::class, 'index']);
    Route::get('/pengurus/by-bidang', [PengurusController::class, 'byBidang']);

    // RW Infos
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

    // Assets (public read-only)
    Route::get('/assets', [AssetController::class, 'index']);
    Route::get('/assets/{asset}', [AssetController::class, 'show']);

    // Transactions (public read-only)
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'publicIndex']);
        Route::get('/summary', [TransactionController::class, 'summary']);
        Route::get('/balance', [TransactionController::class, 'publicBalance']);
    });

    // Transaction Types (public read-only)
    Route::get('/transaction-types', [TransactionController::class, 'types']);
});

// Protected routes (auth required)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Users CRUD
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

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

    // Transactions (RW Level)
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('/summary', [TransactionController::class, 'summary']);
        Route::get('/balance', [TransactionController::class, 'balance']);
        Route::post('/', [TransactionController::class, 'store']);
        Route::get('/{id}', [TransactionController::class, 'show']);
        Route::post('/{id}', [TransactionController::class, 'update']);
        Route::delete('/{id}', [TransactionController::class, 'destroy']);
    });

    // Transaction Types
    Route::prefix('transaction-types')->group(function () {
        Route::get('/', [TransactionController::class, 'types']);
        Route::post('/', [TransactionController::class, 'storeType']);
        Route::put('/{id}', [TransactionController::class, 'updateType']);
        Route::delete('/{id}', [TransactionController::class, 'destroyType']);
    });

    // Contributors (Iuran Rutin)
    Route::prefix('contributors')->group(function () {
        Route::get('/', [ContributorController::class, 'index']);
        Route::post('/', [ContributorController::class, 'store']);
        Route::get('/{id}', [ContributorController::class, 'show']);
        Route::put('/{id}', [ContributorController::class, 'update']);
        Route::delete('/{id}', [ContributorController::class, 'destroy']);
    });

    // Contributor Bills (Tagihan Iuran)
    Route::prefix('contributor-bills')->group(function () {
        Route::get('/', [ContributorController::class, 'bills']);
        Route::get('/summary', [ContributorController::class, 'billsSummary']);
        Route::post('/generate', [ContributorController::class, 'generateBills']);
        Route::get('/{id}', [ContributorController::class, 'showBill']);
        Route::post('/{id}/pay', [ContributorController::class, 'payBill']);
        Route::post('/{id}/unpay', [ContributorController::class, 'unpayBill']);
        Route::delete('/{id}', [ContributorController::class, 'destroyBill']);
    });

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
        Route::post('houses', [RtController::class, 'storeHouse']);
        Route::put('houses/{id}', [RtController::class, 'updateHouse']);
        Route::delete('houses/{id}', [RtController::class, 'destroyHouse']);

        // House Members
        Route::get('houses/{houseId}/members', [RtController::class, 'houseMembers']);
        Route::post('houses/{houseId}/members', [RtController::class, 'storeHouseMember']);
        Route::post('houses/{houseId}/members/copy-from', [RtController::class, 'copyToMember']);
        Route::get('houses/{houseId}/members/{memberId}', [RtController::class, 'houseMember']);
        Route::post('houses/{houseId}/members/{memberId}', [RtController::class, 'updateHouseMember']);
        Route::delete('houses/{houseId}/members/{memberId}', [RtController::class, 'destroyHouseMember']);

        // House Owner
        Route::get('houses/{houseId}/owner', [RtController::class, 'houseOwner']);
        Route::post('houses/{houseId}/owner', [RtController::class, 'storeHouseOwner']);
        Route::delete('houses/{houseId}/owner', [RtController::class, 'destroyHouseOwner']);

        // House Occupant
        Route::get('houses/{houseId}/occupant', [RtController::class, 'houseOccupant']);
        Route::post('houses/{houseId}/occupant', [RtController::class, 'storeHouseOccupant']);
        Route::delete('houses/{houseId}/occupant', [RtController::class, 'destroyHouseOccupant']);

        // Bills IPL
        Route::get('bills/ipl', [RtController::class, 'billsIpl']);
        Route::post('bills/ipl', [RtController::class, 'storeBillIpl']);
        Route::put('bills/ipl/{id}', [RtController::class, 'updateBillIpl']);
        Route::delete('bills/ipl/{id}', [RtController::class, 'destroyBillIpl']);

        // Bills Cash
        Route::get('bills/cash', [RtController::class, 'billsCash']);
        Route::post('bills/cash', [RtController::class, 'storeBillCash']);
        Route::put('bills/cash/{id}', [RtController::class, 'updateBillCash']);
        Route::delete('bills/cash/{id}', [RtController::class, 'destroyBillCash']);

        // Bills PKK
        Route::get('bills/pkk', [RtController::class, 'billsPkk']);
        Route::post('bills/pkk', [RtController::class, 'storeBillPkk']);
        Route::put('bills/pkk/{id}', [RtController::class, 'updateBillPkk']);
        Route::delete('bills/pkk/{id}', [RtController::class, 'destroyBillPkk']);

        // Bulk Update Bills by House
        Route::put('houses/{id}/bills/bulk', [RtController::class, 'bulkUpdateBills']);

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
