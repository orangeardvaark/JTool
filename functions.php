<?php

// This has to be here because it's used on the index.php page AND in some of the ajax stuff
	if (isset($_REQUEST['logout']))
		logout();


/* HTTPS Redirect */
if (!(isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || 
   $_SERVER['HTTPS'] == 1) ||  
   isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&   
   $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'))
{
   $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
   header('HTTP/1.1 301 Moved Permanently');
   header('Location: ' . $redirect);
   exit();
}


//***********************
// HELPER FUNCTIONS AND SUCH

	// Too many path complications. This is to keep everything clear
	define("DOCROOT", __DIR__.'/');

	// Am I admin or not.
	// In it's own function in case we need to change how this works later
	function ima_admin()
	{
		return ($_SESSION['admin'])?1:0;
	}

	// Array to translate the goofy db codes to real words
	$at_types[1] = 'Blaster';
	$at_types[2] = 'Controller';
	$at_types[3] = 'Defender';
	$at_types[4] = 'Scrapper';
	$at_types[5] = 'Tanker';
	$at_types[6] = 'Science';
	$at_types[7] = 'Mutation';
	$at_types[8] = 'Magic';
	$at_types[9] = 'Technology';
	$at_types[10] = 'Natural';
	$at_types[7028] = 'Peacebringer';
	$at_types[7076] = 'Warshade';
	$at_types[9198] = 'Mastermind';
	$at_types[9200] = 'Stalker';
	$at_types[9201] = 'Brute';
	$at_types[9700] = 'Dominator';
	$at_types[10929] = 'Corruptor';
	
	$servers = [
		1 => "Paragon",
		2 => "Cryptic",
		3 => "Bree",
		4 => "Undying",
		5 => "Phoenix",
		6 => "Rebirth",
		7 => "Resurgence",
		8 => "Titan",
		9 => "Unity",
		10 => "Torchbearer",
		11 => "Unstoppable",
		12 => "Perseverant",
		13 => "Indomitable",
		14 => "Timeless",
		15 => "Everlasting",
		16 => "Excelsior"
	];	
	
//***********************
// SERVER FUNCTIONS
	
	// Function to write user. In a function for AJAX support
	// For ease, allows a user row or just id for input
	function print_shard($row)
	{
		if (!is_array($row))	// It's an id
		{
			$authconn = auth_connect();
			$result = sqlsrv_query(
				$authconn,
				"SELECT * FROM dbo.server WHERE id = ?",
				array($row)
			);
			$row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC);
		}
	
		return '
			<div class="shard_tr deets_tr" id="shard_'.$row['id'].'" data-id="'.$row['id'].'" data-name="'.$row['name'].'">
				<div class="toon_bk">
					<div class="coh_name">'.ucwords($row['name']).'</div>
				</div>
				<div class="shard_deets">External IP: <div class="ex_ip">'.$row['ip'].'</div></div>
				<img class="ch_ex_ip icon_button" src="graphics/icons/gears.png" data-id="'.$row['id'].'" title="Change external IP" />
				<img class="copy_ex_ip icon_button" src="graphics/icons/copy_ip.png" data-id="'.$row['id'].'" title="IPs are often the same. Click here to copy the External IP TO the Internal IP" />
				<div class="shard_deets">Internal IP: <div class="in_ip">'.$row['inner_ip'].'</div></div>
				<img class="ch_in_ip icon_button" src="graphics/icons/gears.png" data-id="'.$row['id'].'" title="Change external IP" />
				<img class="copy_in_ip icon_button" src="graphics/icons/copy_ip.png" data-id="'.$row['id'].'" title="IPs are often the same. Click here to copy the Internal IP TO the External IP" style="transform: scaleX(-1)"/>
				<img src="graphics/icons/trash.png" class="del_shard_button del_button icon_button right_icon_button" title="Delete '.ucwords($row['name']).'?"/>
				<div id="shard_msg_'.$row['id'].'" class="msg covers"></div>
				<div id="shard_spinner_'.$row['id'].'" class="spinner"></div>
			</div>
		';
	}	

	// Why do this here? Same reason: for updating data based on ajax changes
	function available_for_shards()
	{
		// Stores all server options
		global $servers;
		$used = array();
		$to_return = '';
		
		$authconn = auth_connect();
		$result = sqlsrv_query($authconn,"SELECT * FROM dbo.server");
		if (sqlsrv_has_rows($result)) 
		while ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC)) 
			$used[] = $row['id'];

		foreach ($servers as $index=>$name)
		{
			if (in_array($index,$used)) continue;	// SKIP!
			$to_return .= '<option value="'.$index.'">'.$name.'</option>';
		}

		if ($to_return)
			return $to_return;
		else
			return '<option value=0>None yet!</option>';
	}
	
	function get_users()
	{
		$result = sqlsrv_query(auth_connect(),"SELECT account, uid FROM dbo.user_account ORDER BY uid ASC");

		$users = array();
		$count = 0;
		while ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC)) 
		{					
			$users[$count]['uid'] = $row['uid'];
			$users[$count]['account'] = $row['account'];
			$count++;
		}
		return $users;
	}
	
