<?php

namespace Tenchology\Translate\Console\Commands;

use Illuminate\Console\Command;

class TranslateJsonFileCommand extends Command
{
    protected $signature = 'translate:json-file';

    protected $description = 'Searches for translation keys – inserts into JSON translation files.';

    protected array $directories;
    protected array $filesExtensions = ['php'];

    public function __construct()
    {
        parent::__construct();

        $this->directories = [
            app_path(),
            base_path('packages'),
            resource_path('views'),
        ];
    }


    public function handle(): void
    {
        $translationKeys  = $this->findProjectTranslationsKeys();
        $translationFiles = $this->getProjectTranslationFiles();

        foreach ($translationFiles as $file) {
            $translationData = $this->getAlreadyTranslatedKeys($file);
            $added = [];

            $this->line('Language: ' . str_replace('.json', '', basename($file)));

            foreach ($translationKeys as $key) {
                if (!isset($translationData[$key])) {
                    $translationData[$key] = '';
                    $added[]               = $key;

                    $this->warn(" - Added: {$key}");
                }
            }

            if ($added) {
                $this->line('Updating translation file...');

                $this->writeNewTranslationFile($file, $translationData);

                $this->info('Translation file have been updated!');
            } else {
                $this->warn('Nothing new found for this language.');
            }

            $this->line('');
        }
    }

    /**
     * @return array
     */
    private function findProjectTranslationsKeys(): array
    {
        $allKeys = [];

        foreach ($this->directories as $directory) {
            foreach ($this->filesExtensions as $extension) {
                $this->getTranslationKeysFromDir($allKeys, $directory, $extension);
            }
        }

        ksort($allKeys);
        return $allKeys;
    }

    /**
     * @param array $keys
     * @param string $dirPath
     * @param string $fileExt
     */
    private function getTranslationKeysFromDir(array &$keys, string $dirPath, string $fileExt = 'php'): void
    {
        $files = $this->findFiles("{$dirPath}/*.{$fileExt}", GLOB_BRACE);

        foreach ($files as $file) {
            $content = $this->getSanitizedContent($file);

            foreach (['lang', '__'] as $translationMethod) {
                $this->getTranslationKeysFromFunction($keys, $translationMethod, $content);
            }
        }
    }

    /**
     * @param array $keys
     * @param string $functionName
     * @param string $content
     */
    private function getTranslationKeysFromFunction(array &$keys, string $functionName, string $content): void
    {
        $matches = [];

        preg_match_all("#{$functionName}\('(.*?)'\)#", $content, $matches);

        if (! empty($matches)) {
            foreach ($matches[1] as $match) {
                $match = str_replace('"', "'", str_replace("\'", "'", $match));

                if (! empty($match)) {
                    $keys[$match] = $match;
                }
            }
        }
    }

    private function findFiles($pattern, $flags = 0): bool|array
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->findFiles($dir . '/' . basename($pattern), $flags));
        }

        return $files;
    }

    /**
     * @return array
     */
    private function getProjectTranslationFiles(): array
    {
        $path  = base_path('lang');
        return glob("{$path}/*.json", GLOB_BRACE);
    }

    /**
     * @param string $filePath
     * @return array
     */
    private function getAlreadyTranslatedKeys($filePath): array
    {
        $current = json_decode(file_get_contents($filePath), true);

        ksort($current);

        return $current;
    }

    /**
     * @param string $filePath
     * @param array $translations
     */
    private function writeNewTranslationFile(string $filePath, array $translations): void
    {
        file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param string $filePath
     * @return string
     */
    private function getSanitizedContent(string $filePath): string
    {
        return str_replace("\n", ' ', file_get_contents($filePath));
    }
}
