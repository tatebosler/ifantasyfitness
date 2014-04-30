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
$slug_fail = false;

if(isset($_POST['submitted'])) {
	$ok = true;
	$regStart = strtotime($_POST['reg_start']);
	$regEnd = strtotime($_POST['reg_end']);
	$compStart = strtotime($_POST['comp_start']);
	$compEnd = strtotime($_POST['comp_end']);
	
	# Process to find the first day of daily Goals
	$goalStartStr = substr($_POST['comp_start'], 0, 10).' 12:00 AM';
	$goalStart = strtotime($goalStartStr);
	
	# Escape name
	$name = filter_var($_POST['name'], FILTER_SANITIZE_ENCODED);
	$slug = filter_var($_POST['slug'], FILTER_SANITIZE_ENCODED);
	
	# If all looks good, add to database.
	$fields = array('reg_start','reg_end','comp_start','comp_end','name','slug');
	foreach($fields as $value) {
		if(empty($_POST[$value])) $ok = false;
	}
	# Make sure that time goes forwards
	if($regStart >= $regEnd) $ok = false;
	if($regEnd >= $compStart) $ok = false;
	if($compStart >= $compEnd) $ok = false;
	
	if($ok) {
		# Verify that the slug doesn't exist
		$slug_unique = @mysqli_query($db, "SELECT * FROM seasons WHERE name='$slug'");
		if(mysqli_num_rows($slug_unique) == 0) {
			$season_creator = @mysqli_query($db, "INSERT INTO seasons (name, reg_start, reg_end, comp_start, comp_end, display_name, dailygoal_start) VALUES ('$slug', $regStart, $regEnd, $compStart, $compEnd, '$name', $goalStart)");
			if($season_creator) {
				setcookie('confirm_message','create',time()+3,'/','.ifantasyfitness.com');
				header("Location: http://www.ifantasyfitness.com/settings/seasons");
			}
		} else {
			$slug_fail = true;
		}
	}
}

# User is valid, and has proper permissions.
$current_set = 'Seasons';
$title = $current_set . ' - Create Season - Settings';
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
		if($slug_fail) echo '<div class="alert alert-danger">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<i class="fa fa-times"></i> Sorry! That slug already exists. Please use a different slug.';
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
		<h2>Create Season</h2>
		<p><a href="/settings/seasons">&larr; Back to season list</a></p>
		<form name="season-create" method="post" class="form-horizontal">
			<div class="form-group">
				<label class="col-xs-2 control-label">Name</label>
				<div class="col-xs-10">
					<input type="text" name="name" class="form-control" maxlength="255">
					<span class="help-block">The name of the season, as it will be displayed across the site (255 characters max)</span>
				</div>
			</div>
			<div class="form-group <?php if($slug_fail) echo " has-error"; ?>">
				<label class="col-xs-2 control-label">Slug</label>
				<div class="col-xs-10">
					<input type="text" name="slug" class="form-control" maxlength="16" value="<?php echo date('Y'); ?>">
					<span class="help-block">Technical name of the season, used internally - must be unique, 16 characters max, cannot be changed</span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-2 control-label">Registration start</label>
				<div class='col-xs-10'>
			        <div class="input-group date" id="reg_start">
			            <input type="text" class="form-control" name="reg_start" readonly placeholder="Use the calendar on the right to select start date and time">
			            <span class="input-group-addon"><span class="fa fa-calendar"></span>
			            </span>
			        </div>
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-2 control-label">Registration end</label>
				<div class="col-xs-10">
			        <div class="input-group date" id="reg_end">
			            <input type="text" class="form-control" name="reg_end" readonly placeholder="Use the calendar on the right to select end date and time">
			            <span class="input-group-addon"><span class="fa fa-calendar"></span>
			            </span>
				    </div>
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-2 control-label">Competition start</label>
				<div class='col-xs-10'>
			        <div class="input-group date" id="comp_start">
			            <input type="text" class="form-control" name="comp_start" readonly placeholder="Use the calendar on the right to select start date and time">
			            <span class="input-group-addon"><span class="fa fa-calendar"></span>
			            </span>
			        </div>
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-2 control-label">Competition end</label>
				<div class="col-xs-10">
			        <div class="input-group date" id="comp_end">
			            <input type="text" class="form-control" name="comp_end" readonly placeholder="Use the calendar on the right to select end date and time">
			            <span class="input-group-addon"><span class="fa fa-calendar"></span>
			            </span>
				    </div>
				</div>
			</div>
			<p>You can create teams on the next page.</p>
			<input type="hidden" name="submitted" value="create">
			<input type="submit" class="btn btn-primary btn-block" value="Create Team">
		</form>
	</div>
</div
<?php
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