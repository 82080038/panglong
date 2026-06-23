<?php
namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (session()->has('tenant_id')) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', session('tenant_id'));
            }
        });

        static::creating(function ($model) {
            if (session()->has('tenant_id') && empty($model->tenant_id)) {
                $model->tenant_id = session('tenant_id');
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
