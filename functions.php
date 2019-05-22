<?php

// This has to be here because it's used on the index.php page AND in some of the ajax stuff
	if (isset($_REQUEST['logout']))
		logout();

/*

// HTTPS Redirect  - This was in the original CohDBTool, but it was causing problems so OFF IT GOES!
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
*/

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

	// Quick helper - case insensitive inarray
	function in_arrayi($needle, $haystack) {
		return in_array(strtolower($needle), array_map('strtolower', $haystack));
	}
	
	function get_username_from_id($uid)
	{
		// Get AuthName based on UserID
		$result = sqlsrv_query(
			auth_connect(), 
			"SELECT account FROM dbo.user_account WHERE uid = ?",
			array($uid)
		);
		if (sqlsrv_has_rows($result)) 
		{
			$row = sqlsrv_fetch_array($result);
			return $row['account'];
		}
		else
			return 0;
	}
	
	function toon_id_from_name($name)
	{
		// Get AuthName based on UserID
		$result = sqlsrv_query(
			db_connect(), 
			"SELECT ContainerId FROM dbo.Ents WHERE Name = ?",
			array($name)
		);
		if (sqlsrv_has_rows($result)) 
		{
			$row = sqlsrv_fetch_array($result);
			return $row['ContainerId'];
		}
		else
			return 0;
	}
		
	function toon_name_from_id($cid)
	{
		// Get AuthName based on UserID
		$result = sqlsrv_query(
			db_connect(), 
			"SELECT Name FROM dbo.Ents WHERE ContainerId = ?",
			array($cid)
		);
		if (sqlsrv_has_rows($result)) 
		{
			$row = sqlsrv_fetch_array($result);
			return $row['Name'];
		}
		else
			return 0;
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
	$at_types[37071] = 'Arachnos Soldier';
	$at_types[37072] = 'Arachnos Widow';
	$at_types[68887] = 'Sentinal';
	
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
					<div class="coh_name">'.($row['name']).'</div>
				</div>
				<div class="shard_deets">External IP: <div class="ex_ip">'.$row['ip'].'</div></div>
				<img class="ch_ex_ip icon_button" src="graphics/icons/gears.png" data-id="'.$row['id'].'" title="Change external IP" />
				<img class="copy_ex_ip icon_button" src="graphics/icons/copy_ip.png" data-id="'.$row['id'].'" title="IPs are often the same. Click here to copy the External IP TO the Internal IP" />
				<div class="shard_deets">Internal IP: <div class="in_ip">'.$row['inner_ip'].'</div></div>
				<img class="ch_in_ip icon_button" src="graphics/icons/gears.png" data-id="'.$row['id'].'" title="Change external IP" />
				<img class="copy_in_ip icon_button" src="graphics/icons/copy_ip.png" data-id="'.$row['id'].'" title="IPs are often the same. Click here to copy the Internal IP TO the External IP" style="transform: scaleX(-1)"/>
				<img src="graphics/icons/trash.png" class="del_shard_button del_button icon_button right_icon_button" title="Delete '.($row['name']).'?"/>
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
		$result = sqlsrv_query(auth_connect(),"SELECT account, uid, last_login FROM dbo.user_account ORDER BY uid ASC");

		$users = array();
		$count = 0;
		while ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC)) 
		{					
			$users[$count]['uid'] = $row['uid'];
			$users[$count]['account'] = $row['account'];
			if ($row['last_login'] instanceof DateTime)
				$users[$count]['last_login'] = $row['last_login']->format('Y-m-d H:i:s');
			else 
				$users[$count]['last_login'] = 'None yet';
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
			while ($count++ < $max_tries && in_array($try_name, $used_names)) {
				$try_name = substr($try_name,0,11).rand( 100 , 999 );
			};
		}
		if ( strlen($try_name) < 3 || strlen($try_name) > 20 || $count == $max_tries)
			return array($try_name, strlen($try_name), $count);
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
			if (!sqlsrv_query($db_conn, "DELETE FROM dbo.$table WHERE ContainerId = ?",array($cid)))
				$error++;
			
		// Some data in other dbs
		$aucconn = auc_connect();
		if (!sqlsrv_query($aucconn, "DELETE FROM dbo.auction_ents WHERE ent_id = '$cid'" )) $error++;

		return $error;
	}
	
	// For reducing code and ease of reading the code, I made this function to simply return true or false if we have the right to mess with something
	function mine_or_admin($athing)
	{
		if ($_SESSION['admin']) return true;
		if (!is_array($athing))
			if ($athing == $_SESSION['uid']) return true;
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
				"SELECT account, uid, last_login FROM dbo.user_account WHERE uid = ?",
				array($row)
			);
			$row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC);
		}
		
		return '
			<div id="account_'.$row['uid'].'" class="account_tr deets_tr" data-uid="'.$row['uid'].'" data-account="'.$row['account'].'">
				<div id="account_'.$row['uid'].'" class="toon_bk" title="User ID ('.$row['uid'].') - Currently offline">
					<div class="coh_name">'.ucwords($row['account']).'</div>
					<div class="account_online"><img /></div>
				</div>
				<span class="last_login">Last Login: '.$row['last_login'].'</span>
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
		if ($row['PlayerType']) // villain
			$color = 'villain';
		if ($row['PraetorianProgress'] && $row['PraetorianProgress'] < 3) // going rogue
			$color = 'rogue';
		$tooltip = $row['Name'].':: Level '.$row['Level'].' '.ucwords($color).' '.$at_types[$row['Origin']].' '.$at_types[$row['Class']].' ContainerId: '.$row['ContainerId'];
		
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
			// This only happens when the character has borked and is probably dead.
			if (!$row['Origin'])
				$admin_controls = '<span>Dead character?</span>';
				
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
// Base related
	
	
	function fix_sg_name($name)
	{
		return str_replace('\s',"'",$name);
	}
	
	function print_bases()
	{
		$dbconn = db_connect();
		$result = sqlsrv_query($dbconn,"SELECT dbo.Base.ContainerId,Name,LeaderId FROM dbo.Base INNER JOIN dbo.Supergroups ON dbo.Base.SupergroupId = dbo.Supergroups.ContainerId");
		
		$to_return = '';
		while ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC))
		{
			if (mine_or_admin($row['LeaderId']))
			{
				$row['Name'] = fix_sg_name($row['Name']);
				$to_return .= '
					<div id="base_'.$row['ContainerId'].'" class="base_tr deets_tr" data-name="'.$row['Name'].'" data-cid="'.$row['ContainerId'].'" data-leader="'.$row['LeaderId'].'">
						<div class="toon_bk base_bk '.$color.' has_spinner" title="Leader name: '.toon_name_from_id($row['LeaderId']).'">
							<div class="coh_name">Base for: '.$row['Name'].'</div>
						</div>
						<img src="graphics/icons/copy.png" class="base_export_button right_icon_button" title="Export '.$row['Name'].'?"/>
						<div class="msg covers"></div>
						<div class="spinner"></div>
					</div>';
			}
		}
		if (!$to_return)
			$to_return .= '<div class="no_bases" >No bases yet!</div>';

		return $to_return;
	}
	
	
