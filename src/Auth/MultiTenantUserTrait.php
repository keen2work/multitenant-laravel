<?php

namespace EMedia\MultiTenant\Auth;

use App\Entities\Tenants\Tenant;
use App\Entities\TenantUsers\TenantUser;

trait MultiTenantUserTrait
{

	public function tenants()
	{
		 return $this->belongsToMany(config('auth.tenantModel'), 'tenant_users', 'user_id', 'tenant_id');
	}

	public function roles()
	{
		return $this->belongsToMany(config('auth.roleModel'), 'tenant_users');
	}

	public function tenantUser()
	{
		return $this->hasMany(TenantUser::class);
	}
}
