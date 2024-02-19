<?php

namespace EMedia\MultiTenant;

use EMedia\MultiTenant\Entities\TenantManager;
use Illuminate\Support\ServiceProvider;

class MultiTenantServiceProvider extends ServiceProvider
{

	public function boot()
	{

	}

	public function register()
	{
		$this->mergeConfigFrom( __DIR__ . '/../config/auth.php', 'auth');

		$this->app->bind('emedia.tenantManager.tenant', config('auth.tenantModel'));

		$this->app->singleton('emedia.tenantManager', function () {
			return $this->app->make(TenantManager::class);
		});
	}

}