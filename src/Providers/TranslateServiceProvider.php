<?php

namespace Tenchology\Translate\Providers;

use Filament\Panel;
use Illuminate\Support\ServiceProvider;
use Tenchology\Translate\Console\Commands\TranslateJsonFileCommand;
use Tenchology\Translate\Console\Commands\TranslateLabelsCommand;
use Tenchology\Translate\TranslatePlugin;

class TranslateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(fn (Panel $panel) => ($panel->getId() !== 'admin') || $panel->plugin(new TranslatePlugin()));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                TranslateJsonFileCommand::class,
                TranslateLabelsCommand::class
            ]);
        }
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'translates');
    }
}
