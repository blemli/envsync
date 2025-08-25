<?php

namespace Blemli\Envsync;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Blemli\Envsync\Commands\EnvsyncCommand;

class EnvsyncServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('envsync')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_envsync_table')
            ->hasCommand(EnvsyncCommand::class);
    }
}
