<?php


use Illuminate\Support\Facades\File;

if (! function_exists('valuesofCurrentLangJsonFile')) {
    function valuesofCurrentLangJsonFile(): \Illuminate\Support\Collection
    {
        $values = File::get(base_path('lang/'.app()->currentLocale().'.json'));
        return collect(json_decode($values, true));
    }
}
