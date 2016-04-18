<?php

Route::group([
	'namespace'  => 'Seat\Addon\Charts\Http\Controllers',
	'middleware' => 'bouncer:superuser',
	], function () {

	Route::get('/corporation/view/charts/{corporationID}', 'ChartController@index')->name('corporation.charts.index');

});


