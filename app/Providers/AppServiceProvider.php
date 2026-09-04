<?php

namespace App\Providers;

use App\Models\PurchaseOrder;
use App\Models\Requisition;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        // Morph map for polymorphic relationships
        Relation::morphMap([
            'requisition' => Requisition::class,
            'purchase_order' => PurchaseOrder::class,
        ]);
    }
}
