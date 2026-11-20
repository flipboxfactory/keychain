<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\RequireAdmin;
use flipbox\keychain\controllers\cp\view\EditController;
use flipbox\keychain\controllers\cp\view\GeneralController;
use Illuminate\Support\Facades\Route;

// Control panel screens. Craft adds the CP prefix, authentication and the accessCp check.
Route::middleware(RequireAdmin::class)->group(function () {
    Route::get('keychain', [GeneralController::class, 'index'])->name('keychain.index');
    Route::get('keychain/new', [EditController::class, 'index'])->name('keychain.new');
    Route::get('keychain/openssl', [EditController::class, 'openssl'])->name('keychain.openssl');
    Route::get('keychain/{keypairId}', [EditController::class, 'index'])->whereNumber('keypairId')->name('keychain.edit');
});
