<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionInvoice extends Model
{
    protected $fillable = ['invoice_no', 'tenant_id', 'subscription_id', 'invoice_date',
        'due_date', 'amount', 'status', 'paid_at', 'payment_method', 'notes'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
