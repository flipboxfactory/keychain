<?php

declare(strict_types=1);

namespace flipbox\keychain\tests;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Plugin\Testing\PluginTestCase;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Override;

abstract class TestCase extends PluginTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Use Craft's model factories, as core's tests do
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CraftCms\\Cms\\Database\\Factories\\'.class_basename($modelName).'Factory',
        );
    }

    protected function admin(): User
    {
        return User::query()->where('admin', true)->firstOrFail();
    }

    /**
     * A user who can access the control panel but isn't an admin.
     */
    protected function cpUser(): User
    {
        // Solo only allows one user
        Edition::set(Edition::Pro);

        return User::factory()->withPermissions(['accessCp'])->create(['admin' => false]);
    }

    /**
     * Laravel skips CSRF checks in unit tests; this keeps them on.
     */
    protected function enforceCsrf(): void
    {
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }
}
