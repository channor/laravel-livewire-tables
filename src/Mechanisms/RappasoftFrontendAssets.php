<?php

namespace Rappasoft\LaravelLivewireTables\Mechanisms;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Drawer\Utils;

class RappasoftFrontendAssets
{
    public bool $hasRenderedRappsoftScripts = false;

    public bool $hasRenderedRappsoftStyles = false;

    public mixed $rappasoftScriptRoute;

    public mixed $rappasoftStylesRoute;

    public array $rappasoftScriptTagAttributes = [];

    public array $rappasoftStyleTagAttributes = [];

    public function register(): void
    {
        app()->singleton($this::class);
    }

    public function boot(): void
    {
        app($this::class)->setRappaScriptRoute(function ($handle) {
            $scriptPath = '/livewire/rappasoft-laravel-livewire-tables.js';

            return Route::get($scriptPath, $handle);
        });

        app($this::class)->setRappaStylesRoute(function ($handle) {
            $stylesPath = '/livewire/rappasoft-laravel-livewire-tables.css';

            return Route::get($stylesPath, $handle);
        });

        Blade::directive('rappasoftScripts', [static::class, 'rappasoftScripts']);
        Blade::directive('rappasoftStyles', [static::class, 'rappasoftStyles']);
    }

    public function useRappasoftScriptTagAttributes(array $attributes): void
    {
        $this->rappasoftScriptTagAttributes = [...$this->rappasoftScriptTagAttributes, ...$attributes];
    }

    public function setRappaScriptRoute(callable $callback): void
    {
        $route = $callback([self::class, 'returnJavaScriptAsFile']);

        $this->rappasoftScriptRoute = $route;
    }

    public function setRappaStylesRoute(callable $callback): void
    {
        $route = $callback([self::class, 'returnStylesAsFile']);

        $this->rappasoftStylesRoute = $route;
    }

    public static function rappasoftScripts(mixed $expression): string
    {
        return '{!! \Rappasoft\LaravelLivewireTables\Mechanisms\RappasoftFrontendAssets::scripts('.$expression.') !!}';
    }

    public static function rappasoftStyles(mixed $expression): string
    {
        return '{!! \Rappasoft\LaravelLivewireTables\Mechanisms\RappasoftFrontendAssets::styles('.$expression.') !!}';
    }

    public function returnJavaScriptAsFile(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->pretendResponseIsJs(__DIR__.'/../../resources/js/laravel-livewire-tables.js');
    }

    public function returnStylesAsFile(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->pretendResponseIsCSS(__DIR__.'/../../resources/css/laravel-livewire-tables.css');
    }

    protected function pretendResponseIsCSS(string $file): \Symfony\Component\HttpFoundation\Response
    {
        $expires = strtotime('+1 minute');
        $lastModified = filemtime($file);
        $cacheControl = 'public, max-age=60';

        $headers = [
            'Content-Type' => 'text/css; charset=utf-8',
            'Expires' => Utils::httpDate($expires),
            'Cache-Control' => $cacheControl,
            'Last-Modified' => Utils::httpDate($lastModified),
        ];

        return response()->file($file, $headers);
    }

    protected function pretendResponseIsJs(string $file): \Symfony\Component\HttpFoundation\Response
    {
        $expires = strtotime('+1 minute');
        $lastModified = filemtime($file);
        $cacheControl = 'public, max-age=30';

        $headers = [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Expires' => Utils::httpDate($expires),
            'Cache-Control' => $cacheControl,
            'Last-Modified' => Utils::httpDate($lastModified),
        ];

        return response()->file($file, $headers);
    }

    public function maps(): \Symfony\Component\HttpFoundation\Response
    {
        return Utils::pretendResponseIsFile(__DIR__.'/../../../resources/js/laravel-livewire-tables.min.js.map');
    }

    public static function styles(array $options = []): array|string|null
    {
        app(static::class)->hasRenderedRappsoftStyles = true;

        $debug = config('app.debug');

        $styles = static::css($options);

        // HTML Label.
        $html = $debug ? ['<!-- Rappasoft Styles -->'] : [];

        $html[] = $styles;

        return implode("\n", $html);

    }

    public static function scripts(array $options = []): ?string
    {
        app(static::class)->hasRenderedRappsoftScripts = true;

        $debug = config('app.debug');

        $scripts = static::js($options);

        // HTML Label.
        $html = $debug ? ['<!-- Rappasoft Scripts -->'] : [];

        $html[] = $scripts;

        return implode("\n", $html);
    }

    public static function css(array $options= []): ?string
    {
        $styleUrl = app(static::class)->rappasoftStylesRoute->uri;
        $styleUrl = rtrim($styleUrl, '/');

        $styleUrl = (string) str($styleUrl)->start('/');

        return <<<HTML
            <link href="{$styleUrl}" rel="stylesheet" />
        HTML;
    }

    public static function js(array $options= []): string
    {
        // Use the default endpoint...
        $url = app(static::class)->rappasoftScriptRoute->uri;

        $url = rtrim($url, '/');

        $url = (string) str($url)->start('/');

        // Add the build manifest hash to it...

        $nonce = isset($options['nonce']) ? "nonce=\"{$options['nonce']}\"" : '';

        $extraAttributes = Utils::stringifyHtmlAttributes(
            app(static::class)->rappasoftScriptTagAttributes,
        );

        return <<<HTML
        <script  src="{$url}"  {$nonce} {$extraAttributes}></script>
        HTML;
    }

    protected static function minify(string $subject): array|string|null
    {
        return preg_replace('~(\v|\t|\s{2,})~m', '', $subject);
    }
}
