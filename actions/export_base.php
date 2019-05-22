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
	
	// Get vars
	$cid = $_POST['cid'];

	$dbconn = db_connect();
	$result = sqlsrv_query(
		$dbconn,
		"SELECT SupergroupId,LeaderId,Name FROM dbo.Base INNER JOIN dbo.Supergroups ON dbo.Base.SupergroupId = dbo.Supergroups.ContainerId WHERE dbo.Base.ContainerId = ?",
		array($cid)
		);
	
	if ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC))
	{
		if (!mine_or_admin($row['LeaderId']))
			die(json_encode(array('error' => "This base is not for you to export!", 'post'=>$_POST)));
		$name = fix_sg_name($row['Name']);
	}
	// If there's no leader, you can still export if you're an admin
	else if (!ima_admin())
		die(json_encode(array('error' => "This base has no leader! Export not authorized!", 'post'=>$_POST)));

	$temp = export_base($row['SupergroupId'],$name);
	if (is_array($temp) && $temp['error']) 
		die(json_encode(array('error' =>  $temp['error'], 'post'=>$_POST)));
	
	die(json_encode(array('available_bases' => bases_available_for_import(), 'post'=>$_POST, 'diag'=>$temp)));
	
?>