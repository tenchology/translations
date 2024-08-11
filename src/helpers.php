<?php


use Illuminate\Support\Facades\File;

if (! function_exists('LangJsonFileValues')) {
    function LangJsonFileValues($lang): \Illuminate\Support\Collection
    {
        if(File::exists(base_path('lang/'.$lang.'.json'))) {
            return collect(File::json(base_path('lang/'.$lang.'.json')));
        }
        return collect([]);

    }
}
