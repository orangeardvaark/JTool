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
	$which = $_POST['which'];		// What action will we do?
	
	// If you're not admin, you don't belong here
	if (!ima_admin()) die(json_encode(array('error' => "You are not an admin. Buh-bye!", 'post'=>$_POST)));


	if ('doit' == $which)
	{
		if (!$char_data = $_POST['char_data']) 
			die(json_encode(array('error' => "Who was I supposed to import again?")));
		
		if (!file_put_contents(DOCROOT.'characters/toon_import_temp.txt',$char_data))
			die(json_encode(array('error' => "Couldn't create temp file for import!")));
			
		$cmd = $_SESSION["DBQUERY"].' -putcharacter < '.escapeshellarg(DOCROOT.'characters/toon_import_temp.txt');
		$status = 0;
		$response = '';
		exec($cmd, $response, $status);
		
		// Before error correction, delete the temp file
		@unlink(DOCROOT.'characters/toon_import_temp.txt');
		
		if ($status == 0) 	// O means good in this case
		{
			// Get most recently inserted ID
			$dbconn = db_connect();
			$result = sqlsrv_query($dbconn,"SELECT IDENT_CURRENT('dbo.Ents')");
			$row = sqlsrv_fetch_array($result);
			$toon_id = $row[0];
				
			die(json_encode(array('result' => toon_row(get_toon($toon_id)), 'diag'=>print_r(get_toon($toon_id),1))));
		}
		else
			die(json_encode(array('error' => "Couldn't complete the import for some reason!", 'diag'=>$status)));
	}
	else if ('prepare' == $which)
	{
		$import_name = $_POST['name'];
		$temp = file_get_contents(DOCROOT.'characters/'.$import_name.'.txt');
		$temp = explode(PHP_EOL,$temp);
		$character_data = [];
		$lineno = 0;		// We need to keep it in sensible order... no idea what would happen if we changed it.
		foreach ($temp as $a_line)
		{
			// Why get rid of lines with double slashes? Partially because they appear to be comments and partially because they seem to be data that wouldn't be relevant to an insert (sg id, character id, etc).
			if (strpos($a_line,'//') === 0)
				continue;
			$split_at = strpos($a_line, ' ');
			$name = substr($a_line, 0, $split_at);
			$value = substr($a_line, $split_at+1);
			
			// Special - name collission check
			if ($name == 'Name')
			{
				$temp = $value;
				if (is_array($value = name_collision_check(trim($value,'"'))))
					die(json_encode(array('error' => "Could not resolve name collision.", 'post'=>$_POST, 'diag'=>print_r($value,1))));
			}				
				
			$character_data[$lineno] = array($name,$value);
			$lineno++;
			
		}
	die(json_encode(array('character_data' => json_encode($character_data))));
	}
	
	
?>