<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\RequireAdmin;
use flipbox\keychain\controllers\UpsertController;
use Illuminate\Support\Facades\Route;

// Form actions, at `actions/keychain/…` (the Craft 5 action paths). Craft also registers them on the site's action
// trigger without authentication, so every route requires an admin here. POST and DELETE requests are CSRF-protected
// by Craft's web middleware.
Route::middleware(RequireAdmin::class)->group(function () {
    Route::post('upsert', [UpsertController::class, 'index']);
    Route::post('upsert/index', [UpsertController::class, 'index']);
    Route::post('upsert/openssl', [UpsertController::class, 'openssl']);
    Route::post('upsert/generate-openssl', [UpsertController::class, 'generateOpenssl']);
    Route::post('upsert/change-status', [UpsertController::class, 'changeStatus']);
    Route::match(['post', 'delete'], 'upsert/delete', [UpsertController::class, 'delete']);
});
