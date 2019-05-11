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
	$move_to = $_POST['move_to'];
	
//die(json_encode(array('error' => "You don't seem to own toon with Cid:".$cid.'::'.$move_to, 'post'=>$_POST, 'diag' => print_r($toon,1))));
		
	// Get AuthID based on AuthName
	$result = sqlsrv_query(
		auth_connect(), 
		"SELECT account FROM dbo.user_account WHERE uid = ?", 
		array($move_to)
	);
	if (sqlsrv_has_rows($result)) 
	{
		$row = sqlsrv_fetch_array($result);
		$authName = ucwords($row['account']);
	}
	
	// Make the actual move
	sqlsrv_query(
		$dbconn, 
		"UPDATE dbo.Ents SET AuthId = ?, AuthName = ? WHERE ContainerId = ?",
		array($move_to,$authName,$cid)
	);

	die(json_encode(array('result' => toon_row(get_toon($cid)), 'post'=>$_POST, 'diag'=>print_r(get_toon($cid),1))));
?>