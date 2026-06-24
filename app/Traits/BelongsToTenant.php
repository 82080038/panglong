<?php
namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = static::currentTenantId();
            if ($tenantId !== null) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            $tenantId = static::currentTenantId();
            if ($tenantId !== null && empty($model->tenant_id)) {
                $model->tenant_id = $tenantId;
            }
        });
    }

    protected static function currentTenantId()
    {
        return app()->bound('currentTenantId') ? app('currentTenantId') : null;
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
