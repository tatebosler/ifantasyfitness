<?php
if(!isset($_COOKIE['iff-id'])) header('Location: http://www.ifantasyfitness.com');
$id = filter_var($_COOKIE['iff-id'], FILTER_SANITIZE_NUMBER_INT);

# Validate the user
include('../../php/db.php');
$check_q = @mysqli_query($db, "SELECT * FROM users WHERE id=$id");
if(mysqli_num_rows($check_q) > 0) {
	$user = mysqli_fetch_array($check_q);
	# confirm with social token
	$valid = false;
	if(isset($_COOKIE['iff-google']) and $_COOKIE['iff-google'] === $user['google']) $valid = true;
	if(isset($_COOKIE['iff-facebook']) and $_COOKIE['iff-facebook'] === $user['facebook']) $valid = true;
	if(!$valid) header('Location: http://www.ifantasyfitness.com');
}

# Grab user's permission value
# 0 - normal user
# 1 - captain
# 2 - coach
# 3 - administrator (this option rarely used)
$perms = $user['permissions'];

# User is valid
$current_set = 'Admin settings';
$title = $current_set . ' - Settings';
$connected = true;
include('../../php/head-auth.php');

function settingsType($name, $link, $min_perm) {
	global $current_set;
	global $perms;
	if($perms >= $min_perm) {
		echo '<li';
		if($name == $current_set) echo ' class="active"';
		echo '><a href="/settings/'.$link.'">'.$name.'</a></li>';
	}
}
?>
<div class="row">
	<div class="col-xs-12">
		<?php
		if($_POST['confirm_message'] == 'ok') echo '<div class="alert alert-success">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<h4><i class="fa fa-check"></i> Your settings have been saved.</h4>';
		?>
		<h2>Settings</h2>
	</div>
</div>
<div class="row">
	<div class="col-sm-3 col-md-2">
		<ul class="nav nav-pills nav-stacked">
			<?php
			settingsType('My profile','profile',0);
			settingsType('Goals', 'goals', 0);
			settingsType('My team', 'team', 1);
			settingsType('Seasons', 'seasons', 2);
			settingsType('People', 'people', 2);
			settingsType('Admin settings', 'admin', 2);
			?>
		</ul>
	</div>
	<div class="col-sm-9 col-md-10">
		<h2>Administrative settings</h2>
		<div class="alert alert-warning">
			<h4><i class="fa fa-warning"></i> Heads-up: These settings affect the entire site.</h4>
			Please be careful with them.
		</div>
		<form name="multipliers" class="form-horizontal">
			<div class="well">
				<h3>Point Multipliers</h3>
			</div>
		</form>
		<form name="multipliers" class="form-horizontal">
			<div class="well">
				<h3>Point Caps</h3>
			</div>
		</form>
		<form name="multipliers" class="form-horizontal">
			<div class="well">
				<h3>Altitude Settings</h3>
			</div>
		</form>
		<form name="multipliers" class="form-horizontal">
			<div class="well">
				<h3>Announcement</h3>
			</div>
		</form>
		<form name="multipliers" class="form-horizontal">
			<div class="well">
				<h3>Database maintenance</h3>
			</div>
		</form>
	</div>
</div>
<?php
include('../../php/foot.php');
?>