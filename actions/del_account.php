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
	
	// Get vars
	$uid = $_POST['uid'];
	

	// Get AuthName based on UserID
	$result = sqlsrv_query(
		$authconn, 
		"SELECT account FROM dbo.user_account WHERE uid = ?",
		array($uid)
	);
	if (sqlsrv_has_rows($result)) 
	{
		$row = sqlsrv_fetch_array($result);
		$userName = $row['account'];
	}
	else
		die(json_encode(array('error' => "I don't recognize that ID. Sorry.", 'post'=>$_POST, 'diag' => sqlsrv_errors())));
	

	// It passed our PDO query before so it seems to be valid. We'll use shorthand queries from here
	
	// First, delete all characters belonging to that user
	// HOWEVER - as a safety precaution, we'll export them all first
	$dbconn = db_connect();
	$result = sqlsrv_query(
		$dbconn,
		"SELECT ContainerId,Name FROM dbo.Ents WHERE AuthId = ?",
		array($uid)
	);
	
	// See if this user has characters
	if (sqlsrv_has_rows($result)) 
	{
		while ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC)) 
		{
			// In an abundance of paranoia, export first
			$deets = export_toon($row['ContainerId'], $row['Name']);
			if ($deets['error'])
				die(json_encode(array('error' => "Could not safely export ".$row['Name'].". Delete cancelled.", 'post'=>$_POST, 'diag' => $deets['error'])));
		
			// NOW we can delete
			del_toon($row['ContainerId']);
		}
	}
		
	
	$error = array();
	$query1 = "DELETE FROM dbo.user_data WHERE uid = '$uid'";
	$query2 = "DELETE FROM dbo.user_server_group WHERE uid = '$uid'";
	$query3 = "DELETE FROM dbo.block_msg WHERE uid = '$uid'";
	$query3 = "DELETE FROM dbo.user_auth WHERE account = '$userName'";
	$query4 = "DELETE FROM dbo.user_account WHERE uid = '$uid'";
	if (!sqlsrv_query($authconn, $query1)) $error[0]=1;
	if (!sqlsrv_query($authconn, $query2)) $error[1]=1;
	if (!sqlsrv_query($authconn, $query3)) $error[2]=1;
	if (!sqlsrv_query($authconn, $query4)) $error[3]=1;


	
	if (!$error)
		die(json_encode(array('result' => 1, 'user_options' => accounts_for_import(), 'post'=>$_POST)));
	else
		die(json_encode(array('error' => "There was a problem deleting the user. At least one table opperation didn't complete", 'post'=>$_POST, 'diag' => print_r($error,1))));
?>