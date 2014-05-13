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
$current_set = 'People';
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

$role = 'all';
if(isset($_GET['role'])) {
	$role = filter_var($_GET['role'],FILTER_SANITIZE_SPECIAL_CHARS);
}

$season = 'all';
if(isset($_GET['season'])) {
	$season = filter_var($_GET['season'],FILTER_SANITIZE_SPECIAL_CHARS);
}

$teams = array();
$teams[1] = "No team";
$team_lister = @mysqli_query($db, "SELECT * FROM tData WHERE season='$season'");
while($team = mysqli_fetch_array($team_lister)) {
	$teams[$team['id']] = $team; # Puts all teams for listed season into an array
}

$divisions = array(1 => "Upperclassmen", 2 => "Underclassmen", 3 => "Middle School", 4 => "Staff", 5 => "Parents", 6 => "Alumni");
?>
<div class="row hidden-print">
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
	<div class="col-sm-3 col-md-2 hidden-print">
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
		<div class="panel panel-default hidden-print">
			<div class="panel-heading">
				<h3 class="panel-title">Filter</h3>
			</div>
			<div class="panel-body">
				<div class="row">
					<label class="col-xs-6">Season</label>
					<label class="col-xs-6">Role</label>
				</div>
				<div class="row">
					<div class="col-xs-6">
						<form name="f_season">
							<select name="season" onchange="document.location.href=document.f_season.season.options[document.f_season.season.selectedIndex].value" class="form-control">
								<option value="/settings/people?season=all&role=<?=$role?>">All seasons</option>
								<?php
								$seasons_fetcher = @mysqli_query($db, "SELECT * FROM seasons");
								while($the_season = mysqli_fetch_array($seasons_fetcher)) {
									echo '<option value="/settings/people?season='.$the_season['name'].'&role='.$role.'"';
									if($season == $the_season['name']) echo ' selected';
									echo '>'.$the_season['display_name'].'</option>';
								}
								?>
							</select>
						</form>
					</div>
					<div class="col-xs-6">
						<form name="f_role">
							<select name="role" onchange="document.location.href=document.f_role.role.options[document.f_role.role.selectedIndex].value" class="form-control">
							<?php
							$roles = array(0=>'Athletes',1=>'Team leaders',2=>'Coaches',3=>'Administrators','all'=>'All participants');
							foreach($roles as $key=>$name) {
								echo '<option value="/settings/people?season='.$season.'&role='.$key.'"';
								if($role == $key) echo ' selected';
								echo '>'.$name.'</option>';
							}
							?>
							</select>
						</form>
					</div>
				</div>
			</div>
		</div>
		<div class="hidden-print alert alert-info">
			<h4><i class="fa fa-print"></i> This page is printer friendly</h4>
			Not all elements of this page will be printed - just the important ones.
		</div>
		<?php
		$people_fetcher_q = "SELECT * FROM tMembers";
		if($season != 'all') $people_fetcher_q .= " WHERE season='$season'";
		$people_fetcher = @mysqli_query($db, $people_fetcher_q);
		if(mysqli_num_rows($people_fetcher) == 0) {
			echo '<p class="lead">Looks like there isn\'t anyone that matches your search criteria.</p>';
		} else {
			# At least one person is listed in the season, now check all returned ranks.
			$people = array(); # an array of people to be displayed
			while($person = mysqli_fetch_array($people_fetcher)) {
				if($role != 'all') {
					$person_id = $person['user'];
					$person_checker = @mysqli_query($db, "SELECT * FROM users WHERE id=$person_id");
					$fullPerson = mysqli_fetch_array($person_checker);
					$person['name'] = $fullPerson['first'].' '.$fullPerson['last'];
					$person['role'] = $fullPerson['permissions'];
					if($fullPerson['permissions'] == $role) {
						$people[] = $person; # add to array
					}
				} else {
					$person_id = $person['user'];
					$person_checker = @mysqli_query($db, "SELECT * FROM users WHERE id=$person_id");
					$fullPerson = mysqli_fetch_array($person_checker);
					$person['name'] = $fullPerson['first'].' '.$fullPerson['last'];
					$person['role'] = $fullPerson['permissions'];
					$people[] = $person;
				}
			}
			
			if(empty($people)) {
				echo '<p class="lead">Looks like there isn\'t anyone that matches your search criteria.</p>';
			} else {
				echo '<table class="table table-striped table-hover">
				<thead>
				<tr>
				<th>Name</th>
				<th>Season</th>
				<th>Prediction</th>
				<th>Division</th>
				<th class="hidden-print">Actions</th>
				</tr>
				</thead>
				<tbody>';
				foreach($people as $person) {
					echo '<tr>
					<td>'.$person['name'].'</td>
					<td>'.$person['season'].' ('.$teams[$person['team']].')</td>
					<td>'.$person['prediction'].'</td>
					<td>'.$divisions[$person['division']].'</td>
					<td class="hidden-print">edit - drop - permissions</td>
					</tr>';
				}
				echo '</tbody></table>';
			}
		}
		?>
	</div>
</div>
<?php
include('../../php/foot.php');
?>