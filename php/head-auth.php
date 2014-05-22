<?php
if(!$connected) include('db.php');
# Assume the user is valid. Now, what season are we in?
$now = time();
$season_count = @mysqli_query($db, "SELECT * FROM seasons");
if(mysqli_num_rows($season_count) == 1) {
	$season_data = mysqli_fetch_array($season_count);
	$season = $season_data['name'];
	$s = true;
} elseif (mysqli_num_rows($season_count) == 0) {
	$s = false;
} else {
	$season_finder = @mysqli_query($db, "SELECT * FROM seasons WHERE comp_start <= $now ORDER BY comp_start DESC");
	if(mysqli_num_rows($season_finder) == 0) {
		$s = false;
	} else {
		$season_data = mysqli_fetch_array($season_finder);
		$season = $season_data['name'];
		$s = true;
	}
}

if($s) {
	$point_fetch = @mysqli_query($db, "SELECT * FROM tMembers WHERE user=$id AND season='$season'");
	$point_data = mysqli_fetch_array($point_fetch);
	$points = round($point_data['season_total'], 2);
} else {
	$points = 0;
}
echo '<!DOCTYPE html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>'.$title.' - iFantasyFitness - Train Competitively</title>
	    <link href="//netdna.bootstrapcdn.com/bootswatch/3.1.1/simplex/bootstrap.min.css" rel="stylesheet">
	    <link href="//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.css" rel="stylesheet">
	    <link href="//ifantasyfitness.com/css/bootstrap-datetimepicker.css" rel="stylesheet">
	</head>
    <body>
		<div class="navbar navbar-inverse" role="navigation">
			<div class="container">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" href="/home">iFantasyFitness</a>
				</div>
				<div class="collapse navbar-collapse">
					<ul class="nav navbar-nav">
						<li><a href="/home"><i class="hidden-sm fa fa-home"></i> Home</a></li>
						<li><a href="/add"><i class="hidden-sm fa fa-plus"></i> Add points <span class="badge">'.$points.'</span></a></li>
						<li><a href="/records"><i class="hidden-sm fa fa-th-list"></i> My records</a></li>
						<li><a href="/leaderboard"><i class="hidden-sm fa fa-bar-chart-o"></i> Leaderboard</a></li>
						<li class="hidden-sm"><a href="/rules"><i class="hidden-sm fa fa-bullhorn"></i> Rules</a></li>
					</ul>
					<ul class="nav navbar-nav navbar-right">
						<li class="hidden-sm"><a href="/settings"><i class="fa fa-cog"></i> Settings</a></li>
						<li class="hidden-sm"><a href="/login/signout.php"><i class="fa fa-sign-out"></i> Sign out</a></li>
						<li class="visible-sm"><a href="/settings"><i class="fa fa-cog"></i></a></li>
						<li class="visible-sm"><a href="/login/signout.php"><i class="fa fa-sign-out"></i></a></li>
					</ul>
				</div>
			</div>
		</div>
		<div class="container">';
$announce_data = array();
$announcement_grab = @mysqli_query($db, "SELECT * FROM globals WHERE name LIKE 'announcement\_%'");
while($announcement_info = mysqli_fetch_array($announcement_grab)) {
	$ada_field = substr($announcement_info['name'],13);
	$announce_data[$ada_field] = $announcement_info['value'];
}
if(!empty($announce_data['text'])) {
	echo '<div class="row hidden-print">
	<div class="col-xs-12">
	<div class="well">'.$announce_data['text'];
	if(!empty($announce_data['link'])) {
		echo ' <a href="'.$announce_data['link'].'">';
		if(!empty($announce_data['link_text'])) {
			echo $announce_data['link_text'];
		} else {
			echo $announce_data['link'];
		}
		echo '</a>';
	}
	echo '</div></div></div>';
}
?>