<?php

namespace FlagPal\FlagPal;

/**
 * Holds one stable FlagPal instance per project for the lifetime of a
 * request, so that switching projects via FlagPal::asProject() — whether
 * from a Pennant driver or from application code directly — always
 * converges on the same instance instead of silently mutating shared state.
 */
class FlagPalProjectRegistry
{
    /** @var array<string, FlagPal> */
    private array $instances = [];

    public function remember(string $project, callable $factory): FlagPal
    {
        return $this->instances[$project] ??= $factory();
    }

    public function find(string $project): ?FlagPal
    {
        return $this->instances[$project] ?? null;
    }

    /** @return array<string, FlagPal> */
    public function all(): array
    {
        return $this->instances;
    }
}
