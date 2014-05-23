<?php
if(!isset($_GET['id']) and !isset($_POST['id'])) header('Location: http://www.ifantasyfitness.com');
if(!isset($_GET['id'])) {
	$slug = filter_var($_POST['id'], FILTER_SANITIZE_SPECIAL_CHARS);
} else {
	$slug = filter_var($_GET['id'], FILTER_SANITIZE_SPECIAL_CHARS);
}
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

# Process data entries
if(isset($_POST['t-submit'])) {
	if($_POST['t-submit'] > 0) {
		# Editing existing team
		$tid = filter_var($_POST['t-submit'], FILTER_SANITIZE_NUMBER_INT);
		$name = filter_var($_POST['name-'.$tid], FILTER_SANITIZE_SPECIAL_CHARS);
		$captain = filter_var($_POST['captain-'.$tid], FILTER_SANITIZE_NUMBER_INT);
		
		if(strlen($name) > 0) {
			$team_updater = @mysqli_query($db, "UPDATE tData SET name='$name', captain=$captain WHERE id=$tid");
			$team_assigner = @mysqli_query($db, "UPDATE tMembers SET team=$tid WHERE user=$captain AND season='$slug'");
		}
	} elseif ($_POST['t-submit'] == 0) {
		# Creating new team
		$name = filter_var($_POST['name-0'], FILTER_SANITIZE_SPECIAL_CHARS);
		$captain = filter_var($_POST['captain-0'], FILTER_SANITIZE_NUMBER_INT);
		
		if(strlen($name) > 0) {
			$team_inserter = @mysqli_query($db, "INSERT INTO tData (name, season, captain) VALUES ('$name', '$slug', $captain)");
			$tid = mysqli_insert_id($team_inserter);
			$team_assigner = @mysqli_query($db, "UPDATE tMembers SET team=$tid WHERE user=$captain AND season='$slug'");
		}
	}
}
if($_POST['td-submit'] > 0) {
	# Deletion is confirmed, go!
	# Delete the team
	$tid = filter_var($_POST['td-submit'], FILTER_SANITIZE_NUMBER_INT);
	$team_deleter = @mysqli_query($db, "DELETE FROM tData WHERE id=$tid");
	
	# Clear registrations by setting users' team value to 1.
	$reg_deleter = @mysqli_query($db, "UPDATE tMembers SET team=1 WHERE team=$tid");
	
	# Delete records associated with it
	$record_deleter = @mysqli_query($db, "DELETE FROM records WHERE team=$tid");
	
	# Done
	if(!$team_deleter or !$reg_deleter or !$record_deleter) {
		$deletion = false;
	} else {
		$deletion = true;
	}
}
if ($_POST['other-submitted'] == 1) {
	# Doing other things
	$ok = true;
	$name = filter_var($_POST['name'], FILTER_SANITIZE_SPECIAL_CHARS);
	$regStart = strtotime($_POST['reg_start']);
	$regEnd = strtotime($_POST['reg_end']);
	$compStart = strtotime($_POST['comp_start']);
	$compEnd = strtotime($_POST['comp_end']);
	
	$fields = array('reg_start','reg_end','comp_start','comp_end','name');
	foreach($fields as $value) {
		if(empty($_POST[$value])) {
			$ok = false;
		}
	}
	# Make sure that time goes forwards
	if($regStart >= $regEnd) $ok = false;
	if($regEnd >= $compStart) $ok = false;
	if($compStart >= $compEnd) $ok = false;
	
	if($ok) {
		$the_season_updater = @mysqli_query($db, "UPDATE seasons SET display_name='$name', reg_start=$regStart, reg_end=$regEnd, comp_start=$compStart, comp_end=$compEnd, WHERE name='$slug'");
	}
}

# Validate the season
$the_season_fetcher = @mysqli_query($db, "SELECT * FROM seasons WHERE name='$slug'");
if(mysqli_num_rows($the_season_fetcher) == 0) {
	header('Location: http://www.ifantasyfitness.com/settings/seasons');
} else {
	$the_season = mysqli_fetch_array($the_season_fetcher);
}

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

