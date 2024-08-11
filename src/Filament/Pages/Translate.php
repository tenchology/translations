<?php

namespace Tenchology\Translate\Filament\Pages;

use Exception;
use Filament\Actions\Action;
use Filament\Actions\LocaleSwitcher;
use Filament\Actions\SelectAction;
use Filament\Forms\Components;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\NoReturn;
use Stichoza\GoogleTranslate\GoogleTranslate;

class Translate extends Page
{
    use interactsWithForms;

    public ?string $activeLocale = 'en';

    protected static ?string $navigationIcon = 'heroicon-o-language';

    protected static string $view = 'translates::filament.pages.translates';

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('Translates');
    }

    public function getHeading(): string|Htmlable
    {
        return __("Translates");
    }

    protected function getHeaderActions(): array
    {
        return [
            SelectAction::make('activeLocale')->options(config('app.locales'))->label(__('Language')),
            Action::make('auto_translate')->label('Auto Translate')->color('success')->icon('heroicon-o-language')->action('auto_translate'),
            Action::make('submit')->label(__('Save'))->color('success')->icon('heroicon-o-check')->action('submit'),
        ];
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function updatedInteractsWithForms(string $statePath): void
    {
        if($statePath == 'activeLocale') {
            $this->form->fill();
        }

    }


    public function form(Form $form): Form
    {
        $jsonString = [];
        if(File::exists(base_path('lang/'.$this->activeLocale.'.json'))){
            $jsonString = file_get_contents(base_path('lang/'.$this->activeLocale.'.json'));
            $jsonString = collect(json_decode($jsonString, true));
            $jsonString  = $jsonString->map(fn($value, $key) => TextInput::make($key)->default($value))
                ->flatten()
                ->toArray();
//            $jsonString = $jsonString->flatten()->toArray();
        }

        return $form
            ->schema([
                Section::make(__('Add Translation Keys') )
                    ->collapsed()
                    ->schema([
                        Components\Repeater::make('keys')
                            ->default([])
                            ->hiddenLabel()
                            ->columns(2)
                            ->schema([
                                TextInput::make('key')->label(__('Key')),
                                TextInput::make('value')->label(__('Value')),
                            ])
                    ])
                    ->columns(1),
                Section::make(__('Translations Keys (Language: :language)', ['language' => $this->activeLocale]) )
                    ->schema($jsonString ?? [])
                    ->columns(3),

            ])->statePath('data');
    }

    #[NoReturn]
    public function submit(): void
    {
        $keys_array = Arr::mapWithKeys($this->form->getState()['keys'], function (array $item, int $key) {
            return [trim($item['key']) => trim($item['value'] ?? trim($item['key']))];
        });
        $array_without_keys = Arr::except($this->form->getState(), ['keys']);

        $array_with_keys = array_merge($array_without_keys, $keys_array);
//        dd($array_with_keys);




        try {
            $newJsonString = json_encode($array_with_keys, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            if (File::exists(base_path('lang/'.$this->activeLocale.'.json'))){
                File::delete(base_path('lang/'.$this->activeLocale.'.json'));
            }
            file_put_contents(base_path('lang/'.$this->activeLocale.'.json'), stripslashes($newJsonString));
        }catch (Exception $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }

        Notification::make()->title(__('Translation keys saved'))->success()->send();
    }


    public function auto_translate(): void
    {

        // todo:: queue auto translate to chunks
        try {
//            set_time_limit(16000);
//            $translated = [];
//            collect(Arr::except($this->form->getState(), ['keys']))->chunk(20)->each(function ($chunk) use (&$translated){
//                $chunk->map(function ($value, $key) use (&$translated){
//                    $translated[$key] = (new GoogleTranslate)->setTarget($this->activeLocale)->translate($value);
//                    $this->form->fill($translated);
//                });
//            });
//
//            $values = Arr::except($this->form->getState(), ['keys']);

            collect(valuesofCurrentLangJsonFile())->chunk(20)->each(function ($chunk){
                dispatch(function () use ($chunk) {
                    $chunk->map(function ($value, $key){
                        $translated = (new GoogleTranslate)->setTarget($this->activeLocale)->translate($key);
                        $values = valuesofCurrentLangJsonFile();
                        $values[$key] = $translated;
                        File::put(base_path('lang/'.$this->activeLocale.'.json'), json_encode($values, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    });
                });
            });
        }catch (Exception $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }

        Notification::make()->title('keys will be translated in background')->success()->send();

    }




}
