<?php
if(!isset($_COOKIE['iff-id'])) header('Location: http://www.ifantasyfitness.com');
if(!isset($_GET['season'])) header("Location: http://www.ifantasyfitnes.com/home");
include('../php/db.php');
$id = filter_var($_COOKIE['iff-id'], FILTER_SANITIZE_NUMBER_INT);
$slug = filter_var($_GET['season'], FILTER_SANITIZE_SPECIAL_CHARS);

$check_q = @mysqli_query($db, "SELECT * FROM users WHERE id=$id");
if(mysqli_num_rows($check_q) > 0) {
	$user = mysqli_fetch_array($check_q);
	# confirm with social token
	$valid = false;
	if(isset($_COOKIE['iff-google']) and $_COOKIE['iff-google'] === $user['google']) $valid = true;
	if(isset($_COOKIE['iff-facebook']) and $_COOKIE['iff-facebook'] === $user['facebook']) $valid = true;
	if(!$valid) header('Location: http://www.ifantasyfitness.com');
}

if($user['profile'] == 1) header('Location: http://www.ifantasyfitness.com/settings/profile');

# Validate that the given season exists, AND that registration is open now.
$now = time();
$season_fetcher = @mysqli_query($db, "SELECT * FROM seasons WHERE name='$slug' AND reg_start <= $now AND reg_end >= $now");
if(mysqli_num_rows($season_fetcher) == 0) {
	setcookie("reg-fail",$slug,$now+3,'/','.ifantasyfitness.com');
	header("Location: http://www.ifantasyfitness.com/home");
}

# Preliminary reg-exist check
$regExists = @mysqli_query($db, "SELECT * FROM tMembers WHERE user=$id AND season='$slug'");
if(mysqli_num_rows($regExists) > 0) {
	setcookie('reg-exists',$slug,$now+3,'/','.ifantasyfitness.com');
	header("Location: http://www.ifantasyfitness.com/home");
}

# Season and user are valid.
# If user is confirming registration, process
if(isset($_POST['submitted'])) {
	if($_POST['prediction'] > 0) $predict = filter_var($_POST['prediction'], FILTER_SANITIZE_NUMBER_INT);
	if($_POST['division'] >= 0) $division = filter_var($_POST['division'], FILTER_SANITIZE_NUMBER_INT);
	if($_POST['goal'] >= 0) $goal = filter_var($_POST['goal'], FILTER_SANITIZE_NUMBER_INT);
	if($predict > 0 and $division >= 0 and $goal >= 0) {
		$registerer = @mysqli_query($db, "INSERT INTO tMembers (user, team, season, prediction, division, daily_goal) VALUES ($id, 1, '$slug', $predict, $division, $goal)");
		if($registerer) {
			setcookie('reg-confirmed',$slug,$now+3,'/','.ifantasyfitness.com');
			header("Location: http://www.ifantasyfitness.com/home");
		}
	} elseif ($predict == 0) {
		$no_goal = true;
	}
}

$connected = true;
$title = "Season Registration";
include('../php/head-auth.php');
?>
<div class="row">
	<div class="col-xs-12">
		<h2>Register for <?=$slug?></h2>
		<p><strong>Team Leaders:</strong> Once you have completed the registration process, be sure to ask your coaches for the Team Leader permissions.</p>
		<?php
		if($no_goal) {
			echo '<div class="alert alert-warning">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<h4><i class="fa fa-warning"></i> No Goal</h4>
			Please enter your prediction of how many points you think you\'ll earn in the '.$slug.' season.</div>';
		}
		?>
		<form name="register" action="./index.php?season=<?=$slug?>" method="post" class="form-horizontal">
			<div class="form-group<?php if ($no_goal) echo ' has-error'?>">
				<label class="col-xs-2 control-label">Prediction</label>
				<div class="col-xs-10">
					<p class="form-control-static">Please enter your prediction for <strong>how many TOTAL points you will score this season.</strong> This will also serve as your season point goal, and you can view it on your <a href="/home">home screen</a>. Captains and coaches will see this value when drafting you to a team, so please enter a realistic prediction.</p><br>
					<input type="number" name="prediction" class="form-control" min="0" max="5000">
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-2 control-label">Division</label>
				<div class="col-xs-10">
					<p class="form-control-static">Please select a division for the leaderboards.</p>
					<div class="radio">
						<label>
							<input type="radio" name="division" value="1" <?php if($user['grad'] == (date('Y') + 1) or $user['grad'] == (date('Y') + 2)) echo 'checked';?>>
							<strong>Upperclassmen</strong> - students entering junior or senior year
						</label>
					</div>
					<div class="radio">
						<label>
							<input type="radio" name="division" value="2" <?php if($user['grad'] == (date('Y') + 3) or $user['grad'] == (date('Y') + 4)) echo 'checked';?>>
							<strong>Underclassmen</strong> - students entering freshman or sophomore year
						</label>
					</div>
					<div class="radio">
						<label>
							<input type="radio" name="division" value="3" <?php if($user['grad'] == (date('Y') + 5) or $user['grad'] == (date('Y') + 6) or $user['grad'] == (date('Y') + 7)) echo 'checked';?>>
							<strong>Middle School</strong> - students entering grades 6 through 8
						</label>
					</div>
					<div class="radio">
						<label>
							<input type="radio" name="division" value="4">
							<strong>Staff</strong> - all SPPS staff, including coaches
						</label>
					</div>
					<div class="radio">
						<label>
							<input type="radio" name="division" value="5">
							<strong>Parents</strong> of runners or skiers (who aren't SPPS staff)
						</label>
					</div>
					<div class="radio">
						<label>
							<input type="radio" name="division" value="6" <?php if($user['grad'] <= date('Y')) echo 'checked';?>>
							<strong>Alumni</strong> of Highland (who don't fall into a division above)
						</label>
					</div>
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-2 control-label">Running Goal</label>
				<div class="col-xs-10">
					<p class="form-control-static">Please select a Daily Running Goal program. This will appear under your season points goal, and give you an idea of what to do to improve your running ability.</p>
					<div class="radio">
						<label>
							<input type="radio" name="goal" value="0">
							<strong>BRONZE</strong> - 150 miles. Recommended for freshmen, middle schoolers, and athletes brand new to the team.
						</label>
					</div>
					<div class="radio">
						<label>
							<input type="radio" name="goal" value="1">
							<strong>SILVER</strong> - 250 miles (men), 200 miles (women). Recommended for sophomores and athletes recovering from an injury.
						</label>
					</div>
					<div class="radio">
						<label>
							<input type="radio" name="goal" value="2">
							<strong>GOLD</strong> - 325 miles (men), 250 miles (women). Recommended for juniors and athletes who have historically run JV.
						</label>
					</div>
					<div class="radio">
						<label>
							<input type="radio" name="goal" value="3">
							<strong>PLATINUM</strong> - 400 miles (men), 300 miles (women) Recommended for seniors and determined varsity athletes.
						</label>
					</div>
					<div class="radio">
						<label>
							<input type="radio" name="goal" value="4">
							<strong>DIAMOND</strong> - 500 miles (men), 400 miles (women). For experienced varsity runners only. Requires serious motivation.
						</label>
					</div>
					<br>
					<input type="submit" class="btn btn-primary" value="Register">
					<input type="hidden" name="submitted" value="<?=$slug?>">
				</div>
			</div>
		</form>
	</div>
</div>
<?php
include_once("../php/foot.php");
?>