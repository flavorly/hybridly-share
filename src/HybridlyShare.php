<?php

namespace Flavorly\HybridlyShare;

use Closure;
use Flavorly\HybridlyShare\Drivers\AbstractDriver;
use Flavorly\HybridlyShare\Drivers\CacheDriver;
use Flavorly\HybridlyShare\Drivers\SessionDriver;
use Flavorly\HybridlyShare\Exceptions\DriverNotSupportedException;
use Flavorly\HybridlyShare\Exceptions\PrimaryKeyNotFoundException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Laravel\SerializableClosure\SerializableClosure;

class HybridlyShare
{
    use Macroable;

    protected Collection $container;

    protected ?AbstractDriver $driver = null;

    /**
     * @throws DriverNotSupportedException
     */
    public function __construct()
    {
        // Boot the driver
        $this->getDriver();
        // On build, we will pull from driver.
        $this->container = $this->getDriver()->get();
        // We need to Flush also
        $this->flushDriver();
    }

    /**
     * Shares the Value with Hybridly & Also stores it in the driver.
     *
     * @return $this
     *
     * @throws DriverNotSupportedException
     * @throws PhpVersionNotSupportedException
     */
    public function share(string $key, mixed $value, bool $append = false): static
    {
        // Ensure we serialize the value for sharing
        $value = $this->serializeValue($value);
        if ($append) {
            $value = array_merge_recursive($this->container->get($key, []), [$value]);
        }
        $this->container->put($key, $value);

        $this->shareToDriver();

        return $this;
    }

    /**
     * Alias to share function, but to append
     *
     * @return $this
     *
     * @throws DriverNotSupportedException
     * @throws PhpVersionNotSupportedException
     */
    public function append(string $key, mixed $value): static
    {
        return $this->share($key, $value, true);
    }

    /**
     * Share if condition is met
     *
     * @return $this
     *
     * @throws DriverNotSupportedException
     * @throws PhpVersionNotSupportedException
     */
    public function shareIf(bool $condition, string $key, mixed $value, bool $append = false): static
    {
        if ($condition) {
            return $this->share($key, $value, $append);
        }

        return $this;
    }

    /**
     * Share if condition is met
     *
     * @return $this
     *
     * @throws DriverNotSupportedException
     * @throws PhpVersionNotSupportedException
     */
    public function shareUnless(bool $condition, string $key, mixed $value, bool $append = false): static
    {
        return $this->shareIf(! $condition, $key, $value, $append);
    }

    /**
     * Forget the value from the container & driver
     *
     * @throws DriverNotSupportedException
     */
    public function forget(...$keys): static
    {
        $this->container->forget(...$keys);
        $this->shareToDriver();

        return $this;
    }

    /**
     * Flush the items from the driver
     * And also from hybridly
     *
     * @throws DriverNotSupportedException
     */
    public function flush(): static
    {
        $this->container = collect();
        $this->flushDriver();

        return $this;
    }

    /**
     * Flush the driver only
     *
     * @return $this
     *
     * @throws DriverNotSupportedException
     */
    protected function flushDriver(): static
    {
        $this->getDriver()->flush();

        return $this;
    }

    /**
     * Syncs to Hybridly Share
     *
     * @param  bool  $flush
     * @return HybridlyShare
     *
     * @throws DriverNotSupportedException
     */
    public function sync(bool $flush = true): static
    {
        if ($this->shouldIgnore()) {
            return $this;
        }

        // serialize/Unpack any pending Serialized Closures
        $this->unserializeContainerValues();

        // Persist the keys for emptiness
        $persistentKeys = config('hybridly-share.persistent-keys', []);
        if (! empty($persistentKeys)) {
            collect($persistentKeys)->each(fn ($value, $key) => hybridly()->share($key, $value));
        }

        // Share with hybridly
        $this->container->each(fn ($value, $key) => hybridly()->share($key, $value));

        // Flush on sharing
        if ($flush && config('hybridly-share.flush', true)) {
            $this->flushDriver();
            $this->container = collect();
        }

        return $this;
    }

    /**
     * Get the params being shared for the container
     *
     * @throws DriverNotSupportedException
     */
    public function shared(bool $flush = false): array
    {
        if ($this->shouldIgnore()) {
            return [];
        }

        $container = clone $this->container;
        // Flush on sharing
        if ($flush && config('hybridly-share.flush', true)) {
            $this->flushDriver();
            $this->container = collect();
        }

        return $container->toArray();
    }

    /**
     * Syncs to Hybridly Share & Also for the driver
     *
     * @return $this
     *
     * @throws DriverNotSupportedException
     */
    protected function shareToDriver(): static
    {
        // Need to pack/serialize to driver, because driver does not support closures
        // But it does take Laravel Serializable Closure
        $this->serializeContainerValues();

        // Then we are ready to put it in the driver
        $this->getDriver()->put($this->container);

        return $this;
    }

    /**
     * If it should be shared
     */
    public function shouldIgnore(?Request $request = null): bool
    {
        $request = $request ?? request();
        $ignoreUrls = collect(config('hybridly-share.ignore_urls', ['broadcasting/auth']));
        foreach ($ignoreUrls as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Attempt to Serialize closures
     *
     * @throws PhpVersionNotSupportedException
     */
    protected function serializeValue($value)
    {
        if ($value instanceof Closure) {
            return new SerializableClosure($value);
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {

                // Other edge cases can be added here
                if (! $item instanceof Closure) {
                    continue;
                }

                $value[$key] = $this->serializeValue($item);

            }
        }

        return $value;
    }

    /**
     * Attempts to resolve the value recursively.
     *
     * @throws PhpVersionNotSupportedException
     */
    protected function unserializeValue($value): mixed
    {
        if ($value instanceof SerializableClosure) {
            return $value->getClosure();
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->unserializeValue($item);
            }
        }

        return $value;
    }

    /**
     * Transforms the values, and attempts to resolve pending Serialized Closures
     */
    protected function unserializeContainerValues(): static
    {
        $this->container->transform(fn ($value) => $this->unserializeValue($value));

        return $this;
    }

    /**
     * Transforms the values, and attempts to resolve pending Serialized Closures
     */
    protected function serializeContainerValues(): static
    {
        $this->container->transform(fn ($value) => $this->serializeValue($value));

        return $this;
    }

    /**
     * Binds the Hybridly share to a specific user.
     *
     * @param  Authenticatable  $authenticatable
     * @return $this
     *
     * @throws DriverNotSupportedException
     * @throws PrimaryKeyNotFoundException
     */
    public function forUser(Authenticatable $authenticatable): self
    {
        if (! $this->driver instanceof CacheDriver) {
            throw new PrimaryKeyNotFoundException('You can only use the forUser method with a cache driver');
        }

        $this->getDriver()->setPrimaryKey($authenticatable->getKey());

        return $this;
    }

    /**
     * Get the driver instance.
     *
     * @throws DriverNotSupportedException
     */
    protected function getDriver(): AbstractDriver
    {
        if (null !== $this->driver) {
            return $this->driver;
        }

        $driver = config('hybridly-share.driver', 'session');
        if (! in_array($driver, ['session', 'cache'])) {
            throw new DriverNotSupportedException($driver);
        }

        $this->driver = match ($driver) {
            'session' => app(config('hybridly-share.session_driver', SessionDriver::class)),
            'cache' => app(config('hybridly-share.cache-driver', CacheDriver::class)),
            default => 'session',
        };

        return $this->driver;
    }
}
