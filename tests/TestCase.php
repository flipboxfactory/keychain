<?php

declare(strict_types=1);

namespace flipbox\keychain\tests;

use CraftCms\Cms\Plugin\Testing\PluginTestCase;
use CraftCms\Cms\User\Models\User;

abstract class TestCase extends PluginTestCase
{
    protected function admin(): User
    {
        return User::query()->where('admin', true)->firstOrFail();
    }
}
