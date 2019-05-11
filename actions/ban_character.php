<?php
	// ***********************
	// AJAX standard stuff
	
	// Start the session so we have access to our logged in user vars
	@session_start();
	// Using the db... duh
	require_once('../db.php');
	// Always functions. Need those too
	require_once('../functions.php');

	// Get vars
	if (!$toon = get_toon($_POST['cid'])) die(json_encode(array('error' => "Couldn't retrieve character with Cid:".$_POST['cid'], 'post'=>$_POST, 'diag' => print_r($toon,1))));

	// Ownership check!
	if (!mine_or_admin($toon)) die(json_encode(array('error' => "You don't seem to own toon with Cid:".$_POST['cid'], 'post'=>$_POST, 'diag' => print_r($toon,1))));

	// Check for our DB connection
	if (!$dbconn = db_connect()) die(json_encode(array('error' => "DB Error. Did your session time out?", 'post'=>$_POST, 'diag' => sqlsrv_errors())));

	$cid = $_POST['cid'];
	
	$toon['Banned'] = ($toon['Banned'] ? 0 : 1);

	sqlsrv_query($dbconn,"UPDATE dbo.Ents SET Banned = ".$toon['Banned']." WHERE ContainerId = ".$cid);
	
	die(json_encode(array('result' => 1, 'new_hammer'=>ban_button($toon),'post'=>$_POST)));	
?>