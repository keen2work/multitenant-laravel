# Multi-Tenant Handling Package for Laravel

This adds multi-tenant handling capability on a single database.

### Version Compatibility

| Laravel Version | This Package Version | Branch |
|----------------:|---------------------:|-------:|
|             v10 |                  2.0 |    2.x |  
|              v9 |                  1.0 |    1.x |  
|              v8 |                0.4.0 |    0.0 |  
|              v7 |                0.3.0 |    0.0 |  
|              v6 |                0.2.0 |    0.0 |  

## How to Install

Add the private repository in your `composer.json` file, and run `composer update`

```
	"repositories": [
        {
            "type":"vcs",
            "url":"git@bitbucket.org:elegantmedia/multi-tenant.git"
        }
    ]
```

In `config\app.php`, add the service provider and the Facade.
Add to the list of providers:
`EMedia\MultiTenant\MultiTenantServiceProvider::class`

Add to the list of aliases:
`'TenantManager' => EMedia\MultiTenant\Facades\TenantManager::class,`

### Publish the config file

Publish the config file with the following command.
```
php artisan vendor:publish --provider="EMedia\MultiTenant\MultiTenantServiceProvider" --tag=config
```

### Add the Tenant Model

Create a new Eloquent model for your Tenant, such as `App\Tenant`.

In your `config/multiTenant.php`, add update the Tenant model namespace if needed.

```
	'tenantModel'		=> App\Entities\Auth\Tenant::class,
```

### Migrations
All tables which should be bound to a Tenant, must have a `tenant_id` field (integer).

### Setting up Models

On the Eloquent models that should be bound to a Tenant, add the `TenantScopedModelTrait`

Eg:

```
	class Car extends Model
    {

        protected $tenant_column = ['tenant_id'];   // optional

        use TenantScopedModelTrait;

    }
```

You can change the foreign key with the `$tenant_column` property. If not provided, it will default to `tenant_id`.


## How to Run

After installation, you can set the tenant at any point of the application. Most commonly this should be done in a Middleware or after a login function. You can bind a Tenant by ID or passing a Tenant object.

Bind with ID
```
	TenantManager::setTenantById(2);
```

Alternatively, bind with a Tenant object
```
	$tenant = App\Tenant::find(2);
	TenantManager::setTenant($tenant);
```

And then when your run an Eloquent query, all the results will be scoped to that Tenant.
Example:
```
	// return all cars for Tenant 2
	$cars = Car::all();

	// delete a car, only if it belongs to Tenant 2
	Car::destroy(['14']);

	// create a car, and bind that to Tenant 2
	$car = Car::create(['name' => 'Nissan GTR']);
```

## Excluding Tenant binding

You can disable tenant binding before running some Eloquent queries.
```
	TenantManager::disable();
	$invitations = Invitation::all();
	TenantManager::enable();
```
This will return all the Invitations in above example. **Always** enable the `TenantManager` after running such a query.


## WARNING
Because of how query scoping is executed in Laravel 5, any `orWhere*` queries must be nested when scoping by Tenant.

Bad
```
	// DO NOT use the orWHere queries
    $cars = Car::where('id', 2)->orWhere('id', 5)->get();
    $cars = Car::where('id', 2)->orWhereExists('id', 5)->get();

    // DO NOT use this as well
    $carsQuery = Car::where('id', 2);
    $carsQuery = Car::orWhere('id', 5);
    $cars = $carsQuery->get();
```

Good
```
    // use nested queries for orWhere
    $cars = Car::where(function ($query) {
        $query->where('id', 1);
        $query->orWhere('id', 4);
    })->get();
```

Also if you're writing Raw queries, (without Eloquent), you must manually filter the rows by `tenant_id`


## TODO: Roadmap

- Remove disabling the TenantManager and find a solution to run queries for all entities
- Multiple Database Support


## Reference
Reference material and some code used/modified from these projects.

[Multi-Tenant package for Laravel4](https://github.com/AuraEQ/laravel-multi-tenant)
[Multi-Tenancy in Laravel4](http://culttt.com/2014/03/31/multi-tenancy-laravel-4/)
