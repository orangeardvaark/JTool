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
	$in_ip = $_POST['in_ip'];
	if (!filter_var($in_ip, FILTER_VALIDATE_IP)) die(json_encode(array('error' => "That internal IP doesn't look right. Check it and try again.", 'post'=>$_POST)));
	$ex_ip = $_POST['ex_ip'];
	if (!filter_var($ex_ip, FILTER_VALIDATE_IP)) die(json_encode(array('error' => "That external IP doesn't look right. Check it and try again.", 'post'=>$_POST)));


	$result = sqlsrv_query(
		$authconn, 
		"SELECT id FROM dbo.server WHERE id = ?",
		array($id)
	);	
	if (sqlsrv_has_rows($result)) 
		die(json_encode(array('error' => "That shard already exists. Try refreshing the page and see if it shows up.", 'post'=>$_POST)));

	//echo "Shard does not Exist!";
	sqlsrv_query(
		$authconn, 
		"INSERT INTO dbo.server (id, name, ip, inner_ip, ageLimit, pk_flag, server_group_id) VALUES (?,?,?,?, 0, 0, 1);",
		array($id,$servers[$id],$ex_ip,$in_ip)
	);
	sqlsrv_query(
		$authconn, 
		"INSERT INTO dbo.worldstatus (idx, status) VALUES ( ? , 1);",
		array($id)
	);
	
	die(json_encode(array('result' => 1, 'new_shard'=> print_shard($id), 'shard_options' => available_for_shards())));	
?>