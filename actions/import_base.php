<?php

	// ***********************
	// AJAX standard stuff
	
	// Start the session so we have access to our logged in user vars
	@session_start();
	// Using the db... duh
	require_once('../db.php');
	// Always functions. Need those too
	require_once('../functions.php');
	// Check for our DB dbconnection
	if (!$dbconn = db_connect()) die(json_encode(array('error' => "DB Error. Did your session time out?", 'post'=>$_POST, 'diag' => sqlsrv_errors())));
	
	// If you're not admin, you don't belong here
	if (!ima_admin()) die(json_encode(array('error' => "You are not an admin. Buh-bye!", 'post'=>$_POST)));

	// Get vars
	$sgid = $_POST['sgid'];
	$name = $_POST['name'];
	
	if (!$sgid || !$name) die(json_encode(array('error' => "Lost track of which base to import. Please reload and try again.", 'post'=>$_POST)));

	$cmd = $_SESSION["DBQUERY"].' -dbquery -setbase '.$sgid.' '.escapeshellarg(DOCROOT.'bases/'.$name.'.txt');
	$status = 0;
	$response = '';
	exec($cmd, $response, $status);
	if ($status != 0) 	// O means good in this case
		die(json_encode(array('error' =>  $temp['error'], 'post'=>$_POST, 'diag'=> $status)));
	
// *****************************************************************************
// IMPORT CODE ABOVE THIS LINE
// The return statement below will work perfectly so long as the $ContainerID is set to the new character's ID

	// Have to return all bases -- easier than checking if an existing one was updated or a new one added. Just redraw the whole section
	die(json_encode(array('base_list' => print_bases(), 'diag'=>$cmd)));
	
?>