//***********************
// ACCOUNT FUNCTIONS
	
	function logout()
	{
		@sqlsrv_close($_SESSION['dbconn']);
		@sqlsrv_close($_SESSION['authconn']);
		$_SESSION = '';
		session_unset(); 
		session_destroy(); 	
		@session_start(); 
	}

	/* Checksum algorithm */
	function adler32($data)
	{
		$mod_adler = 65521;
		$a = 1;
		$b = 0;
		$len = strlen($data);
		for($index = 0; $index < $len; $index++)
		{
			$a = ($a + ord($data[$index])) % $mod_adler;
			$b = ($b + $a) % $mod_adler;
		}

		return ($b << 16) | $a;
	}

	/* Generate password hash */
	function game_hash_password($authname, $password)
	{
		$authname = strtolower($authname);
		$a32 = adler32($authname);
		$a32hex = sprintf('%08s', dechex($a32));
		$a32hex = substr($a32hex, 6, 2) . substr($a32hex, 4, 2) . substr($a32hex, 2, 2) . substr($a32hex, 0, 2);
		$digest = hash('sha512', $password . $a32hex, TRUE);
		return $digest;
	}

	// This was the cleanest way I could think of to do error checking for account creation for both the index page and the AJAX create funciton
	function validate_name_pass()
	{
		$account = strtolower($_POST["account"]);
		// Verify that account is valid
		if (!ctype_alnum($account) || strlen($account) < 3 || strlen($account) > 14)
			$error = "<li>Account name must be 3 to 14 characters; only letters and numbers.</li>";
		
		$password = $_POST["password"];
		// Verify that password is valid
		if (!ctype_print($password) || strlen($password) < 8 || strlen($password) > 16)
			$error .= "<li>Password must be 8 to 16 characters</li>";

		$authconn = auth_connect();
		
		// Quick check... Are they already a user
		$result = sqlsrv_query(
			$authconn,
			"SELECT * FROM dbo.user_account WHERE account=?",
			array($account)
		);		
		if (sqlsrv_has_rows($result))
		{
			$row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC);
			$uid = $row['uid'];
			$exists = 1;
		}
		
		return array('account'=>$account,'password'=>$password,'error'=>$error, 'exists'=>$exists,'uid'=>$uid);
	}


	// Add an account
	// Recieves an account name and a password from post vars, error checks them, checks for dups, then adds if able.
	// RETURNS 0 if the user couldn't be created, 'exists' if the user already exists, and an ID otherwise
	function add_account($account, $password)
	{
		$authconn = auth_connect();

		$hash = bin2hex(game_hash_password($account, $password));

		// Get Last UserID from user_account since MSSQL doesn't do Auto Increment????
		$query = "SELECT TOP 1 uid FROM dbo.user_account ORDER BY uid DESC";
		$result = sqlsrv_query($authconn, $query);
		if (sqlsrv_has_rows($result)) 
		{
			$row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC);
			$userID = $row['uid'];
			$id = $userID + 1;
		}
		else
			$id = 1;
		
		if (!$id) return 0;

		// Protect from injection attacks using PDO style inserts (? marks instead of values)
		$good = sqlsrv_query(
			$authconn,
			"INSERT INTO dbo.user_account (account, uid, forum_id, pay_stat) VALUES (?,?,?, 1014);",
			array($account, $id, $id))
		;
		$good = sqlsrv_query(
			$authconn,
			"INSERT INTO dbo.user_auth (account, password, salt, hash_type) VALUES (?, CONVERT(BINARY(128),?), 0, 1);",
			array($account, $hash))
		;
		$good = sqlsrv_query(
			$authconn,
			"INSERT INTO dbo.user_data (uid, user_data) VALUES (?, 0x0080C2E000D00B0C000000000CB40058);",
			array($id))
		;
		$good = sqlsrv_query(
			$authconn,
			"INSERT INTO dbo.user_server_group (uid, server_group_id) VALUES (?, 1);",
			array($id))
		;
		
		return $id;
	}
	
	function name_collision_check($try_name)
	{
		$dbconn = db_connect();
		
		// Collision attempts
		$max_tries = 300;
		
		// Verify the name is unique
		$result = sqlsrv_query(
			$dbconn,
			"SELECT Name FROM dbo.Ents WHERE Name LIKE ?",
			array($try_name.'%')
		);

		// Is there a duplicate?
		if (sqlsrv_has_rows($result)) 
		{
			$used_names = array();
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC))
				$used_names[] = $row['Name'];
			$count = 0;
			do {
				$try_name = $try_name.rand( 100 , 999 );
			} while ($count++ < $max_tries && in_array($try_name, $used_names));
		}
		if (!ctype_alnum($try_name) || strlen($try_name) < 3 || strlen($try_name) > 14 || $count == $max_tries)
			return 0;
		else
			return $try_name;
	}
	
