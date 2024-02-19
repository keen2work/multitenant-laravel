<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTenantsTables extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('tenants', function (Blueprint $table) {
			$table->increments('id');
			$table->string('uuid')->unique()->index();
			$table->string('company_name');
			$table->string('slug')->nullable();
			$table->softDeletes();
			$table->timestamps();
		});

		// Create table to assign all users assigned to a tenant
		Schema::create('tenant_users', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->integer('tenant_id')->references('id')->on('tenants');
			$table->integer('user_id')->unsigned()->references('id')->on('users');
			$table->unique(['tenant_id', 'user_id']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('tenant_users');
		Schema::drop('tenants');
	}
}