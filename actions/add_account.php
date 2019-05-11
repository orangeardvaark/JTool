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
	if (!$_SESSION['page'] == 'login' && !ima_admin()) die(json_encode(array('error' => "You are not an admin. Buh-bye!", 'post'=>$_POST)));

	// Check for our DB connection
	if (!$authconn = auth_connect()) die(json_encode(array('error' => "DB Error. Did your session time out?", 'post'=>$_POST, 'diag' => sqlsrv_errors())));
	
	// Get vars
	$deets = validate_name_pass();
	$password = $deets['password'];
	$account = $deets['account'];
	
	// No duplicates!
	if ($deets['uid']) die(json_encode(array('error' => "That account exists already!", 'post'=>$_POST)));
	// Problems with the input?
	if ($deets['error']) die(json_encode(array('error' => $deets['error'], 'post'=>$_POST)));
	
	if ($id = add_account($account,$password))
		die(json_encode(array('result' => 1, 'new_user' => print_user($id), 'new_user_toons'=> print_toons_for($id,$account), 'user_options' => accounts_for_import(), 'post'=>$_POST)));
	else
		die(json_encode(array('error' => "Something went wrong with account creation.", diag=> 'account:'.$account.' :: password: '.$password, 'post'=>$_POST)));
?>