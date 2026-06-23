<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = ['transfer_no', 'transfer_date', 'from_warehouse_id', 'to_warehouse_id', 'status', 'notes', 'created_by'];

    public function items()
    {
        return $this->hasMany(StockTransferItem::class, 'transfer_id');
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
}
