<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'category_id',
        'shop_id',
        'quantity',
        'total_price',
        'payment_method',
        'transaction_id',
        'discount_type',      // 🆕 Added
        'discount_value',     // 🆕 Added
        'discount',           // 🆕 Added
        'cashier_id', // ← add this
           // 🧍 Customer (optional)
        'customer_name',
        'customer_phone',
        
        // 🔥 THIS IS THE MISSING PIECE
        'sale_type',
    ];

    /**
     * Relationships
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

        public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }


    /**
     * Accessor: Calculate final price after discount
     */
    public function getFinalPriceAttribute()
    {
        return $this->total_price - $this->discount;
    }


    public static function calculateTotalSales($date = null, $search = null)
{
    $query = self::query();

    // Filter by date
    if ($date) {
        $query->whereDate('created_at', $date);
    }

    // Filter by search term
    if ($search) {
        $query->whereHas('product', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    }

    // Sum total sales after discount
    return $query->get()->sum(function ($sale) {
        $discounted = $sale->total_price - ($sale->discount ?? 0);
        return max($discounted, 0); // avoid negative values
    });
}

}
