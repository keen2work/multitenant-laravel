<?php

namespace EMedia\MultiTenant\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method bool multiTenancyIsActive() Is multi-tenancy active for this application?
 * @method bool setTenant($tenant) Set a tenant
 *
 * @method void setTenantById($tenantId) Set a tenant by ID
 * @method object getTenant() Get current tenant
 * @method bool isTenantSet() Is the tenant set?
 * @method bool isTenantNotSet() Is the tenant set?
 * @method void clearTenant() Clear the tenant from the session
 * @method void disable() Disable the tenant manager
 * @method void enable() Enable the tenant manager
 * @method \Illuminate\Support\Collection allTenants() Get all Tenants associated with the current User
 */
class TenantManager extends Facade
{
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'emedia.tenantManager';
	}
}
