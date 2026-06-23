<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    protected $fillable = ['transfer_id', 'product_id', 'quantity', 'unit_id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
