<?php 

	// ***********************
	// AJAX standard stuff
	
	// Start the session so we have access to our logged in user vars
	@session_start();
	// Using the db... duh
	require_once('../db.php');
	// Always functions. Need those too
	require_once('../functions.php');

	if (!$authconn = auth_connect()) die(json_encode(array('error' => "DB Error. Did your session time out?")));

	// Did they send us a UID?
	if (!$uid = $_POST['uid'])
	{
		// Must want to change our own UID
		$uid = $_SESSION['uid'];
		$account = $_SESSION['account'];
	}
	else
	{	
		// They gave us a uid, but is it THEIRS?
		if ($uid != $_SESSION['uid'] && !$_SESSION['admin'])
			// wtf? They're trying to change a password on an account that's not theirs and their not admin? Hellz nah.
			die(json_encode(array('error' => "What the frondz dude? Trying to change a password that's not yours?")));

		// Get the account name of the user who's pass we're changing (need it for the hash)
		$result = sqlsrv_query(
			$authconn,
			"SELECT * FROM dbo.user_account WHERE uid=?",
			array($uid)
		);
		$row = sqlsrv_fetch_array($result);
		$account = $row['account'];
	}
	
	
	$password = $_POST['password'];	


	if (!$uid) die(json_encode(array('error' => "We lost track of who you were trying to change the password for. Weird.")));

	// Verify that the new password is valid
	if (!ctype_print($password) || strlen($password) < 8 || strlen($password) > 16)
		die(json_encode(array('error' => "New password must be 8 to 16 characters", 'diag'=> print_r($_POST,1).$password.'::'.strlen($password))));

	$hash = bin2hex(game_hash_password($account, $password));

	if (sqlsrv_query(
		$authconn, 
		"UPDATE dbo.user_auth SET password = CONVERT(BINARY(128),?) WHERE account = ?", 
		array($hash, $account)
	))
		// No errors
		die(json_encode(array('result' => 1)));
	else
		die(json_encode(array('error' => "Couldn't do it Capn'! Password change failed.", 'diag'=> sql_debug("UPDATE dbo.user_auth SET password = CONVERT(BINARY(128),?) WHERE account = ?", array($hash, $account)))));

?>