<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn (): HtmlString => new HtmlString('
                <p class="text-center text-sm text-gray-500 mt-4">
                    Don\'t have an account?
                    <a href="/register" class="text-primary-600 hover:underline font-medium">Create one</a>
                </p>
            '),
        );
    }
}
