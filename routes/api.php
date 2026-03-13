<?php

use App\Http\Controllers\Api\ExtensionController;
use App\Http\Middleware\ProfileTokenAuth;
use Illuminate\Support\Facades\Route;

// === PUBLIC AUTH endpoints (no token needed) ===
Route::prefix('extension/auth')->group(function () {
    Route::post('/login', [ExtensionController::class, 'login']);
    Route::post('/register-profile', [ExtensionController::class, 'registerProfile']);
});

// === PROTECTED endpoints (require valid API token) ===
Route::prefix('extension')
    ->middleware(ProfileTokenAuth::class)
    ->group(function () {
        Route::get('/profile', [ExtensionController::class, 'getProfile']);
        Route::post('/sync-profile', [ExtensionController::class, 'syncProfile']);
        Route::get('/campaigns', [ExtensionController::class, 'getCampaigns']);
        Route::post('/report', [ExtensionController::class, 'reportComment']);
        Route::post('/campaign-complete', [ExtensionController::class, 'campaignComplete']);
        Route::post('/heartbeat', [ExtensionController::class, 'heartbeat']);
        Route::post('/broadcast', [ExtensionController::class, 'broadcast']);
        Route::get('/settings', [ExtensionController::class, 'getSettings']);
        Route::post('/settings', [ExtensionController::class, 'updateSettings']);
        Route::get('/comment-templates', [ExtensionController::class, 'getCommentTemplates']);

        // Groups
        Route::get('/groups', [ExtensionController::class, 'getGroups']);
        Route::post('/groups/sync', [ExtensionController::class, 'syncGroups']);
        Route::delete('/groups/{groupId}', [ExtensionController::class, 'deleteGroup']);

        // Scheduled Posts
        Route::get('/scheduled-posts', [ExtensionController::class, 'getScheduledPosts']);
        Route::post('/scheduled-posts', [ExtensionController::class, 'createScheduledPost']);
        Route::put('/scheduled-posts/{id}/status', [ExtensionController::class, 'updateScheduledPostStatus']);
        Route::delete('/scheduled-posts/{id}', [ExtensionController::class, 'cancelScheduledPost']);
    });
