<?php

namespace Flavorly\HybridlyShare\Drivers;

use Flavorly\HybridlyShare\Exceptions\PrimaryKeyNotFoundException;
use Illuminate\Support\Collection;

abstract class AbstractDriver
{
    protected ?string $primaryKey = null;

    /**
     * Get the data on the driver
     */
    abstract public function get(): Collection;

    /**
     * Put the data into the driver
     */
    abstract public function put(Collection $container): void;

    /**
     * Flush the data available on the driver
     */
    abstract public function flush(): void;

    /**
     * Set the Primary Key
     *
     * @return $this
     */
    public function setPrimaryKey(string $key): static
    {
        $this->primaryKey = $key;

        return $this;
    }

    /**
     * Gets & Generates the primary key
     *
     * @throws PrimaryKeyNotFoundException
     */
    protected function key(): string
    {
        if (null === $this->primaryKey) {
            throw new PrimaryKeyNotFoundException();
        }

        return implode(
            '_',
            [
                config('hybridly-share.prefix_key', 'inertia_container_'),
                $this->primaryKey,
            ]
        );
    }
}
