<!DOCTYPE html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>iFantasyFitness - Train Competitively</title>
    <link href="css/main.css" rel="stylesheet">
    <link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css" rel="stylesheet">
    <link href="css/homepage.css" rel="stylesheet">
    <link href="css/bootstrap-datetimepicker.css" rel="stylesheet">
</head>
<body>
	<div class="site-wrapper">
		<div class="site-wrapper-inner">
			<div class="cover-container">
				<div class="masthead clearfix">
					<div class="inner">
						<h3 class="masthead-brand" style="color:#fff;">iFantasyFitness</h3>
						<ul class="nav masthead-nav">
						<?php
						if(isset($_COOKIE['iff-id'])) {
							echo '<li><a href="/add">Add points</a></li>
							<li><a href="/leaderboard">Leaderboard</a></li>';
						} else {
							echo '<li><a href="/leaderboard">Leaderboard</a></li>
							<li><a href="/rules">Rules</a></li>
							<li><a href="/login">Sign in</a></li>';
						}
						?>
						</ul>
					</div>
				</div>
			<div class="inner cover">
				<h1 class="cover-heading" style="color: #fff;">Welcome to iFantasyFitness.</h1>
				<p class="lead">iFantasyFitness is Highland Park Cross Country's game of summer training. By competing, the team gets stronger.</p>
				<p class="lead">
					<?php
					if(isset($_COOKIE['iff-id'])) {
						echo '<a href="/home" class="btn btn-lg btn-custom">Go to dashboard</a>';
					} else {
						echo '<a href="/login" class="btn btn-lg btn-custom">Get started</a>';
					}
					?>
				</p>
			</div>
			<div class="mastfoot">
				<div class="inner">
					<p>iFantasyFitness copyright &copy; 2012-<?=date('Y')?> Highland Park Cross Country. Developed by <a href="http://www.tatebosler.com">Tate Bosler</a>.</p>
					<p><a href="http://blog.ifantasyfitness.com">blog</a> - <a href="https://github.com/ichiefboz/ifantasyfitness">source code</a> - <a href="https://twitter.com/ifantasyfitness">twitter</a> - <a href="http://www.dreamhost.com/donate.cgi?id=17581">support us</a></p>
				</div>
			</div>
		</div>
	</div>
</div>