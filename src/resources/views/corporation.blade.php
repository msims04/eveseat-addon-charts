@extends('web::layouts.grids.12')

@section('title', trans('charts::charts.charts'))
@section('page_header', trans('charts::charts.charts'))

@section('full')
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css">
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>

<div class="row">

	<div class="col-xs-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{!! trans('charts::charts.skill_points') !!}</h3>
			</div>
			<div class="panel-body">
				<div class="box-body box-profile">
					<div id="skill-points"></div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-xs-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{!! trans('charts::charts.users') !!}</h3>
			</div>
			<div class="panel-body">
				<div class="box-body box-profile">
					<div id="users"></div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-xs-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{!! trans('charts::charts.active_users') !!}</h3>
			</div>
			<div class="panel-body">
				<div class="box-body box-profile">
					<div id="active-users"></div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-xs-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{!! trans('charts::charts.active_characters') !!}</h3>
			</div>
			<div class="panel-body">
				<div class="box-body box-profile">
					<div id="active-characters"></div>
				</div>
			</div>
		</div>
	</div>

</div>

<script>
	Morris.Bar({
		element: 'skill-points',
		data: {!! $skill_points !!},
		xkey: 'y',
		ykeys: ['a'],
		labels: ['Series A']
	});

	Morris.Donut({
		element: 'users',
		data: {!! $users !!},
	});

	Morris.Donut({
		element: 'active-users',
		data: {!! $active_users !!},
	});

	Morris.Donut({
		element: 'active-characters',
		data: {!! $active_characters !!},
	});

</script>

@endsection
