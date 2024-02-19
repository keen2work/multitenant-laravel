<?php

namespace EMedia\MultiTenant\Scoping\Traits;

use Illuminate\Database\Eloquent\Model;
use EMedia\MultiTenant\Scoping\TenantScope;

trait TenantScopedModelTrait
{
	
	public function tenant()
	{
		return $this->belongsTo(config('auth.tenantModel'), $this->getTenantColumn());
	}

	public static function bootTenantScopedModelTrait()
	{
		$tenantScope = app('EMedia\MultiTenant\Scoping\TenantScope');

		// Add Global scope that will handle all operations except create()
		static::addGlobalScope($tenantScope);

		// Add an observer that will automatically add the tenant id when create()-ing
		static::creating(function (Model $model) use ($tenantScope) {
			$tenantScope->creating($model);
		});
	}

	/**
	 * Returns a new builder without the tenant scope applied.
	 *
	 *     $allUsers = User::allTenants()->get();
	 *
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public static function allTenants()
	{
		return with(new static())->newQueryWithoutScope(new TenantScope());
	}

	/**
	 * Foreign key of the model for Tenant
	 * Set the $tenant_column property in the model, otherwise a default will be returned
	 *
	 * @return string
	 */
	public function getTenantColumn()
	{
		if (isset($this->tenant_column)) return $this->tenant_column;

		return 'tenant_id';
	}

	public function getTenantWhereClause($tenantColumn, $tenantId)
	{
		return "{$this->getTable()}.{$tenantColumn} = '{$tenantId}'";
	}

}