//***********************
// TOON FUNCTIONS
	
	// This assumes several checks have already taken place. For example, that the correct owner has done the delete or it's an admin calling it
	function del_toon($cid)
	{
		$db_conn = db_connect();
		// DELETE ANY TABLE THAT HAS "ContainerId", don't want anything to stick around!
		$tablesToDeleteFromArray = array('AccountInventory', 'Appearance', 'ArenaEvents', 'ArenaPlayers', 'AttackerParticipants', 'AttribMods', 'AutoCommands', 'BadgeMonitor', 'Badges', 'Badges01', 'Badges02', 'Base', 'BaseRaids', 'Boosts', 'CertificationHistory', 'ChatChannels', 'ChatTabs', 'ChatWindows', 'CombatMonitorStat', 'CompletedOrders', 'Contacts', 'CostumeParts', 'DefeatRecord', 'DefenderParticipants', 'Email', 'EventHistory', 'FameStrings', 'Friends', 'GmailClaims', 'GmailPending', 'Ignore', 'Inspirations', 'InvBaseDetail', 'InvRecipe0', 'InvRecipeInvention', 'InvSalvage0', 'InvStoredSalvage0', 'ItemOfPowerGames', 'ItemsOfPower', 'KeyBinds', 'Leagues', 'LevelingPacts', 'MapDataTokens', 'MapGroups', 'MapHistory', 'MARTYTracks', 'MinedData', 'MiningAccumulator', 'NewspaperHistory', 'Offline', 'Participants', 'PendingOrders', 'Petitions', 'PetNames', 'PowerCustomizations', 'Powers', 'QueuedRewardTables', 'RecentBadge', 'Recipes', 'Recipients', 'RewardTokens', 'RewardTokensActive', 'Seating', 'SGRaidInfos', 'SgrpBadgeStats', 'SgrpCustomRanks', 'SgrpMembers', 'SgrpPraetBonusIDs', 'SgrpRewardTokens', 'SGTask', 'ShardAccounts', 'SouvenirClues', 'SpecialDetails', 'Stats', 'statserver_SupergroupStats', 'StoryArcs', 'SuperCostumeParts', 'SuperGroupAllies', 'Supergroups', 'TaskForceContacts', 'TaskForceParameters', 'Taskforces', 'TaskForceSouvenirClues', 'TaskForceStoryArcs', 'TaskForceTasks', 'Tasks', 'TeamLeaderIds', 'TeamLockStatus', 'TeamupRewardTokensActive', 'Teamups', 'TeamupTask', 'TestDataBaseTypes', 'Tray', 'VisitedMaps', 'Windows', 'Ents2', 'Ents');
		
		$error = 0;
		// Loop through Tables and Delete Data
		foreach($tablesToDeleteFromArray as $table)
		{
			if (!sqlsrv_query($db_conn, "DELETE FROM dbo.$table WHERE ContainerID = ?",array($cid)))
				$error++;
		}
		return $error;
	}
	
	// For reducing code and ease of reading the code, I made this function to simply return true or false if we have the right to mess with something
	function mine_or_admin($athing)
	{
		if ($_SESSION['admin']) return true;
		// if athing is a toon, this will check for ownership
		if ($athing['AuthId'] == $_SESSION['uid']) return true;
		// Simple uid check
		if ($athing['uid'] == $_SESSION['uid']) return true;
		
		return false;
	}
	
	
	// Gets a toon's data from the db. This will return an array if successful and falst otherwise
	function get_toon($cid)
	{
		if (!$cid = intval($cid)) return false;
		if ($cid < 1 || $cid > 20000000) return false;	// Seriously though... 20 million is a big enough range for your toons isn't it?
			
		$dbconn = db_connect();
		// I had a join to do this, but it behaved weirdly so here's a weird fix.
		$result = sqlsrv_query(
			$dbconn,
			"SELECT * FROM dbo.Ents LEFT JOIN dbo.Ents2 ON dbo.Ents.ContainerId = dbo.Ents2.ContainerId WHERE dbo.Ents.ContainerId = ?",
			array($cid)
		);
		if (sqlsrv_has_rows($result)) 
			return sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC);

		return false;
	}
	
	// Function to write user. In a function for AJAX support
	// For ease, allows a user row or just id for input
	function print_user($row)
	{
		if (!is_array($row))	// It's an id
		{
			$authconn = auth_connect();
			$result = sqlsrv_query(
				$authconn,
				"SELECT account, uid FROM dbo.user_account WHERE uid = ?",
				array($row)
			);
			$row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC);
		}
		
		return '
			<div id="account_'.$row['uid'].'" class="account_tr deets_tr" data-uid="'.$row['uid'].'" data-account="'.$row['account'].'">
				<div id="account_'.$row['uid'].'" class="toon_bk" title="Currently offline">
					<div class="coh_name">'.ucwords($row['account']).'</div>
					<div class="account_online"><img /></div>
				</div>
				<img src="graphics/icons/trash.png" class="del_account_button del_button right_icon_button" data-uid="'.$row['uid'].'" title="Delete '.$row['account'].'?"/>
				<img src="graphics/icons/name_change.png" class="account_name_change right_icon_button" title="Change name for '.ucwords($row['account']).'?"/>
				<img src="graphics/icons/password.png" class="pass_change_button password_button right_icon_button" data-uid="'.$row['uid'].'" title="Change password for '.$row['account'].'?"/>
				<div class="msg covers"></div>
				<div class="spinner"></div>
			</div>
		';
	}
	
	// Set this way so it's easy at any time to draw or redraw all the toons for specific users. Also used as an easy way to keep admin toons on top of the list
	function print_toons_for($uid,$account)
	{
		$dbconn = db_connect();
		$result = sqlsrv_query(
			$dbconn,
			"SELECT dbo.Ents.ContainerId,AccessLevel,Banned,Active,Name,Class,Origin,Level,PlayerType,PraetorianProgress,AuthId FROM dbo.Ents LEFT JOIN dbo.Ents2 ON dbo.Ents.ContainerId = dbo.Ents2.ContainerId WHERE AuthId = ? ORDER BY ContainerId ASC",
			array($uid)
		);
		
		$to_return = '<div id="toons_for_'.$uid.'" class="toons_area" >';
		$to_return .= '<h4>'.$account.'</h4>';
		$to_return .= '<div class="toon_table" data-uid="'.$uid.'" data-account="'.$account.'" >';
		$to_return .= '<div class="no_toons" style="display:none">No toons yet!</div>';
		$toons = '';
			while ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC)) 
				$to_return .=  toon_row($row);
		$to_return .= '</div></div>';	
		return $to_return;
	}
	
	// Helper. Like all things, needed because of AJAX
	function ban_button($row)
	{
		if ($row['Banned'])
			return '<img src="graphics/icons/ban_on.png" class="toon_ban_button right_icon_button" title="Click to UN-ban '.$row['Name'].'" data-banned=1 />';
		else
			return '<img src="graphics/icons/ban_off.png" class="toon_ban_button right_icon_button" title="Click if you want to BAN '.$row['Name'].'" data-banned=0 />';
	}
	
	// Why make this a function? Because then if someone creates a new toon or imports, we can dynamically "draw" the new character in via JS
	// by returning this string from the ajax call!
	function toon_row($row)
	{
		$row['Level']++; // uses zero index so it's off by one
		$color = 'hero';
		if ($row['PlayerType'] && $row['PraetorianProgress'] && $row['PraetorianProgress'] < 3) // going rogue
			$color = 'rogue';
		else if ($row['PlayerType']) // villain
			$color = 'villain';
		$tooltip = $row['Name'].':: Level '.$row['Level'].' '.ucwords($color).' '.$at_types[$row['Origin']].' '.$at_types[$row['Class']];
		
		// Set some admin controls
			$access_options = '';
			for ($i=0;$i<12;$i++)
			{
				$selected = ($row['AccessLevel']==$i)? 'selected ':'';
				$access_options .= '<option value="'.$i.'" '.$selected.'>'.$i.'</option>';
			}
			$moveto_options = '';
			$moveto_options .= '<option value="" >Transfer to:</option>';
			$users = get_users();
			foreach ($users as $a_user)
				if ($a_user['uid'] != $row['AuthId'])
					$moveto_options .= '<option value="'.$a_user['uid'].'" >'.ucwords($a_user['account']).'</option>';
			
			if (ima_admin())
			$admin_controls = '
				<select class="toon_access_level" title="Controls what your toon can do. For example, I think Mods are 11">'.$access_options.'</select>
				<select class="toon_moveto" title="Choose another player to transfer this character to">'.$moveto_options.'</select>
				'.ban_button($row).'
			';
				
		return '
			<div id="toon_'.$row['ContainerId'].'" class="toon_tr deets_tr" data-name="'.$row['Name'].'" data-cid="'.$row['ContainerId'].'">
				<div class="toon_bk '.$color.' has_spinner" title="'.$tooltip.'">
					<div class="coh_name">'.$row['Name'].'</div>
					<div class="toon_level">Lvl ('.$row['Level'].')</div>
					<img src="graphics/icons/'.$row['Origin'].'.png" />
					<img src="graphics/icons/'.$row['Class'].'.png" data-active="'.$row['Active'].'" />
				</div>
				<img src="graphics/icons/trash.png" class="toon_del_button del_button right_icon_button" title="Delete '.$row['Name'].'?"/>
				'.$admin_controls.'
				<img src="graphics/icons/copy.png" class="toon_export_button right_icon_button" title="Export '.$row['Name'].'?"/>
				<img src="graphics/icons/name_change.png" class="toon_name_change right_icon_button" title="Change name for '.$row['Name'].'?"/>
				<div class="msg covers"></div>
				<div class="spinner"></div>
			</div>';
	}	
	
	
