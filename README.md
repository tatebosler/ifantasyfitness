iFantasyFitness | Train Competitively
===============
Welcome to iFantasyFitness. This is an online game, where players score points by exercising. The more you do, and the harder the activity, the more points you get. At the end of the game, the players with the most points win.

This project is open-source except for security requirements such as database credentials. Original source code is licensed under the MIT License.

**To install on your own site:**

1. Download a release.
2. Create a MySQL database.
3. Create a file `php/db.php` that looks like the code below. Replace the strings (in double quotes) with your actual database information.
4. Create a file `login/tokens.php` that looks like the code below, replacing the strings with access keys from Google and Facebook.
5. Run the MySQL commands listed below.

Your `php/db.php` file should look like this:

	<?php
    // Database Access Credentials - DO NOT share or commit elsewhere.
    const DB_HOST = "localhost";
    const DB_USER = "mysqli_user";
    const DB_PASSWORD = "extremely secure password";
    const DB_NAME = "iFF";
    $db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    ?>

And your `login/tokens.php` file:

	<?php
	# Facebook application details
	
	$fb_app_id = 'Facebook_App_ID';
	$fb_app_secret = 'Facebook_App_Secret';
	
	# Google application details
	
	$google_app_name = 'iFantasyFitness';
	$google_client_id = 'Google_Client_ID';
	$google_app_secret = 'Google_Client_Secret';
	$google_app_redirect = 'Google_OAuth_Redirect_URI';
	$google_app_key = 'Google_Browser_API_Key';
	?>

Security notes
==============
For security reasons, all tokens and keys in use are stored in two files:

- `php/db.php` for MySQL database credentials
- `login/tokens.php` for login-related API tokens and keys

It's up to you to get these tokens and credentials for yourself if you want to run iFF on your own server.

Bug reporting!
==============
If you find a bug please please please report it as an issue.

If it's a security hole, please send us an email, or fork the repository and send us a pull request with the fix.

APIs and libraries in use
=========================
iFantasyFitness currently uses the following libraries and APIs:

- Facebook Graph API and dependencies
- Google+ Sign-in API and dependencies
- Bootstrap 3.1.1 - http://getbootstrap.com
- Simplex Bootswatch theme for Bootstrap 3.1.1 - http://bootswatch.com/simplex 

Dependencies for Google, Facebook and other things are outlined in `login/composer.json`.

Note: The web server on which iFantasyFitness is hosted updates the dependencies of Composer, and Composer itself, on a regular basis. The files included in this repository may not be the latest versions.

MySQL Queries
=============
Run this query to set up your tables. These tables all belong in the same database.

	SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
	
	CREATE TABLE IF NOT EXISTS `dailygoals` (
	  `day` tinyint(3) unsigned NOT NULL,
	  `0` tinyint(3) unsigned NOT NULL,
	  `1` tinyint(3) unsigned NOT NULL,
	  `2` tinyint(3) unsigned NOT NULL,
	  PRIMARY KEY (`day`)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
	
	CREATE TABLE IF NOT EXISTS `globals` (
	  `name` varchar(64) NOT NULL,
	  `special` tinyint(4) NOT NULL,
	  `value` tinytext NOT NULL,
	  `display` tinytext NOT NULL,
	  PRIMARY KEY (`name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	
	CREATE TABLE IF NOT EXISTS `records` (
	  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	  `user` bigint(20) unsigned NOT NULL,
	  `team` int(10) unsigned NOT NULL,
	  `timestamp` bigint(20) unsigned NOT NULL,
	  `flag` tinyint(1) NOT NULL DEFAULT '1',
	  `include` tinyint(1) NOT NULL DEFAULT '1',
	  `run` double unsigned NOT NULL,
	  `run_p` double unsigned NOT NULL,
	  `run_team` double unsigned NOT NULL,
	  `run_team_p` double unsigned NOT NULL,
	  `rollerski` double unsigned NOT NULL,
	  `rollerski_p` double unsigned NOT NULL,
	  `walk` double unsigned NOT NULL,
	  `walk_p` double unsigned NOT NULL,
	  `hike` double unsigned NOT NULL,
	  `hike_p` double unsigned NOT NULL,
	  `bike` double unsigned NOT NULL,
	  `bike_p` double unsigned NOT NULL,
	  `swim` double unsigned NOT NULL,
	  `swim_p` double unsigned NOT NULL,
	  `paddle` double unsigned NOT NULL,
	  `paddle_p` double unsigned NOT NULL,
	  `strength` double unsigned NOT NULL,
	  `strength_p` double unsigned NOT NULL,
	  `sports` double unsigned NOT NULL,
	  `sports_p` double unsigned NOT NULL,
	  `altitude` float unsigned NOT NULL DEFAULT '1',
	  `total` double unsigned NOT NULL,
	  `comments` text NOT NULL,
	  `source` tinytext NOT NULL,
	  PRIMARY KEY (`id`),
	  KEY `user` (`user`)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;
	
	CREATE TABLE IF NOT EXISTS `seasons` (
	  `name` varchar(16) NOT NULL,
	  `reg_start` bigint(20) unsigned NOT NULL,
	  `reg_end` bigint(20) unsigned NOT NULL,
	  `comp_start` bigint(20) unsigned NOT NULL,
	  `comp_end` bigint(20) unsigned NOT NULL,
	  `display_name` tinytext NOT NULL,
	  `dailygoal_start` bigint(20) unsigned NOT NULL,
	  PRIMARY KEY (`name`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	
	CREATE TABLE IF NOT EXISTS `tData` (
	  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	  `name` tinytext NOT NULL,
	  `season` varchar(16) NOT NULL,
	  `captain` bigint(20) unsigned NOT NULL,
	  `total` double NOT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;
	
	CREATE TABLE IF NOT EXISTS `tMembers` (
	  `user` bigint(20) unsigned NOT NULL,
	  `team` bigint(20) unsigned NOT NULL,
	  `season` varchar(16) NOT NULL,
	  `prediction` smallint(5) unsigned NOT NULL,
	  `division` tinyint(3) unsigned NOT NULL,
	  `daily_goal` tinyint(3) unsigned NOT NULL,
	  `day_run` double NOT NULL,
	  `day_total` double NOT NULL,
	  `week_strength` double NOT NULL,
	  `week_sports` double NOT NULL,
	  `week_total` double NOT NULL,
	  `season_total` double NOT NULL,
	  `season_run` double NOT NULL
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	
	CREATE TABLE IF NOT EXISTS `users` (
	  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	  `profile` tinyint(1) NOT NULL DEFAULT '1',
	  `first` tinytext NOT NULL,
	  `last` tinytext NOT NULL,
	  `google` tinytext NOT NULL,
	  `twitter` tinytext NOT NULL,
	  `facebook` tinytext NOT NULL,
	  `grad` smallint(5) unsigned NOT NULL,
	  `gender` tinyint(1) NOT NULL,
	  `permissions` tinyint(3) unsigned NOT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

http://www.ifantasyfitness.com

Please star the repository and do all of the GitHub things!

DFTBA