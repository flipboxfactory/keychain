<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use flipbox\keychain\records\KeyChainRecord;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `keychain` table on install. Upgrades from keychain 5.x already have it, and run the dated
 * migrations in this directory instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable(KeyChainRecord::TABLE)) {
            return;
        }

        Schema::create(KeyChainRecord::TABLE, function (Blueprint $table) {
            // INT, like Craft's tables and keychain tables upgraded from 5.x (MySQL foreign keys need matching types)
            $table->integer('id', true);
            $table->text('description')->nullable()->comment('User defined description so a human can understand what this is for.');
            $table->text('key');
            $table->text('certificate');
            $table->string('class');
            $table->string('pluginHandle');
            $table->text('settings')->nullable();
            $table->boolean('enabled')->default(true);
            $table->boolean('isEncrypted')->default(true);
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(KeyChainRecord::TABLE);
    }
};
