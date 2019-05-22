<?php
	// ***********************
	// AJAX standard stuff
	
	// Start the session so we have access to our logged in user vars
	@session_start();
	// Using the db... duh
	require_once('../db.php');
	// Always functions. Need those too
	require_once('../functions.php');

	// Only admins make accounts this way (unless it was called from the login page). If you're not admin, you don't belong here
	if (!ima_admin()) die(json_encode(array('error' => "You are not an admin. Buh-bye!", 'post'=>$_POST)));

	// Check for our DB connection
	if (!$authconn = auth_connect()) die(json_encode(array('error' => "DB Error. Did your session time out?", 'post'=>$_POST, 'diag' => sqlsrv_errors())));
	if (!$dbconn = db_connect()) die(json_encode(array('error' => "DB Error. Did your session time out?", 'post'=>$_POST, 'diag' => sqlsrv_errors())));
	
	// Get vars
	$uid = $_POST['uid'];
	$new_name = $_POST['new_name'];

	// Quick error check
	if (!ctype_alnum($new_name) || strlen($new_name) < 3 || strlen($new_name) > 14)
		die(json_encode(array('error' => "<li>Account name must be 3 to 14 characters; only letters and numbers.</li>")));
	

	// Collision check
	$result = sqlsrv_query(
		$authconn, 
		"SELECT account FROM dbo.user_account WHERE account = ?",
		array($new_name)
	);
	if (sqlsrv_has_rows($result)) 
		die(json_encode(array('error' => "That name already exists. Please try another", 'post'=>$_POST)));


	// Get AuthName based on UserID
	$result = sqlsrv_query(
		$authconn, 
		"SELECT account FROM dbo.user_account WHERE uid = ?",
		array($uid)
	);
	if (sqlsrv_has_rows($result)) 
	{
		$row = sqlsrv_fetch_array($result);
		$old_name = $row['account'];
	}
	else
		die(json_encode(array('error' => "I don't recognize that ID (".$uid."). Sorry.", 'post'=>$_POST, 'diag' => sqlsrv_errors())));


	// DO IT!
	// Update any toons
	if (!sqlsrv_query($dbconn,
		"UPDATE dbo.Ents SET AuthName = ? WHERE AuthId = ?",
		array($new_name,$uid)
	))
		die(json_encode(array('error' => "Could not update username for toons of ".$old_name.". Check the dbo.Ents table for weirdness", 'post'=>$_POST, 'diag' => print_r($error,1))));
	
	
	// Update user tables
	if (!sqlsrv_query($authconn,
		"UPDATE dbo.user_account SET account = ? WHERE uid = ?",
		array($new_name,$uid)
	))
		die(json_encode(array('error' => "Could not update username in the dbo.user table. Check dbo.user and dbo.user_auth to make sure the new name is listed.", 'post'=>$_POST, 'diag' => print_r($error,1))));
	
	if (!sqlsrv_query($authconn,
		"UPDATE dbo.user_auth SET account = ? WHERE account = ?",
		array($new_name,$old_name)
	))
		die(json_encode(array('error' => "Could not update username in the dbo.user_auth table. Check dbo.user and dbo.user_auth to make sure the new name is listed.", 'post'=>$_POST, 'diag' => print_r($error,1))));

	// Success if it got this far
	die(json_encode(array('user_row' => print_user($uid), 'user_toons'=> print_toons_for($uid,$new_name), 'user_options' => accounts_for_import(), 'post'=>$_POST)));
?>