<?php

namespace Tenchology\Translate;

use Filament\Panel;
use Filament\Contracts\Plugin;
use Tenchology\Translate\Filament\Pages\Translate;

class TranslatePlugin implements Plugin
{
    public function getId(): string
    {
        return 'translate';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([Translate::class]);
    }

    public function boot(Panel $panel): void {}
}
