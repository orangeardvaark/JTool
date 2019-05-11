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
	if (!$authconn = auth_connect()) die(json_encode(array('error' => "DB Error. Did your session time out?", 'post'=>$_POST, 'diag' => sqlsrv_errors())));
	
	// Get vars
	$deets = validate_name_pass();
	$password = $deets['password'];
	$account = $deets['account'];
	
	// No duplicates!
	if (!$deets['uid']) die(json_encode(array('error' => "That user doesn't seem to exist. Please check your spelling and try again.", 'post'=>$_POST)));
	// Problems with the input?
	if ($deets['error']) die(json_encode(array('error' => $deets['error'], 'post'=>$_POST)));
		
	// Test the password
	$hash = bin2hex(game_hash_password($account, $password));
	// USE PDO style query because we don't trust user input
	$result = sqlsrv_query(
		$authconn,
		"SELECT * FROM dbo.user_auth WHERE account=? AND password=CONVERT(BINARY(128),?)",
		array($account,$hash)
	);
	
	// A result means it passed
	if (sqlsrv_has_rows($result))
	{
		$_SESSION['uid'] = $deets['uid'];
		
		// Get details on this user
		$result = sqlsrv_query(
			$authconn,
			"SELECT * FROM dbo.user_account WHERE uid=?",
			array($deets['uid'])
		);

		// last check, just in case
		if (sqlsrv_has_rows($result)) 
		{
			$row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC);
			$_SESSION['account'] = $row['account'];
			if (in_array($_SESSION['account'],$admins))
				$_SESSION['admin'] = true;
			$_SESSION['last_login'] = $row['last_login'];
			$_SESSION['last_ip'] = $row['last_ip'];
			die(json_encode(array('result' => 1)));
		}
		else
			die(json_encode(array('error' => "There was a problem logging in. Definitely going to need to talk to the admin about this.")));
	}
	else
		die(json_encode(array('error' => "Did that look like the right password to you? The database doesn't think so :P")));
?>