<?php

namespace Tenchology\Translate\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Stichoza\GoogleTranslate\GoogleTranslate;

class TranslateChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chunk;
    protected $activeLocale;

    public function __construct($chunk, $activeLocale)
    {
        $this->chunk = $chunk;
        $this->activeLocale = $activeLocale;
    }

    public function handle()
    {
        $this->chunk->map(function ($value, $key) {
            $translated = (new GoogleTranslate)->setTarget($this->activeLocale)->translate($key);
            $values = LangJsonFileValues($this->activeLocale);
            $values[$key] = $translated;
            File::put(base_path('lang/'.$this->activeLocale.'.json'), json_encode($values, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        });
    }
}
