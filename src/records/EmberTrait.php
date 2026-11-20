<?php

declare(strict_types=1);

namespace flipbox\keychain\records;

/**
 * Enabled/disabled helpers for records with an `enabled` column.
 *
 * @property bool $enabled
 */
trait EmberTrait
{
    public function isEnabled(): bool
    {
        return (bool) $this->enabled;
    }

    public function isDisabled(): bool
    {
        return ! $this->isEnabled();
    }

    public function toEnabled(): static
    {
        $this->enabled = true;

        return $this;
    }

    public function toDisabled(): static
    {
        $this->enabled = false;

        return $this;
    }
}
