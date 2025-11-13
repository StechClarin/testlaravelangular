<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==============================================================================
// 1. AUTHENTIFICATION (Magic Link)
// Section 1.1 du Test 
// ==============================================================================
Route::prefix('auth')->group(function () {
    // Endpoint pour demander le lien magique (envoi email via Queue)
    Route::post('/request-magic-link', [MagicLinkController::class, 'requestMagicLink']);
    
    // Endpoint pour vérifier le token et retourner le JWT
    Route::get('/verify-magic-link/{token}', [MagicLinkController::class, 'verifyMagicLink'])->name('magic.verify');
});

// ==============================================================================
// 2. ROUTES PROTÉGÉES (Nécessite un token valide)
// ==============================================================================
Route::middleware('auth:sanctum')->group(function () {


    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles.permissions');
    });

    
    Route::apiResource('tasks', TaskController::class);



    Route::middleware('permission:users.read')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
    });

    Route::middleware('permission:users.update')->put('/users/{user}', [UserController::class, 'update']);
    Route::middleware('permission:users.delete')->delete('/users/{user}', [UserController::class, 'destroy']);


    Route::post('/users/{user}/roles', [UserController::class, 'assignRoles'])
        ->middleware('permission:roles.manage');



    Route::middleware('permission:roles.manage')->group(function () {
        Route::apiResource('roles', RoleController::class);

        Route::post('/roles/{role}/permissions', [RoleController::class, 'assignPermissions']);
    });

});