//***********************
// EXPORT AND IMPORT FUNCTIONS
	
	
	// By DarkSynopsis and largely untouched because it's complex
	function export_toon($containerID,$characterName)
	{
		$dbconn = db_connect();
		
		// Make Dirs
		$char_root = DOCROOT.'characters/';
		try {
			@mkdir($char_root, 0777, true);
			@mkdir($char_root.$characterName.'', 0777, true);
		} catch (Exception $e) {
			return array('error'=>"Could not create temp folders for export ".print_r($e,1));
		}
		
		// DUMP ANY TABLE THAT HAS "ContainerId", Who knows what we might need? :)
		$tablesToDumpArray = array('AccountInventory', 'Appearance', 'ArenaEvents', 'ArenaPlayers', 'AttackerParticipants', 'AttribMods', 'AutoCommands', 'BadgeMonitor', 'Badges', 'Badges01', 'Badges02', 'Base', 'BaseRaids', 'Boosts', 'CertificationHistory', 'ChatChannels', 'ChatTabs', 'ChatWindows', 'CombatMonitorStat', 'CompletedOrders', 'Contacts', 'CostumeParts', 'DefeatRecord', 'DefenderParticipants', 'Email', 'Ents', 'Ents2', 'EventHistory', 'FameStrings', 'Friends', 'GmailClaims', 'GmailPending', 'Ignore', 'Inspirations', 'InvBaseDetail', 'InvRecipe0', 'InvRecipeInvention', 'InvSalvage0', 'InvStoredSalvage0', 'ItemOfPowerGames', 'ItemsOfPower', 'KeyBinds', 'Leagues', 'LevelingPacts', 'MapDataTokens', 'MapGroups', 'MapHistory', 'MARTYTracks', 'MinedData', 'MiningAccumulator', 'NewspaperHistory', 'Offline', 'Participants', 'PendingOrders', 'Petitions', 'PetNames', 'PowerCustomizations', 'Powers', 'QueuedRewardTables', 'RecentBadge', 'Recipes', 'Recipients', 'RewardTokens', 'RewardTokensActive', 'Seating', 'SGRaidInfos', 'SgrpBadgeStats', 'SgrpCustomRanks', 'SgrpMembers', 'SgrpPraetBonusIDs', 'SgrpRewardTokens', 'SGTask', 'ShardAccounts', 'SouvenirClues', 'SpecialDetails', 'Stats', 'statserver_SupergroupStats', 'StoryArcs', 'SuperCostumeParts', 'SuperGroupAllies', 'Supergroups', 'TaskForceContacts', 'TaskForceParameters', 'Taskforces', 'TaskForceSouvenirClues', 'TaskForceStoryArcs', 'TaskForceTasks', 'Tasks', 'TeamLeaderIds', 'TeamLockStatus', 'TeamupRewardTokensActive', 'Teamups', 'TeamupTask', 'TestDataBaseTypes', 'Tray', 'VisitedMaps', 'Windows');
		
		$exported = 0;
		// Loop through Tables and Dump Data
		foreach($tablesToDumpArray as $table)
		{
			$query = "SELECT * FROM dbo.$table WHERE ContainerID = '$containerID'";
			$result = sqlsrv_query($dbconn, $query);
			
			if (sqlsrv_has_rows($result)) 
			{
				$rowNo = 0;
				
				while ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC)) 
				{
					$fileName = $char_root.$characterName."/".$table."_" . $rowNo . ".json";
					if (!$fp = fopen($fileName, 'w')) return array('error'=>"Couldn't open ".$fileName);
					if (!fwrite($fp, json_encode($row))) return array('error'=>"Couldn't write to ".$fileName);
					fclose($fp);
					$rowNo++;
				} 
				
				$exported++;
			}
