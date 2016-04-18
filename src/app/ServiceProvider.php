<?php

namespace Seat\Addon\Charts;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
	/**
	 * Bootstrap the application services.
	 *
	 * @param \Illuminate\Routing\Router $router
	 */
	public function boot(Router $router) {
		if (!$this->app->routesAreCached()) {
			include __DIR__ . '/Http/routes.php';
		}

		$this->loadViewsFrom(__DIR__ . '/../resources/views', 'charts');

		$this->mergeConfigFrom(__DIR__ . '/../config/package.corporation.menu.php', 'package.corporation.menu');

		$this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'charts');
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}
}
