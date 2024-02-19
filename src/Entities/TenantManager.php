<?php

namespace EMedia\MultiTenant\Entities;

use App\Entities\Tenants\TenantsRepository;
use EMedia\MultiTenant\Exceptions\MultiTenancyNotActiveException;
use EMedia\MultiTenant\Exceptions\TenantInvalidIdException;
use EMedia\MultiTenant\Exceptions\TenantNotBoundException;
use EMedia\MultiTenant\Exceptions\TenantNotSetException;
use EMedia\MultiTenant\Exceptions\UserDoesNotBelongToTenantException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\UnauthorizedException;
use ReflectionException;

class TenantManager
{

	private $tenant;
	private $enabled;
	private $active;

	public function __construct()
	{
		$this->enable();
		$this->active = config('auth.multiTenantActive');
	}

	/**
	 * Check if multi-tenancy is activated by user
	 *
	 * @throws MultiTenancyNotActiveException
	 */
	private function checkForActivation()
	{
		if (!$this->active) throw new MultiTenancyNotActiveException();
	}

	/**
	 * Set the Tenant for this Session with a valid Tenant ID
	 *
	 * @param $id
	 * @throws TenantInvalidIdException
	 * @throws TenantNotBoundException
	 */
	public function setTenantById($id)
	{
		$this->checkForActivation();
		try
		{
			$tenantResolver = app('emedia.tenantManager.tenant');
		}
		catch (ReflectionException $ex)
		{
			throw new TenantNotBoundException();
		}

		$tenant = $tenantResolver::find($id);
		if (empty($tenant) || empty($tenant->id))
			throw new TenantInvalidIdException();

		$this->setTenant($tenant);
	}

	/**
	 * Set the Tenant for this Session
	 *
	 * @param Model $tenant
	 */
	public function setTenant(Model $tenant)
	{
		$this->checkForActivation();
		$this->tenant = $tenant;
		Session::put('tenant_id', $tenant->id);
	}

	/**
	 * Get the current Tenant for this Session
	 *
	 * @return mixed
	 * @throws TenantInvalidIdException
	 * @throws TenantNotBoundException
	 * @throws TenantNotSetException
	 */
	public function getTenant()
	{
		if ( ! $this->isTenantSet() ) throw new TenantNotSetException();

		// if this is a new request, load session from storage
		if (!$this->tenantLoaded()) {
			$sessionTenantId = Session::get('tenant_id');
			$this->setTenantById($sessionTenantId);
		}

		return $this->tenant;
	}

	public function isTenantSet()
	{
		if (Session::has('tenant_id')) return true;

		return $this->tenantLoaded();
	}

	public function isTenantNotSet()
	{
		return !$this->isTenantSet();
	}

	protected function tenantLoaded()
	{
		return ($this->tenant == null || empty($this->tenant->id))? false: true;
	}

	public function clearTenant()
	{
		$this->tenant = null;
		Session::remove('tenant_id');
	}

	public function disable()
	{
		$this->enabled = false;
	}

	public function enable()
	{
		$this->enabled = true;
	}

	/**
	 * Check if the current instance is active or inactive.
	 * MultiTenancy must be 'active' for it to be enabled or disabled.
	 *
	 * @return boolean
	 */
	public function isEnabled()
	{
		return $this->enabled;
	}

	/**
	 * Check if the tenant manager is active for the application
	 *
	 * @return boolean
	 */
	public function multiTenancyIsActive()
	{
		return $this->active;
	}

	/**
	 * Get all Tenants associated with the current User
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function allTenants()
	{
		$this->checkForActivation();
		$tenantRepo = app(config('auth.tenantRepository'));
		return $tenantRepo->all();
	}

	/**
	 *
	 * Check if a user belongs to a tenant
	 *
	 * @param      $tenant
	 * @param null $user
	 *
	 * @return bool
	 */
	public function doesUserBelongToTenant($tenantId, $user = null): bool
	{
		$tenantRepo = app(config('auth.tenantRepository'));
		if (!$user) $user = auth()->user();

		if (!$user) throw new UnauthorizedException('Login again to access this resource.');

		$user = $tenantRepo->getUserByTenant($user->id, $tenantId);

		return $user ? true: false;
	}

	public function getCurrentTenantForUser($user = null)
	{
		/** @var TenantsRepository $tenantRepo */
		$tenantRepo = app(config('auth.tenantRepository'));
		if (!$user) $user = auth()->user();

		if (!$user) throw new UnauthorizedException('Login again to access this resource.');

		if (!$user->current_tenant_id) throw new TenantNotSetException("User does not have a default tenant set.");

		return $tenantRepo->getTenantByUser($user->id, $user->current_tenant_id);
	}

	/**
	 *
	 * The the current tenant for user, if not set and get the first tenant, if not, throw an Exception.
	 *
	 * @param Authenticatable|null $user
	 *
	 * @return \EMedia\Oxygen\Entities\Auth\MultiTenant\Tenant
	 * @throws TenantNotSetException
	 * @throws UserDoesNotBelongToTenantException
	 */
	public function getClosestTenantForUserOrFail(Authenticatable $user = null)
	{
		/** @var TenantsRepository $tenantRepo */
		$tenantRepo = app(config('auth.tenantRepository'));

		if ($tenant = $this->getCurrentTenantForUser($user)) {
			return $tenant;
		}

		if ($tenant = $user->tenants()->first()) {
			$user->current_tenant_id = $tenant->id;
			$user->save();
			return $tenant;
		}

		throw new UserDoesNotBelongToTenantException("User with ID {$user->id} does not have any tenants.");
	}

}