$captains_list_q = @mysqli_query($db, "SELECT * FROM users WHERE permissions >= 1 AND profile=0");
$captains = array();
while($captain = mysqli_fetch_array($captains_list_q)) {
	$captains[$captain['id']] = $captain['first'].' '.$captain['last'];
}
asort($captains);
?>
<div class="row">
	<div class="col-xs-12">
		<?php
		if($the_season_updater) echo '<div class="alert alert-success">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<i class="fa fa-check"></i> The season has been updated.</div>';
		if($team_inserter) echo '<div class="alert alert-success">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<i class="fa fa-check"></i> The team has been successfully created.</div>';
		if($team_updater) echo '<div class="alert alert-success">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<i class="fa fa-check"></i> The team has been successfully updated.</div>';
		if($deletion) echo '<div class="alert alert-success">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<i class="fa fa-check"></i> The team has been successfully deleted.</div>';
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
		<h2>Edit <?=$slug?> season</h2>
		<p><a href="/settings/seasons">&larr; Back to season list</a></p>
		<hr>
		<h4>Teams <a data-toggle="modal" data-target="#edit-0" class="btn btn-primary pull-right">Create new team</a></h4>
		<table class="table" id="teams">
			<thead>
				<tr>
					<th>Team Name</th>
					<th>Team Leader</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$team_fetcher = @mysqli_query($db, "SELECT * FROM tData WHERE season='$slug' ORDER BY id ASC");
				$teams = array();
				while($team = mysqli_fetch_array($team_fetcher)) {
					echo '<tr><td>'.$team['name'].'</td>';
					$captain = $team['captain'];
					$user_fetcher = @mysqli_query($db, "SELECT * FROM users WHERE id=$captain");
					$the_user = mysqli_fetch_array($user_fetcher);
					echo '<td>'.$the_user['first'].' '.$the_user['last'].'</td>
					<td><i class="fa fa-edit"></i> <a data-toggle="modal" data-target="#edit-'.$team['id'].'">edit</a> <i class="fa fa-trash-o"></i> <a data-toggle="modal" data-target="#delete-'.$team['id'].'">delete</a></td>
					</tr>';
					$teams[] = $team;
				}
				$teams[] = array('id' => 0, 'name' => "New Team", 'captain' => 0);
				?>
			</tbody>
		</table>
		<h4>Other season settings</h4>
		<form name="other" class="form-horizontal" method="post" action="/settings/seasons/edit.php?id=<?=$slug?>">
			<div class="form-group">
				<label class="col-xs-2 control-label">Name</label>
				<div class="col-xs-10">
					<input type="text" name="name" class="form-control" maxlength="255" value="<?=$the_season['display_name']?>">
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-2 control-label">Registration start</label>
				<div class='col-xs-10'>
			        <div class="input-group date" id="reg_start">
			            <input type="text" class="form-control" name="reg_start" readonly placeholder="Use the calendar on the right to select start date and time" value="<?php echo date('m/d/Y g:i A', $the_season['reg_start']); ?>">
			            <span class="input-group-addon"><span class="fa fa-calendar"></span>
			            </span>
			        </div>
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-2 control-label">Registration end</label>
				<div class="col-xs-10">
			        <div class="input-group date" id="reg_end">
			            <input type="text" class="form-control" name="reg_end" readonly placeholder="Use the calendar on the right to select end date and time" value="<?php echo date('m/d/Y g:i A', $the_season['reg_end']); ?>">
			            <span class="input-group-addon"><span class="fa fa-calendar"></span>
			            </span>
				    </div>
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-2 control-label">Competition start</label>
				<div class='col-xs-10'>
			        <div class="input-group date" id="comp_start">
			            <input type="text" class="form-control" name="comp_start" readonly placeholder="Use the calendar on the right to select start date and time" value="<?php echo date('m/d/Y g:i A', $the_season['comp_start']); ?>">
			            <span class="input-group-addon"><span class="fa fa-calendar"></span>
			            </span>
			        </div>
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-2 control-label">Competition end</label>
				<div class="col-xs-10">
			        <div class="input-group date" id="comp_end">
			            <input type="text" class="form-control" name="comp_end" readonly placeholder="Use the calendar on the right to select end date and time" value="<?php echo date('m/d/Y g:i A', $the_season['comp_end']); ?>">
			            <span class="input-group-addon"><span class="fa fa-calendar"></span>
			            </span>
				    </div>
				</div>
			</div>
			<input type="submit" class="btn btn-primary btn-block" value="Save changes">
			<input type="hidden" name="other-submitted" value="1">
		</form>
	</div>
