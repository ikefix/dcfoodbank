<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Product::with('category', 'shop')->get()->map(function($product){
            return [
                'Name' => $product->name,
                'Category' => $product->category->name ?? 'N/A',
                'Selling Price' => $product->price,
                'Cost Price' => $product->cost_price,
                'Stock Quantity' => $product->stock_quantity,
                'Stock Limit' => $product->stock_limit,
                'Shop' => $product->shop->name ?? 'N/A',
                'Barcode' => $product->barcode ?? 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Name',
            'Category',
            'Selling Price',
            'Cost Price',
            'Stock Quantity',
            'Stock Limit',
            'Shop',
            'Barcode'
        ];
    }
}
