<?php
if(!isset($_COOKIE['iff-id'])) header('Location: http://www.ifantasyfitness.com');
$id = filter_var($_COOKIE['iff-id'], FILTER_SANITIZE_NUMBER_INT);

# Validate the user
include('../php/db.php');
$check_q = @mysqli_query($db, "SELECT * FROM users WHERE id=$id");
if(mysqli_num_rows($check_q) > 0) {
	$user = mysqli_fetch_array($check_q);
	# confirm with social token
	$valid = false;
	if(isset($_COOKIE['iff-google']) and $_COOKIE['iff-google'] === $user['google']) $valid = true;
	if(isset($_COOKIE['iff-facebook']) and $_COOKIE['iff-facebook'] === $user['facebook']) $valid = true;
	if(!$valid) header('Location: http://www.ifantasyfitness.com');
}

# User is valid
$title = 'My Records';
$connected = true;
include('../php/head-auth.php');
?>
<div class="row">
	<div class="col-xs-12">
		<?php
		if($_COOKIE['message'] == 'delete') {
			echo '<div class="alert alert-success">
			<i class="fa fa-check"></i> The record has been deleted.
			</div>';
		} elseif ($_COOKIE['message'] == 'edit') {
			echo '<div class="alert alert-success">
			<i class="fa fa-check"></i> Your changes have been saved. The leaderboard will be updated in '.(60 - date('s')).' seconds.
			</div>';
		}
		?>
		<h2>Your Records</h2>
		<?php
		$record_types = array("run" => "Running", "run_team" => "Running at Monument", "rollerski" => "Rollerskiing", "walk" => "Walking", "hike" => "Hiking with packs", "bike" => "Biking", "swim" => "Swimming", "paddle" => "Paddling, Rowing or Kayaking", "strength" => "Strength or core training", "sports" => "Aerobic sports");
		$use_minutes = array('paddle','strength','sports');
		$record_fetcher = @mysqli_query($db, "SELECT * FROM records WHERE user=$id ORDER BY disp_id DESC");
		if(mysqli_num_rows($record_fetcher) == 0) {
			# Does the user have a profile yet?
			echo '<p class="lead">No records found!</p>
			<p class="lead">';
			if($user['profile'] == 1) {
				echo 'Set up your profile, then start recording your workouts!</p>
				<a class="btn btn-primary btn-block" href="/settings/profile">Set up profile</a>';
			} else {
				echo 'Click the button below to start recording workouts!</p>
				<a class="btn btn-primary btn-block" href="/add">Add points</a>';
			}
		} else {
			$i = 0;
			$life_total = 0;
			$current_disp_id = 0;
			while($record = mysqli_fetch_array($record_fetcher)) {
				if($record['disp_id'] == $current_disp_id) {
					continue;
				} else {
					$current_disp_id = $record['disp_id'];
				}
				$i++;
				echo '<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">'.date('F j, Y g:i:s a',$record['timestamp']).'<span class="pull-right">'.round($record['total'], 2).'</span></h3>
				</div>
				<div class="panel-body">
				<table class="table">
					<thead>
						<tr>
							<th class="col-xs-6">Activity</th>
							<th class="col-xs-3">Duration</th>
							<th class="col-xs-3">Points</th>
						</tr>
					</thead>
					<tbody>';
				# Record Data
				foreach($record_types as $data=>$disp) {
					$points = $data . '_p';
					if($record[$data] != 0) {
						echo '<tr>
						<td>'.$disp.'</td>
						<td>'.round($record[$data],2);
						if(in_array($data, $use_minutes)) {
							echo ' minute';
						} else {
							echo ' mile';
						}
						if($record[$data] != 1) echo 's';
						echo '</td>
						<td>'.round($record[$points],2).'</td>
						</tr>';
					}
				}
				echo '</tbody>
				</table>';
				if($record['altitude'] != 1) echo '<p><strong>Altitude bonus awarded:</strong> x'.$record['altitude'].'</p>';
				if(!empty($record['comments'])) echo '<p><strong>Comment:</strong> '.$record['comments'].'</p>';
				echo 'Total: '.round($record['total'],2).' point';
				if($record['total'] != 1) echo 's';
				echo '<span class="pull-right"><i class="fa fa-edit"></i> <a href="/records/edit?id='.$record['id'].'">edit</a> - <i class="fa fa-trash-o"></i> <a href="/records/delete?id='.$record['id'].'">delete</a></span></div>';
				$life_total += $record['total'];
				echo '</div>';
			}
		}
		?>
	</div>
<?php
include('../php/foot.php');
?>