//***********************
// EXPORT AND IMPORT FUNCTIONS
	
	
	// Cleaner, more consistent, and compatible with other projects: using dbquery tool for export
	// Have to do it in a function because we need to call this when deleting users (save all their toons first)
	function export_toon($cid)
	{
		if (!$name = toon_name_from_id($cid))
			return array('error'=>'Could not retrieve character name.');
		$cmd = $_SESSION["DBQUERY"].' -getcharacter "'.$cid.'"> '.escapeshellarg(DOCROOT.'characters/'.$name.'.txt');
		$status = 0;
		$response = '';
		exec($cmd, $response, $status);
		if ($status != 0) 	// O means good in this case
			return array('error'=>'Command could not complete! ('.$cid.')'.$status);
		else
			return $cmd;
	}
	
	// CID is base id in the dbo.base table
	// Passing name too because we already looked it up for access checking anyway, so why check again?
	function export_base($sgID,$name)
	{
		$cmd = $_SESSION["DBQUERY"].' -dbquery -getbase "'.$sgID.'" > '.escapeshellarg(DOCROOT.'bases/'.$name.'.txt');
		$status = 0;
		$response = '';
		exec($cmd, $response, $status);
		if ($status != 0) 	// O means good in this case
			return array('error'=>'Command could not complete! ('.$sgID.')'.$status);
		else
			return $cmd;
	}
	
	// Why do this here? Same reason: for updating data based on ajax changes
	function available_for_import()
	{
		if (!file_exists(DOCROOT.'characters')) {
			mkdir(DOCROOT.'characters', 0777, true);
		}	

		$dir = new DirectoryIterator(DOCROOT."characters/");
		$to_return = '';
		foreach ($dir as $fileinfo) 
		{
			if (!$fileinfo->isDot()) 
			{
				$extension = pathinfo($fileinfo, PATHINFO_EXTENSION);
				if ($extension == 'txt')
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
	function bases_available_for_import()
	{
		if (!file_exists(DOCROOT.'bases')) {
			mkdir(DOCROOT.'bases', 0777, true);
		}	

		$dir = new DirectoryIterator(DOCROOT."bases/");
		$to_return = '<option value="" >Choose a base</option>';
		foreach ($dir as $fileinfo) 
		{
			if (!$fileinfo->isDot()) 
			{
				$extension = pathinfo($fileinfo, PATHINFO_EXTENSION);
				if ($extension == 'txt')
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
		
	
	// get an option list of supergroups - for base import
	function sg_options()
	{
		$to_return = '';
		
		$dbconn = db_connect();
		// Left join important here so we get results even if they don't have a base currently.
		$result = sqlsrv_query(
			$dbconn,
			"SELECT dbo.Supergroups.ContainerId as sgId, dbo.Base.ContainerId as baseId,Name,LeaderId FROM dbo.Supergroups LEFT JOIN dbo.Base ON dbo.Supergroups.ContainerId = dbo.Base.SupergroupId",
			);
		while ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC)) 
		{
			// Technically don't need this since only admins can import right now, but in case someone wants to change it later, here's a security check
			if (mine_or_admin($row['LeaderId']))
				$to_return .= '<option value="'.$row['sgId'].'" data-hasbase='.($row['baseId'] ? 1 : 0).'>'.fix_sg_name($row['Name']).'</option>';
		}
		return $to_return;
	}
				
		
	// Why do this here? Same reason: for updating data based on ajax changes
	function accounts_for_import()
	{
		$to_return = '';
		
		// Security check!
		if (!ima_admin() )
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