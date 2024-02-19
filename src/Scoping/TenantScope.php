<?php

namespace EMedia\MultiTenant\Scoping;

use EMedia\MultiTenant\Facades\TenantManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{

	protected $model;

	public function apply(Builder $builder, Model $model)
	{
		if (TenantManager::isEnabled())
		{
			$tenant = TenantManager::getTenant();
			$builder->whereRaw($model->getTenantWhereClause($model->getTenantColumn(), $tenant->id));
		}
	}

	public function remove(Builder $builder, Model $model)
	{
		$tenant = TenantManager::getTenant();
		$query = $builder->getQuery();

		// TODO: the following code needs to be revised
		// see https://github.com/AuraEQ/laravel-multi-tenant/blob/master/src/AuraIsHere/LaravelMultiTenant/TenantScope.php
		// this code can't be executed because of the dependency on TenantManager
		foreach( (array) $query->wheres as $key => $where) {
			if($this->isTenantConstraint($model, $where, $model->getTenantColumn(), $tenant->id)) {
				unset($query->wheres[$key]);

				$query->wheres = array_values($query->wheres);
				break;
			}
		}
	}

	public function isTenantConstraint($model, array $where, $tenantColumn, $tenantId)
	{
		return $where['type'] == 'raw' && $where['sql'] == $model->getTenantWhereClause($tenantColumn, $tenantId);
	}

	/**
	 * Automatically add the tenant ID when creating a new model
	 *
	 * @param Model $model
	 */
	public function creating(Model $model)
	{
		// If the model has had the global scope removed, bail
		if (! $model->hasGlobalScope($this) ) {
			return;
		}

		$tenant = TenantManager::getTenant();

		// only override the column, if we don't have a specific Tenant ID given.
		if ($model->{$model->getTenantColumn()} === null) {
			$model->{$model->getTenantColumn()} = $tenant->id;
		}
	}

}