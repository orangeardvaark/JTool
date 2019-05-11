<?php
	// ***********************
	// AJAX standard stuff
	
	// Start the session so we have access to our logged in user vars
	@session_start();
	// Using the db... duh
	require_once('../db.php');
	// Always functions. Need those too
	require_once('../functions.php');


	// If you're not admin, you don't belong here
	if (!ima_admin()) die(json_encode(array('error' => "You are not an admin. Buh-bye!", 'post'=>$_POST)));

	// Check for our DB connection
	if (!$authconn = auth_connect()) die(json_encode(array('error' => "DB Error. Did your session time out?", 'post'=>$_POST, 'diag' => sqlsrv_errors())));
	
	// Get vars
	$id = $_POST['id'];
	if (!$id) die(json_encode(array('error' => "Lost the shard ID so nothing has changed.", 'post'=>$_POST)));

	
	sqlsrv_query(
		$authconn,
		"DELETE FROM dbo.server WHERE id = ?",
		array($id)
	);
	sqlsrv_query(
		$authconn,
		"DELETE FROM dbo.worldstatus WHERE idx = ?",
		array($id)
	);
	
	die(json_encode(array('result' => 1, 'post'=>$_POST)));
?>