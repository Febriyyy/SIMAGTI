<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\CertificateVerificationController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\InternshipController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 (baca-saja)
|--------------------------------------------------------------------------
| Awalan /api diberikan otomatis oleh Laravel, jadi alamat lengkapnya /api/v1/...
| Memakai Action, Policy, dan scope yang sama dengan halaman web.
*/
Route::prefix('v1')->middleware(ForceJsonResponse::class)->group(function () {
    // Publik
    Route::post('/auth/token', [AuthTokenController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/certificates/{token}', CertificateVerificationController::class)->middleware('throttle:60,1');

    // Perlu token (Authorization: Bearer ...)
    Route::middleware(['auth:sanctum', EnsureActiveAccount::class])->group(function () {
        Route::delete('/auth/token', [AuthTokenController::class, 'destroy']);
        Route::get('/me', MeController::class);

        Route::get('/internships', [InternshipController::class, 'index']);
        Route::get('/internships/{internship}', [InternshipController::class, 'show'])->whereNumber('internship');
        Route::get('/internships/{internship}/tasks', [InternshipController::class, 'tasks'])->whereNumber('internship');

        Route::get('/companies', [CompanyController::class, 'index']);
        Route::get('/companies/{company}/stats', [CompanyController::class, 'stats'])->whereNumber('company');
    });
});
