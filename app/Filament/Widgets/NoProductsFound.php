<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class NoProductsFound extends Widget
{
    protected static string $view = 'filament.widgets.no-products-found';

    protected static ?int $sort = -10;

    public static function canView(): bool
    {
        return Product::published()->currentUser()->count() === 0;
    }

    protected static ?Collection $products = null;

    protected function getViewData(): array
    {
        return [
            'heading' => 'No products found',
            'description' => 'It looks like you don\'t have any published products yet. Get started by adding a new product.',
            'cta_url' => route('filament.admin.resources.products.create'),
            'cta_text' => 'Add a product',
        ];
    }
}
