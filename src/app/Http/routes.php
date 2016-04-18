<?php

Route::group([
	'namespace'  => 'Seat\Addon\Charts\Http\Controllers',
	'middleware' => ['auth', 'bouncer:superuser'],
	], function () {

	Route::get('/corporation/view/charts/{corporation_id}', 'ChartController@index')->name('corporation.charts.index');

});
