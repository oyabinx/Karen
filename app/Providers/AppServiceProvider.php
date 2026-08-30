<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Master draft dokumen (bend26, draft nota, kartu inventaris)
        // dirender dari resources/draft_documents/ — docs/structure.md
        View::addNamespace('drafts', resource_path('draft_documents'));
    }
}