// DO NOT UNCOMMENT -- I left it here as a warning to others. Not ALL the tables above have data in them for all characters.
// For example, not all characters have inventory. That means it may not have a result, but we should still continue.
// So no failing on this 
//			else
//				return array('error'=>"Could not export ".$table.". Export canceled (check the characters folder for leftovers that need to be deleted).");
		}
		if (!$exported)
			return array('error'=>"No character tables were found! Export canceled (check the characters folder for leftovers that need to be deleted).");
		
		try {
			// ZIP up the Character
			$rootPath = realpath($char_root.$characterName.'/');

			$zip = new ZipArchive();
			$zip->open($char_root.$characterName.'.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($rootPath),
				RecursiveIteratorIterator::LEAVES_ONLY
			);

			foreach ($files as $name => $file)
			{
				if (!$file->isDir())
				{
					$filePath = $file->getRealPath();
					$relativePath = substr($filePath, strlen($rootPath) + 1);

					$zip->addFile($filePath, $relativePath);
				}
			}

			$zip->close();
		} catch (Exception $e) {
			return array('error'=>"Could not create zip file for character ".print_r($e,1));
		}

	
		// Remove Directory Character was Stored in.
		try {
			array_map('unlink', glob($char_root.$characterName.'/*'));
			@rmdir($char_root.$characterName.'/');
		} catch (Exception $e) {
			return array('success'=>"Export complete, but could not delete temp files.".print_r($e,1));
		}
		
		return 1;
	}
		
	
	// Why do this here? Same reason: for updating data based on ajax changes
	function available_for_import()
	{
		$dir = new DirectoryIterator(DOCROOT."characters/");
		$to_return = '';
		foreach ($dir as $fileinfo) 
		{
			if (!$fileinfo->isDot()) 
			{
				$extension = pathinfo($fileinfo, PATHINFO_EXTENSION);
				if ($extension == 'zip')
				{
					$fileinfo = pathinfo($fileinfo, PATHINFO_FILENAME);
					$to_return .= '<option value="'.$fileinfo.'">'.$fileinfo.'</option>';
				}
			}
		}
		if ($to_return)
			return $to_return;
		else
			return '<option value=0>None yet!</option>';
	}
		
	// Why do this here? Same reason: for updating data based on ajax changes
	function accounts_for_import()
	{
		$to_return = '';
		
		// Security check!
		if (!$_SESSION['admin'] )
			$just_me = " WHERE uid='".$_SESSION['uid']."'";
		
		$conn = auth_connect();
		$query = "SELECT uid,account FROM dbo.user_account ".$just_me.' ORDER BY uid ASC';
		$result = sqlsrv_query($conn, $query);
		while ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC)) 
		{
			$to_return .= '<option value="'.$row['uid'].'">'.ucwords($row['account']).'</option>';
		}
		return $to_return;
	}
				
				
				
				
				
				
?>