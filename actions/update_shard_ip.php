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
	$which = $_POST['which'];
	if ('ex' != $which && 'in' != $which) die(json_encode(array('error' => "Which IP were we changing again?", 'post'=>$_POST)));
	$to = $_POST['to'];
	if (!filter_var($to, FILTER_VALIDATE_IP)) die(json_encode(array('error' => "That IP doesn't look right. Check it and try again.", 'post'=>$_POST)));
	
	if ('in' == $which)
		$query = "UPDATE dbo.server SET [inner_ip] = ? WHERE [id] = ?;";
	else
		$query = "UPDATE dbo.server SET [ip] = ? WHERE [id] = ?;";
	$result = sqlsrv_query(
		$authconn, 
		$query,
		array($to,$id)
	);
	die(json_encode(array('result' => 1)));
?>