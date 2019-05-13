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
	$import_name = $_POST['name'];
	$authID = $_POST['uid'];
	
	if (!$import_name || !$authID) die(json_encode(array('error' => "Who was I supposed to import again?")));

// I used to let users do this, but that wasn't super smart so now it's just admins	
//	// Ownership check!
//	if (!mine_or_admin(array('uid'=>$authID))) die(json_encode(array('error' => "Did you try to import a toon to someone else's account? Naughty.")));

	// If you're not admin, you don't belong here
	if (!ima_admin()) die(json_encode(array('error' => "You are not an admin. Buh-bye!", 'post'=>$_POST)));
				
	// Get AuthName based on AuthId
	// Have to use authtable because new characters won't be listed in the regular db yet.
	$result = sqlsrv_query(
		auth_connect(),
		"SELECT account FROM dbo.user_account WHERE uid = ?",
		array($authID)
	);
	if (!sqlsrv_has_rows($result)) die(json_encode(array('error' => "DB Error: ".$authID)));
	$row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC);
	$authName = ucwords($row['account']);
		
//	die(json_encode(array('error' => "test".$import_name.'::'.$authID.'::'.$authName)));
		
		
		
		
		
		
//*************************************************************************************************
// ALL CHECKS AND PREPARATION ARE DONE, NOW DO THE THING:	
// The IMPORT/EXPORT is complex so I left this to better people than me.	
	
	
	if (!file_exists(DOCROOT.'characters')) {
		mkdir(DOCROOT.'characters', 0777, true);
	}	
	
	// UNZIP CHARACTER
	$zip = new ZipArchive;
	$res = $zip->open(DOCROOT.'characters/'.$import_name.'.zip');
	if ($res === TRUE) 
	{
		$zip->extractTo(DOCROOT.'characters/'.$import_name.'/');
		$zip->close();
	} 
	else 
	{
		die(json_encode(array('error' => "Unzip error!")));
	}
		
	$character = DOCROOT."characters/$import_name/";
		
	
	// IMPORT Ents DATA
	$fileinfo = "Ents_0.json";
	$openJSON = file_get_contents($character.$fileinfo);
	//$openJSON = str_replace("null","0",$openJSON);
	$data = json_decode($openJSON, true);
	
		// Verify the name is unique
	if (!$try_name = name_collision_check($data['Name']))
		die(json_encode(array('error' => "Toon name must be unique and 3 to 14 characters; only letters and numbers.", 'post'=>$_POST, 'diag' => $try_name)));


		// NEED TO WORK OUT INSERTING THE FOLLOWING (LastActive, ChatBanExpire, MemberSince, DateCreated, IsSlotLocked)
		$entsDefaultArray = array('Active', 'AuthId', 'AuthName', 'Name', 'StaticMapId', 'MapId', 'PosX', 'PosY', 'PosZ', 'OrientP', 'OrientY', 'OrientR', 'TotalTime', 'LoginCount', 'AccessLevel', 'DbFlags', 'Locale', 'GurneyMapId', 'TitleCommon', 'TitleOrigin', 'MouseSpeed', 'TurnSpeed', 'TopChatFilter', 'BotChatFilter', 'ChatSendChannel', 'KeyProfile', 'KeybindCount', 'FriendCount', 'Rank', 'TimePlayed', 'TaskForceMode', 'BodyType', 'BodyScale', 'BoneScale', 'ColorSkin', 'Motto', 'Description', 'CurrentTray', 'CurrentAltTray', 'ChatDivider', 'SpawnTarget', 'Class', 'Origin', 'Level', 'ExperiencePoints', 'ExperienceDebt', 'InfluencePoints', 'HitPoints', 'Endurance', 'ChatFontSize', 'UniqueTaskIssued', 'TitleSpecial', 'TitlesChosen', 'TitleSpecialExpires', 'AuthUserData', 'UiSettings', 'ShowSettings', 'NPCCostume', 'Banned', 'NumCostumeSlots', 'SuperPrimary', 'SuperSecondary', 'CurrentCostume', 'SuperPrimary2', 'SuperSecondary2', 'SuperTertiary', 'SuperQuaternary', 'FxSpecial', 'FxSpecialExpires', 'CsrModified', 'Gender', 'NameGender', 'PlayerType', 'Prestige');
		$entsNonNULLArray = array();
		$entsValues = array();
				
		foreach($entsDefaultArray as $item)
		{
			if ($item == "AuthId")
			{
				$entsNonNULLArray[] = "AuthId";
				$entsValues[] = $authID;
			}
			else if ($item == "AuthName")
			{
				$entsNonNULLArray[] = "AuthName";
				$entsValues[] = $authName;
			}				
			else if ($item == "Name")
			{
				$entsNonNULLArray[] = "Name";
				$entsValues[] = $try_name;
			}				
			else if (!is_null($data[$item]))
			{
				$entsNonNULLArray[] = $item;
				$entsValues[] = $data[$item];
			}
		}
				
		foreach ($entsValues as &$value)
		{
			$value = "'".$value."'";
		}
		unset($value);
				
		$columnsEnts = implode(",", $entsNonNULLArray);
		$valuesEnts = implode(",", $entsValues);

		// This query is a bit complex. Uncomment this line to see what it's doing before it actuallyd oes it.
	//	die(json_encode(array('error' => "Insert Debug. Check the console for the query", 'diag' => "INSERT INTO dbo.Ents (".$columnsEnts.") VALUES (".$valuesEnts.")")));

		$insertEntsQuery = "INSERT INTO dbo.Ents ($columnsEnts) VALUES ($valuesEnts)";
		sqlsrv_query($dbconn, $insertEntsQuery);

	
	// Get ContainerId from recently inserted Ents
	$query = "SELECT ContainerId FROM dbo.Ents WHERE Name = '$try_name'";
	$result = sqlsrv_query($dbconn, $query);
	if (sqlsrv_has_rows($result)) 
	{
		$row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC);
		$ContainerID = $row['ContainerId'];
	}
	else
		die(json_encode(array('error' => "Insert failed", 'diag' => "SELECT ContainerId FROM dbo.Ents WHERE Name = '$try_name'")));
	
	
	// IMPORT Ents2 DATA
	$fileinfo = "Ents2_0.json";
	$openJSON = file_get_contents($character.$fileinfo);
	//$openJSON = str_replace("null","0",$openJSON);
	$data = json_decode($openJSON, true);
	
			//NEED TO FIX LastDayJobsStart, NO NEED TO INSERT THE FOLLOWING? (AccSvrLock, RaidsId, LevelingPactsId, LeaguesId, HomeDBID, HomeDBID, HomeShard, RemoteDBID, VisitStartTime, HomeSGID, HomeLPID, ShardVisitorData, RemoteShard, LastTurnstileEventID, LastTurnstileMission, TurnstileTeamLock, LastTurnstileStartTime, NewFeaturesVersion, Passcode)
			$ents2DefaultArray = array('ContainerId', 'SubId', 'RespecTokens', 'PendingReward', 'PendingRewardVillian', 'PendingRewardLevel', 'TitleBadge', 'ChatSettings', 'PrimaryChatMinimized', 'MousePitch', 'UiSettings2', 'UserSendChannel', 'FreeTailorSessions', 'MapOptions', 'Notoriety', 'ChatBubbleTextColor', 'ChatBubbleBackColor', 'TitleTheText', 'DividerSuperName', 'DividerSuperMap', 'DividerSuperTitle', 'DividerEmailFrom', 'DividerEmailSubject', 'DividerFriendName', 'DividerLfgName', 'DividerLfgMap', 'ChatBeta', 'LfgFlags', 'Comment', 'TooltipDelay', 'UltraTailor', 'ArenaPaid', 'ArenaPaidAmount', 'ArenaPrizeAmount', 'Insight', 'CurrentAlt2Tray', 'MaxHitPoints', 'WisdomPoints', 'WisdomLevel', 'PvPSwitch', 'Reputation', 'VillainGurneyMapId', 'SkillsUnlocked', 'Rage', 'ExitMissionContext', 'ExitMissionSubHandle', 'ExitMissionCompoundPos', 'ExitMissionOwnerId', 'ExitMissionSuccess', 'TeamCompleteOption', 'TimeInSGMode', 'UpdateTeamTask', 'BuffSettings', 'RecipeInvBonus', 'RecipeInvTotal', 'SalvageInvBonus', 'SalvageInvTotal', 'AuctionInvBonus', 'AuctionInvTotal', 'UiSettings3', 'StoredSalvageInvBonus', 'StoredSalvageInvTotal', 'TrayIndexes', 'HideField', 'originalPrimary', 'originalSecondary', 'MouseScrollSpeed', 'ExperienceRest', 'CurBuild', 'LevelBuild0', 'LevelBuild1', 'LevelBuild2', 'LevelBuild3', 'LevelBuild4', 'LevelBuild5', 'LevelBuild6', 'LevelBuild7', 'PendingArchitectTickets', 'BuildChangeTime', 'BuildName0', 'BuildName1', 'BuildName2', 'BuildName3', 'BuildName4', 'BuildName5', 'BuildName6', 'BuildName7', 'ExitMissionPlayerCreated', 'ArchitectMissionsCompleted', 'PlayerSubType', 'InfluenceType', 'InfluenceEscrow', 'AutoAcceptAbove', 'AutoAcceptBelow', 'LevelAdjust', 'TeamSize', 'UpgradeAV', 'DowngradeBoss', 'PraetorianProgress', 'SpecialMapReturnData', 'IncarnateTimer0', 'IncarnateTimer1', 'IncarnateTimer2', 'IncarnateTimer3', 'IncarnateTimer4', 'IncarnateTimer5', 'IncarnateTimer6', 'IncarnateTimer7', 'IncarnateTimer8', 'IncarnateTimer9', 'TitleColor1', 'TitleColor2', 'AuthUserDataEx', 'SpecialReturnInProgress', 'CurrentRazerTray', 'RequiresGoingRogueOrTrial', 'DisplayAlignmentStatsToOthers', 'DesiredTeamNumber', 'LastAutoCommandRunTime', 'IsTeamLeader', 'PendingCertification0', 'PendingCertification1', 'PendingCertification2', 'PendingCertification3', 'HelperStatus', 'UiSettings4', 'MapOptionRevision', 'MapOptions2', 'SelectedContactOnZoneEnter', 'PendingCertificationGrant', 'TeamupTimer_ActivePlayer', 'ValidateCostume', 'NumCostumeStored', 'DoNotKick', 'HideOpenSalvageWarning', 'Absorb', 'hideStorePiecesState', 'cursorScale');
			$ents2NonNULLArray = array();
			$ents2Values = array();
					
			foreach($ents2DefaultArray as $item)
			{
				if ($item == "ContainerId")
				{
					$ents2NonNULLArray[] = "ContainerId";
					$ents2Values[] = $ContainerID;
				}			
				else if (!is_null($data[$item]))
				{
					$ents2NonNULLArray[] = $item;
					$ents2Values[] = $data[$item];
				}
			}
					
			foreach ($ents2Values as &$value)
			{
				$value = "'".$value."'";
			}
			unset($value);
					
			$columnsEnts2 = implode(",", $ents2NonNULLArray);
			$valuesEnts2 = implode(",", $ents2Values);
			
			//echo "<br><br>INSERT INTO cohdbtest.dbo.Ents2 ($columnsEnts2) VALUES ($valuesEnts2)<br><br>";
					
			$insertEnts2Query = "INSERT INTO dbo.Ents2 ($columnsEnts2) VALUES ($valuesEnts2)";
			sqlsrv_query($dbconn, $insertEnts2Query);
	
	
	// LOOP THROUGH NON ENT FILES
	$dir = new DirectoryIterator($character);
	foreach ($dir as $fileinfo) 
	{
		if (!$fileinfo->isDot()) 
		{
			$databaseToImportTo = strstr($fileinfo, '_', true);
			$openJSON = file_get_contents($character.$fileinfo);
			//$openJSON = str_replace("null","0",$openJSON);
			$data = json_decode($openJSON, true);
			
			// Making sure we exclude Ents since imported above, also not importing some other data that wouldn't be useful on another server.
			if(!in_array($databaseToImportTo, array('Ents','Ents2', 'ArenaEvents', 'ArenaPlayers', 'Base', 'BaseRaids', 'Email', 'Ignore', 'ItemOfPowerGames', 'ItemsOfPower', 'Leagues', 'LevelingPacts', 'QueuedRewardTables', 'SGRaidInfos', 'SgrpBadgeStats', 'SgrpCustomRanks', 'SgrpMembers', 'SgrpPraetBonusIDs', 'SgrpRewardTokens', 'SGTask', 'ShardAccounts', 'statserver_SupergroupStats', 'SuperGroupAllies', 'Supergroups', 'TaskForceContacts', 'TaskForceParameters', 'Taskforces', 'TaskForceSouvenirClues', 'TaskForceStoryArcs', 'TaskForceTasks', 'TeamLeaderIds', 'TeamLockStatus', 'Teamups', 'TeamupTask'), true))
			{
				$defaultArray = array();
				$nonNULLArray = array();
				$valuesArray = array();
				
				// Get Table Columns
				foreach ($data as $key => $value) 
				{
					if (!is_numeric($key))
					{
						$defaultArray[] = $key;
						//$valuesArray[] = $value;
						//echo $fileinfo . " ". $key . " " . $value . "<br>";
					}
				}
				
				// Get Data that goes with the Columns
				foreach($defaultArray as $item)
				{
					if ($item == "ContainerId")
					{
						$nonNULLArray[] = "ContainerId";
						$valuesArray[] = $ContainerID;
					}		
					else if (!is_null($data[$item]))
					{
						$nonNULLArray[] = $item;
						$valuesArray[] = $data[$item];
					}
				}
				
				foreach ($valuesArray as &$value)
				{
					$value = "'".$value."'";
				}
				unset($value);
				
				$columns = implode(",", $nonNULLArray);
				$values = implode(",", $valuesArray);
				
				//echo "INSERT INTO cohdbtest.dbo.$databaseToImportTo ($columns) VALUES ($values)<br><br>";
				
				$insertQuery = "INSERT INTO dbo.$databaseToImportTo ($columns) VALUES ($values)";
				sqlsrv_query($dbconn, $insertQuery);
			}
		}
	}
	

	array_map('unlink', glob(DOCROOT."characters/$import_name/*"));
	@rmdir(DOCROOT."characters/$import_name/");
	
	
	
	
	
	
// *****************************************************************************
// IMPORT CODE ABOVE THIS LINE
// The return statement below will work perfectly so long as the $ContainerID is set to the new character's ID

	die(json_encode(array('result' => toon_row(get_toon($ContainerID)), 'diag'=>print_r(get_toon($ContainerID),1))));
	
	
	
	
	
	
	
?>