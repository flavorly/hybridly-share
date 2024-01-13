<?php

namespace Flavorly\HybridlyShare;

use Closure;
use Hybridly\Hybridly;
use Hybridly\HybridlyServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\RedirectResponse;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class HybridlyShareServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('hybridly-share')
            ->publishesServiceProvider(HybridlyServiceProvider::class)
            ->hasConfigFile();
    }

    public function bootingPackage(): void
    {
        // Booting the package
    }

    public function registeringPackage(): void
    {
        // Register Singleton
        $this->app->singleton(HybridlyShare::class, fn ($app) => new HybridlyShare());

        // Append the Macro to forget specific Hybridly Shared Keys
        if (! Hybridly::hasMacro('append')) {
            Hybridly::macro('append', function (
                string|array|Arrayable $key,
                mixed $value = null
            ): Hybridly {
                // @phpstan-ignore-next-line
                $sharedValue = $this->shared($key, []);
                // We need to evaluate the close for resolving & merge the values
                if ($sharedValue instanceof Closure) {
                    $sharedValue = $sharedValue();
                }
                // @phpstan-ignore-next-line
                $this->share(
                    $key,
                    array_merge_recursive($sharedValue, [$value])
                );

                // @phpstan-ignore-next-line
                return $this;
            });
        }


        // Actual container Macro
        if (! Hybridly::hasMacro('container')) {
            Hybridly::macro('container', function (): HybridlyShare {
                return app(HybridlyShare::class);
            });
        }

        // Tweak the RedirectResponse to add the hybridly Flash
        RedirectResponse::macro('hybridly', function ($key, $value, bool $append = false): RedirectResponse {
            $key = is_array($key) ? $key : [$key => $value];
            foreach ($key as $k => $v) {
                $append ? hybridly()->append($k, $v) : hybridly()->share($k, $v);
            }

            /** @var RedirectResponse $this */
            return $this;
        });

        // Append Middleware
        $kernel = $this->app->make(Kernel::class);
        $kernel->appendMiddlewareToGroup('web', HybridlyContainerShareMiddleware::class);
        $kernel->appendToMiddlewarePriority(HybridlyContainerShareMiddleware::class);
    }
}
