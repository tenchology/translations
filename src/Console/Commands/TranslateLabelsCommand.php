<?php

namespace Tenchology\Translate\Console\Commands;

use Illuminate\Console\Command;

class TranslateLabelsCommand extends Command
{
    protected $signature = 'translate:labels';

    protected $description = 'Command description';

    public function handle(): void
    {
        $this->info('Translating labels...');

        // Search for ->label('Label') in all FilamentResources files using regex pattern ->label\('([^']+)'\)


        // Replace ->label\('([^']+)'\) with ->label\(__\('$1'\)\)
        // (->label('Label') to ->label(__('Label')))

        $this->info('Done!');
    }
}
