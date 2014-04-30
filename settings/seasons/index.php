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

# This page requires Level 2 permissions.
# As such, anyone with lower permissions is hereby banished.
if($perms < 2) header('Location: http://www.ifantasyfitness.com/settings/profile');

# User is valid, and has proper permissions.
$current_set = 'Seasons';
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
		switch($_COOKIE['confirm_message']) {
			case 'create':
				echo '<div class="alert alert-success">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<i class="fa fa-check"></i> The season has been successfully created.</div>';
				break;
			case 'delete':
				echo '<div class="alert alert-success">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<i class="fa fa-check"></i> The season has been successfully deleted.</div>';
				break;
		}
		?>
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
		<h2>Seasons <a href="create.php" class="btn btn-primary pull-right">Create new</a></h2>
		<?php
		$season_fetcher = @mysqli_query($db, "SELECT * FROM seasons ORDER BY reg_start DESC");
		if(mysqli_num_rows($season_fetcher) == 0) {
			echo '<p class="lead">No seasons have been created! <a href="create.php">Click here</a> to create one!</p>';
		} else {
			echo '<table class="table">
			<thead>
			<tr>
			<th>Name</th>
			<th>Registration</th>
			<th>Competition</th>
			<th>Actions</th>
			</tr>
			</thead>
			<tbody>';
			while($season = mysqli_fetch_array($season_fetcher)) {
				echo '<tr><td>'.$season['display_name'].'</td>
				<td>'.date('n-j-Y g:i A',$season['reg_start']).' to '.date('n-j-Y g:i A',$season['reg_end']).'</td>
				<td>'.date('n-j-Y g:i A',$season['comp_start']).' to '.date('n-j-Y g:i A',$season['comp_end']).'</td>
				<td><i class="fa fa-edit"></i> <a href="edit.php?id='.$season['name'].'">edit</a> - <i class="fa fa-trash-o"></i> <a href="delete.php?id='.$season['name'].'">delete</a></tr>';
			}
			echo '</tbody></table>';
		}
		?>
	</div>
</div>
<?php
include('../../php/foot.php');
?>