</div>
<?php
foreach($teams as $team) {
	echo '<form name="edit-'.$team['id'].'" action="/settings/seasons/edit.php?id='.$slug.'" class="form-horizontal" method="post">
	<div id="edit-'.$team['id'].'" aria-hidden="true" class="modal fade">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h4 class="modal-title">Edit '.$team['name'].'</h4>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label class="col-xs-4 control-label">Name</label>
						<div class="col-xs-8">
							<input type="text" name="name-'.$team['id'].'" class="form-control" maxlength="255" value="'.$team['name'].'">
						</div>
					</div>
					<div class="form-group">
						<label class="col-xs-4 control-label">Team Leader</label>
						<div class="col-xs-8">
							<select name="captain-'.$team['id'].'" class="form-control">';
		foreach($captains as $id => $name) {
			echo '<option value="'.$id.'"';
			if($id == $team['captain']) echo ' selected';
			echo '>'.$name.'</option>';
		}
		echo '</select>
						</div>
					</div>
					Don\'t see the athlete you want? Just go to <a href="/settings/people?role=0">People settings</a> and click "Edit" next to their name.
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<input type="submit" class="btn btn-primary" value="Save changes">
					<input type="hidden" name="t-submit" value="'.$team['id'].'">
					<input type="hidden" name="id" value="'.$slug.'">
				</div>
			</div>
		</div>
	</div>
</form>
<form name="delete-'.$team['id'].'" action="/settings/seasons/edit.php?id='.$slug.'" class="form-horizontal" method="post">
	<div id="delete-'.$team['id'].'" aria-hidden="true" class="modal fade">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h4 class="modal-title">Delete '.$team['name'].'</h4>
				</div>
				<div class="modal-body">
					<div class="alert alert-danger">
					<h4><strong>Warning!</strong></h4>
					You are about to delete a team. This action cannot be undone.
					</div>
					<p>Deleting this team will:</p>
					<ul>
						<li>Unregister all of its members from the team (but not from the season), and</li>
						<li>Delete <strong>all records that its members have made</strong> while competing in this team.</li>
					</ul>
					<p><span class="text-primary"><strong>You cannot undo this action.</strong></span> Are you sure you want to delete the team?</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<input type="submit" class="btn btn-primary" value="Delete team">
					<input type="hidden" name="td-submit" value="'.$team['id'].'">
					<input type="hidden" name="id" value="'.$slug.'">
				</div>
			</div>
		</div>
	</div>
</form>';
}

include('../../php/foot.php');
?>
<script>
$(function () {
    $('#reg_start').datetimepicker({
	    icons: {
	        time: "fa fa-clock-o",
	        date: "fa fa-calendar",
	        up: "fa fa-arrow-up",
	        down: "fa fa-arrow-down"
	    }
    });
    $('#reg_end').datetimepicker({
	    icons: {
	        time: "fa fa-clock-o",
	        date: "fa fa-calendar",
	        up: "fa fa-arrow-up",
	        down: "fa fa-arrow-down"
	    }
    });
    $('#comp_start').datetimepicker({
	    icons: {
	        time: "fa fa-clock-o",
	        date: "fa fa-calendar",
	        up: "fa fa-arrow-up",
	        down: "fa fa-arrow-down"
	    }
    });
    $('#comp_end').datetimepicker({
	    icons: {
	        time: "fa fa-clock-o",
	        date: "fa fa-calendar",
	        up: "fa fa-arrow-up",
	        down: "fa fa-arrow-down"
	    }
    });
    $("#reg_start").on("change.dp",function (e) {
       $('#reg_end').data("DateTimePicker").setStartDate(e.date);
    });
    $("#reg_end").on("change.dp",function (e) {
       $('#reg_start').data("DateTimePicker").setEndDate(e.date);
    });
    $("#comp_start").on("change.dp",function (e) {
       $('#comp_end').data("DateTimePicker").setStartDate(e.date);
    });
    $("#comp_end").on("change.dp",function (e) {
       $('#comp_start').data("DateTimePicker").setEndDate(e.date);
    });
});
</script>
