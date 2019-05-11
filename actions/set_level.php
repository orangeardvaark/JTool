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

	$containerID = $_POST['cid'];
	// We have to recieve and return the old level because of AJAX race conditions. 
	// If we stored it in a JS var and the user changes another level while this one is still processing, there would be weird errors
	$o_accessLevel = $_POST['o_accesslevel'];
	if ($o_accessLevel < 0 || $o_accessLevel > 11) die(json_encode(array('error' => "Your level was out of range somehow. Weird", 'post'=>$_POST)));	
	$n_accessLevel = $_POST['n_accesslevel'];
	if ($n_accessLevel < 0 || $n_accessLevel > 11) die(json_encode(array('error' => "Your level was out of range somehow. Weird", 'post'=>$_POST)));	

	$result = sqlsrv_query(
		$dbconn,
		"UPDATE dbo.Ents SET AccessLevel = ? WHERE ContainerId = ?",
		array($n_accessLevel,$containerID)
	);
	if (sqlsrv_rows_affected($result) > 0)
		die(json_encode(array('result' => 1, 'post'=>$_POST)));	
	else
		die(json_encode(array('error' => "Failed at the finish line. Couldn't update the level", 'post'=>$_POST)));	
?>