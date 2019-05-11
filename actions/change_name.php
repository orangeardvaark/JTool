<?php 

	// ***********************
	// AJAX standard stuff
	
	// Start the session so we have access to our logged in user vars
	@session_start();
	// Using the db... duh
	require_once('../db.php');
	// Always functions. Need those too
	require_once('../functions.php');
	
	
	// Check for our DB connection
	if (!$dbconn = db_connect()) die(json_encode(array('error' => "DB Error. Did your session time out?", 'post'=>$_POST, 'diag' => sqlsrv_errors())));
	
	// Get vars
	if (!$toon = get_toon($_POST['cid'])) die(json_encode(array('error' => "Couldn't retrieve character with Cid:".$_POST['cid'], 'post'=>$_POST, 'diag' => print_r($toon,1))));

	// Ownership check!
	if (!mine_or_admin($toon)) die(json_encode(array('error' => "You don't seem to own toon with Cid:".$_POST['cid'], 'post'=>$_POST, 'diag' => print_r($toon,1))));
	
	$containerID = $_POST['cid'];
	$new_name = $_POST['new_name'];

	if (!$try_name = name_collision_check($new_name))
		die(json_encode(array('error' => "Toon name must be unique and 3 to 14 characters; only letters and numbers.", 'post'=>$_POST, 'diag' => $try_name)));
		
	if (sqlsrv_query(
		$dbconn, 
		"UPDATE dbo.Ents SET Name = ? WHERE ContainerId = ?", 
		array($try_name, $containerID)
	)) 
	{
		// Update our toon data then return a replacement row with the right name
		$toon['Name'] = $try_name;
		die(json_encode(array('result' => toon_row($toon), 'post'=>$_POST)));
	}
	else
		die(json_encode(array('error' => "Couldn't do it Capn'! Password change failed.", 'diag'=> sql_debug("UPDATE dbo.user_auth SET password = CONVERT(BINARY(128),?) WHERE account = ?", array($hash, $account)))));

?>