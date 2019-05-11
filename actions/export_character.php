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





//*************************************************************************************************
// ALL CHECKS AND PREPARATION ARE DONE, NOW DO THE THING:	
// The IMPORT/EXPORT is complex so I left this to better people than me.	
// ALSO - it is in a function because the "delete user" exports before deleting characters (for safety) so both this file and that need to call export
	
	$temp=export_toon($toon['ContainerId'], $toon['Name']);
	if ($temp['error']) 
		die(json_encode(array('error' => $temp['error'], 'post'=>$_POST)));
	
		
	
// *****************************************************************************
// IMPORT CODE ABOVE THIS LINE
// The return statement below will work perfectly so long as the $ContainerID is set to the new character's ID
	
	// Be sure to include the new list of importable characters in the return so we can update our import list on the fly!
	die(json_encode(array('result' => 1, 'import_list' => available_for_import(), 'post'=>$_POST)));
	
	
	
	
	
	
	
	
	
	
	
?>