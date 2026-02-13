<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait ScopeByOrganization
{
    /**
     * Boot the trait.
     */
    protected static function bootScopeByOrganization()
    {
        // Add Global Scope to filter by organization_id
        static::addGlobalScope('organization', function (Builder $builder) {
            if (Auth::check()) {
                $model = $builder->getModel();
                $allowGlobal = method_exists($model, 'allowGlobalRecords') && $model->allowGlobalRecords();

                $builder->where(function ($query) use ($allowGlobal) {
                    $query->where($query->getModel()->qualifyColumn('organization_id'), Auth::user()->organization_id);

                    if ($allowGlobal) {
                        $query->orWhereNull($query->getModel()->qualifyColumn('organization_id'));
                    }
                });
            }
        });

        // Auto-assign organization_id when creating
        static::creating(function (Model $model) {
            if (Auth::check() && !$model->organization_id) {
                $model->organization_id = Auth::user()->organization_id;
            }
        });
    }
}
