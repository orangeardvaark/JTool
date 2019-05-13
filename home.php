<?php
	@session_start();		// We have to have this here to access sessions variables (which we need on this page before calling header

 	require_once("header.php"); 					// Writes the same top half of the page for every page	
	
	// Open a db connection for use throughout the file
	$dbconn = db_connect();
	// same with auth
	$authconn = auth_connect();
	$_SESSION['page'] = 'home';
	
//	print_r($_SESSION);
?>
	
	
	<style>
	#prompt_bk {
		position: fixed;
		display: flex;
		align-items: center;
		justify-content: center;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: rgba(0,0,0,.5);
		z-index: 100;
	}
	#prompt_form {
		display: inline-block;
		width: 500px;
	}
	#prompt_form input[type=button] {
		width: 150px;
	}
	#prompt_no {
		float: right;
	}
	#prompt_response {
		width: 100%;
	}
	</style>
	
	<div id="prompt_bk">
		<div id="prompt_form" class="greyblock">
			<h2 id="prompt_title"></h2>
			<p id="prompt_description"></p>
			<input id="prompt_response" type="text" placeholder="Enter your response here"/>
			<input id="prompt_yes" type=button class="green_button" value="Do it!"/>
			<input id="prompt_no" type=button class="orange_button" value="Cancel" onclick="promptOff()"/>
		</div>
	</div>
	
	<script>
		$('#prompt_bk').hide();		// Don't need it showing on page load. We could hide it with css, but then it would lose it's flex display when shown later


		// Just a function to centralize some common logic used when doing deletes or other name matching
		function promptNameMatch(name,prompt,outerArea)
		{
			// In case it's all numbers
			name += '';
			if (!prompt || !name) 
			{
				showMsg(outerArea,'Action cancelled');
				spinnerOff(outerArea);
				return 0;
			}
			if (name.toLowerCase() != prompt.toLowerCase())
			{
				showMsg(outerArea,name.toLowerCase()+" <==> "+prompt.toLowerCase()+" - No match");
				spinnerOff(outerArea);
				return 0;
			}
			return 1;
		}
		
		function promptOff()
		{
			$('#prompt_bk').hide();
			$('#prompt_yes').attr('value','Do it!').click(function(){});	// Set to defaults and remove the click event;
			$('#prompt_response,#prompt_title,#prompt_description').val('');	// set to defaults;
		}
		function promptOn(title,description,go_text,go_func)
		{
			$('#prompt_title').html(title);
			$('#prompt_description').html(description);
			$('#prompt_yes').attr('value',go_text).click(function(){
				go_func($('#prompt_response').val());
				promptOff();
			});
			$('#prompt_no').click(function(){
				go_func('');
				promptOff();
			});
			$('#prompt_bk').show();
			$('#prompt_response').focus();
		}
	</script>
	
	
	<input id=logout_button type="button" value="Logout" onclick="window.location='index.php?logout=1'">
	
	<h2>Hello <b><?php echo $_SESSION['account'];?></b>!</h2>
	<div id="user_deets_area" class="greyblock" >
		<?php 
			if ($_SESSION['last_login']) 
				echo '
					<p id="login_deets">You last logged in <b> 
					'.$_SESSION['last_login']->format('Y/m/d').'</b> from <b>
					'.$_SESSION['last_ip'].'
					</b></p>
				';
		?>
		<h3>Change Password</h3>
		<div id="ch_password_area">
			<input type="password" id="new_password" placeholder="Enter new pass here">
			<input type="submit" id="update_password_button" class="green_button" value="Make it so!">
			<div class="msg covers"></div>
			<div class="spinner"></div>
		</div>
	</div>
	<script>
		// ********************
		// SELF-PASSWORD CHANGE JS and AJAX
		//
		$(document).ready(function(){

			// Because it annoys me when enter doesn't submit
			$('#new_password').keyup(function(e){
				if (e.keyCode == 13)
					$('#update_password_button').click();
			});

			// Add a click event to the password change button
			$('#update_password_button').click(function() {
				// No point to this... just removes the glow which shows under the error message and bothered me
				$('#new_password').blur();

				// Attach a spinner to the password change block while we work
				spinnerOn('#ch_password_area');
				// Attempt a call to change the password
				$.ajax({
					url: 'actions/change_password.php',
					type: 'POST',
					data: { 
						password: $('#new_password').val(), 
					},
					dataType: 'json',
					success: function(msg)
					{
						if (ajaxErr(msg,'#ch_password_area')) return;
						
						// Show success message
						showMsg('#ch_password_area','Password changed successfully!');
						// Clear the password field
						$('#new_password').val('');
					},
					error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
				});		
			});
		});
	</script>
	
<?php if (ima_admin()){ ?>

	
	<h2>Shard Management</h2>
	<div class="blackblock">
		<?php		

			echo '<div id="shard_table">';
				$result = sqlsrv_query($authconn, "SELECT * FROM dbo.server ORDER BY id ASC");
				if (sqlsrv_has_rows($result)) 
				while ($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC)) 
					echo print_shard($row);			
			
			echo '</div>';

			echo '
				<div class=greyblock style="margin-top:30px">
					<h3 >Add Shard</h3>
					<p>Note that the available names are hard-coded so you have to choose from the below list. Also note that multiple shards require multiple servers! If you are running a home server, you should have one that matches what\'s in your server.cfg file and that\'s all. Just use this control to manage the IP addresses and otherwise leave it alone!</p>

					<div id="shard_form"  class="add_form">
						<label>Name</label>
						<select id="shard_id">'.available_for_shards().'</select>
						<input type="text" id="shard_ex_ip" placeholder="External IP" value=""/>
						<input type="text" id="shard_in_ip" placeholder="Internal IP" value=""/>

						<input id="add_shard_button" type="button" class="green_button mini" value="Add Shard">
						<div class="spinner"></div>
						<div class="msg covers"></div>
					</div>
				</div>
			';
		?>
	</div>
	<script>
	
		$(document).ready(function(){
			
			// New shards are PROBABLY going to all have the same IP information so lead with that
			$('#shard_ex_ip').val($('.ex_ip').first().text());
			$('#shard_in_ip').val($('.in_ip').first().text());
			
			// CHANG Shard in "document.on" format so it will work with dynamically added stuff
			$(document).on('click','.ch_ex_ip', function() {
				shardChangeID = $(this).data('id');
				shardChangeType = 'ex';
				// Attach a spinner
				spinnerOn('#shard_'+shardChangeID);
				promptOn('Change IP','Please enter a external IP for:<br> <b>'+$(this).closest('.shard_tr').find('.coh_name').text()+'('+$(this).closest('.shard_tr').find('.ex_ip').text()+')</b>','Change',changeShard);
			});			
			$(document).on('click','.ch_in_ip', function() {
				shardChangeID = $(this).data('id');
				shardChangeType = 'in';
				// Attach a spinner
				spinnerOn('#shard_'+shardChangeID);
				promptOn('Change IP','Please enter a external IP for:<br> <b>'+$(this).closest('.shard_tr').find('.coh_name').text()+'('+$(this).closest('.shard_tr').find('.in_ip').text()+')</b>','Change',changeShard);
			});
			$(document).on('click','.copy_ex_ip', function() {
				shardChangeID = $(this).data('id');
				shardChangeType = 'in';
				changeShard($(this).closest('.shard_tr').find('.ex_ip').text());
			});			
			$(document).on('click','.copy_in_ip', function() {
				shardChangeID = $(this).data('id');
				shardChangeType = 'ex';
				changeShard($(this).closest('.shard_tr').find('.in_ip').text());
			});
			function changeShard(promptResponse){
				// quick check to see if they gave a response
				// Checks for proper IP addresses are done on the backend
				if (!promptOK(promptResponse,'#shard_'+shardChangeID,'Shard Update')) return;
				
				$.ajax({
					url: 'actions/update_shard_ip.php',
					type: 'POST',
					data: { 
						id: shardChangeID,
						which: shardChangeType, 
						to: promptResponse
					},
					dataType: 'json',
					success: function(msg)
					{
						if (ajaxErr(msg,'#shard_'+shardChangeID)) return;
						
						// Show success message
						showMsg('#shard_'+shardChangeID,'IP changed successfully!');
						$('#shard_'+shardChangeID+' .'+shardChangeType+'_ip').text(promptResponse)
					},
					error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
				});		
			}
					
			// CHARACTER Delete in "document.on" format so it will work with dynamically added stuff
			$(document).on('click','.del_shard_button', function() {
				
				delShardName = $(this).closest('.shard_tr').data('name');
				delShardID = $(this).closest('.shard_tr').data('id');
				
				spinnerOn('#shard_'+delShardID);
				promptOn('Delete Shard','To delete...<br><h3 class="name_prompt_confirm">'+delShardName+'</h3>...type the name then click the green button to continue.','Yes, Delete',deleteShardCheck);

			});
			function deleteShardCheck(promptResponse){
				if (!promptNameMatch(delShardName,promptResponse,'#shard_'+delShardID))
					return;
				
				// Goodbye sucka!
				$.ajax({
					url: 'actions/del_shard.php',
					type: 'POST',
					data: { 
						id: delShardID, 
					},
					dataType: 'json',
					success: function(msg)
					{											
						if (ajaxErr(msg,'#shard_'+msg['post']['id'])) return;

						$('#shard_'+msg['post']['id']).remove();
						// Make sure to show or hide "NO TOONS" message now that the counts have changed
						// *********__________DDDD??????????????????>>>>>>>>>>checkToonCount();
					},
					error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
				});	
			}

	
			// ADD A shard
			$('#add_shard_button').click(function(){
				
				// Attach a spinner
				spinnerOn('#shard_form');
				// Attempt a call to change the password
				$.ajax({
					url: 'actions/add_shard.php',
					type: 'POST',
					data: { 
						id: $('#shard_id').val(), 
						ex_ip: $('#shard_ex_ip').val(), 
						in_ip: $('#shard_in_ip').val(), 
					},
					dataType: 'json',
					success: function(msg)
					{
						if (ajaxErr(msg,'#shard_form')) return;

						// Show success message
						showMsg('#shard_form','Done!');
						// Add the new user
						$('#shard_table').append(msg['new_shard']);
						// Update the "import character" select
						$('#shard_id').html(msg['shard_options']);
					},
					error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
				});						
			});
		});
		
	</script>	
	
	<h2>User Management</h2>
	<div class="blackblock">
		<?php

			$users = get_users();
			echo '<div id="account_table">';
			foreach ($users as $a_user)
				echo print_user($a_user);
			echo '</div>';	

			echo '
				<div class=greyblock style="margin-top:30px">
					<h3 >Add Account</h3>
					<p>Enter a name and password to manually create an account</p>
				
					<div id="account_form" class="add_form">
						<label for=account>Account</label>
						<input type=text name="account" maxlength=14 id="add_account" placeholder="Name"/>
						<label for=password>Password</label>
						<input type=text name="password" maxlength=16 id="add_password" placeholder="Password"/>
						<input id="add_account_button" type="button" class="green_button mini" value="Create">
						<div class="spinner"></div>
						<div class="msg covers"></div>
					</div>
				</div>
			';
		?>
	</div>
	<script>
	
		$(document).ready(function(){
			
			// Because it annoys me when enter doesn't submit
			$('#add_account,#add_password').keyup(function(e){
				if (e.keyCode == 13)
					$('#add_account_button').click();
			});
			
			
			// Delete user and all their toons.
			$(document).on('click','.del_account_button', function() {
				delUserID = $(this).data('uid');
				delUserName = $('#account_'+delUserID+' .coh_name').text();
				spinnerOn('#account_'+delUserID);
				promptOn('Delete User','If you really, REALLY sure you want to delete...<h3 class="name_prompt_confirm">'+delUserName+'</h3>... type their name as you see it here (without the quotes). <b>Note that any toons will be exported before delete just in case</b>.','Kill',killUser);
			});
			function killUser(promptResponse){
				if (!promptNameMatch(delUserName,promptResponse,'#account_'+delUserID))
					return;
				
				$.ajax({
					url: 'actions/del_account.php',
					type: 'POST',
					data: { 
						uid: delUserID,
					},
					dataType: 'json',
					success: function(msg)
					{
						if (ajaxErr(msg,'#account_'+delUserID)) return;

						// Delete from users area.
						$('#account_'+delUserID).remove();
						// Delete from toon list area.
						$('#toons_for_'+delUserID).remove();
						// Update the "import character" select
						updateUserOptions(msg['user_options']);
					},
					error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
				});		
			}
			
			
			// CHANG PASSWORD in "document.on" format so it will work with dynamically added stuff
			$(document).on('click','.pass_change_button', function() {
				passChangeUserID = $(this).data('uid');
				spinnerOn('#account_'+passChangeUserID);
				promptOn('Change Password','Please enter a new password for "'+$('#account_'+passChangeUserID+' .coh_name').text()+'"','Change',passChange);
				
			});
			function passChange(promptResponse){
				if (!promptOK(promptResponse,'#account_'+passChangeUserID,'Account Password Change')) return;
			
				$.ajax({
					url: 'actions/change_password.php',
					type: 'POST',
					data: { 
						uid: passChangeUserID,
						password: promptResponse, 
					},
					dataType: 'json',
					success: function(msg)
					{
						if (ajaxErr(msg,'#account_'+passChangeUserID)) return;

						// Show success message
						showMsg('#account_'+passChangeUserID,'Password changed successfully!');
						
					},
					error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
				});		
			}
		
		
		
			// ACCOUNT NAME CHANGE in "document.on" format so it will work with dynamically added stuff
			$(document).on('click','.account_name_change', function() {
				
				// We can use these vars safely since you can't change multiple names before the first request completes
				// So no chance of race conditions
				changeAccountName = $(this).closest('.account_tr').data('account');
				changeAccountUid = $(this).closest('.account_tr').data('uid');
				
				spinnerOn('#account_'+changeAccountUid);
				promptOn('Change Account Name','To change the name for "'+changeAccountName+'", enter the name and click the green button.','Yes, Change',changeNameCheck);

			});
			function changeNameCheck(promptResponse)
			{
				if (!promptOK(promptResponse,'#account_'+changeAccountUid,'Change Account Name')) return;

				$.ajax({
					url: 'actions/rename_account.php',
					type: 'POST',
					data: { 
						uid: changeAccountUid, 
						new_name: promptResponse, 
					},
					dataType: 'json',
					success: function(msg)
					{			
						if (ajaxErr(msg,'#account_'+changeAccountUid)) return;
					
						// update user area.
						$('#account_'+changeAccountUid).replaceWith(msg['user_row']);
						// update toons area.
						$('#toons_for_'+changeAccountUid).replaceWith(msg['user_toons']);
						checkToonCount();
						// Update the "import character" select
						updateUserOptions(msg['user_options']);

						showMsg('#account_'+changeAccountUid,"Done!");

					},
					error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
				});	
			}


			// Add an account
			$('#add_account_button').click(function(){
				
				// Attach a spinner
				spinnerOn('#account_form');
				// Attempt a call to change the password
				$.ajax({
					url: 'actions/add_account.php',
					type: 'POST',
					data: { 
						account: $('#add_account').val(), 
						password: $('#add_password').val(), 
					},
					dataType: 'json',
					success: function(msg)
					{
						if (ajaxErr(msg,'#account_form')) return;
						
						// Show success message
						showMsg('#account_form','Done!');
						// Add the new user
						$('#account_table').append(msg['new_user']);
						// Add the new user to the toon list too
						$('#toon_table').append(msg['new_user_toons']);
						checkToonCount();
						// Update the "import character" select
						updateUserOptions(msg['user_options']);

					},
					error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
				});						
			});
		});
		
	</script>
<?php } ?>	
	
	
	
	
	<h2>Toon Management</h2>
	<div class="blackblock">
		<?php
			echo '<div id="toon_table">';
				echo print_toons_for($_SESSION['uid'],$_SESSION['account']);

				if (ima_admin())
				{
					$users = get_users();
					foreach ($users as $a_user)
						if ($a_user['uid'] != $_SESSION['uid'])
							echo print_toons_for($a_user['uid'],$a_user['account']);
				}
			echo '</div>';

			if (ima_admin())
			echo '
				<div class=greyblock style="margin-top:30px">
					<h3>Import Toon</h3>
					<p>Choose from the list of available characters and which account it should go to, then click IMPORT</p>
				
					<div id="import_form"  class="add_form">
						<label>Name:</label>
						<select id="import_which">'.available_for_import().'</select>
						<label>Account:</label>
						<select id="import_account">'.accounts_for_import().'</select>
						<input id="import_button" type="button" class="green_button mini" value="Import Toon">
						<div class="spinner"></div>
						<div class="msg covers"></div>
					</div>
				</div>
			';
		?>
	</div>
	<script>
		// ********************
		// CHARACTER RELEVANT JS and AJAX
		//
		$(document).ready(function(){

			// CHARACTER EXPORT in "document.on" format so it will work with dynamically added stuff
			$(document).on('click','.toon_export_button', function() {
				
				cid = $(this).closest('.toon_tr').data('cid');
				spinnerOn(this,'.toon_tr');

				// Attempt a call to change the password
				$.ajax({
					url: 'actions/export_character.php',
					type: 'POST',
					data: { 
						cid: cid, 
					},
					dataType: 'json',
					success: function(msg)
					{
						// Why use msg cid? Because the cid var could change if someone clicks several exports in a row before this line executes
						// So the export code returns the cid of the character it completed so we don't lose track and get unpredictable behavior
						if (ajaxErr(msg,'#toon_'+msg['post']['cid'])) return;

						// Show success message
						showMsg('#toon_'+msg['post']['cid'],'Exported!');
						// Update our importable list with the new character
						$('#import_which').html(msg['import_list']);

					},
					error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
				});						
			});			
			
			<?php if (ima_admin()) { ?>
			// CHARACTER IMPORT
			$('#import_button').click(function() {
				
				// Attach a spinner
				spinnerOn('#import_form');
				// Attempt a call to change the password
				$.ajax({
					url: 'actions/import_character.php',
					type: 'POST',
					data: { 
						name: $('#import_which').val(), 
						uid: $('#import_account').val(), 
					},
					dataType: 'json',
					success: function(msg)
					{
						if (ajaxErr(msg,'#import_form')) return;

						// Show success message
						showMsg('#import_form','Done!');
						addTo = $('.toon_table[data-uid="'+$('#import_account').val()+'"]');
						$(addTo).append(msg['result']);
						// Make sure to show or hide "NO TOONS" message now that the counts have changed
						checkToonCount();

					},
					error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
				});						
			});
			<?php }	?>
			
			// CHARACTER Delete in "document.on" format so it will work with dynamically added stuff
			$(document).on('click','.toon_del_button', function() {
				
				// We can use these vars safely since you can't delete multiple characters before the first request completes
				// So no chance of race conditions
				delToonName = $(this).closest('.toon_tr').data('name');
				delToonCid = $(this).closest('.toon_tr').data('cid');
				
				spinnerOn('#toon_'+delToonCid);
				promptOn('Delete Toon','To delete...<br><h3 class="name_prompt_confirm">'+delToonName+'</h3>...type their name then click the green button to continue.','Yes, Delete',deleteCheck);

			});
			
			function deleteCheck(promptResponse){
				if (!promptNameMatch(delToonName,promptResponse,'#toon_'+delToonCid))
					return;
				
				// Goodbye sucka!
				$.ajax({
					url: 'actions/delete_character.php',
					type: 'POST',
					data: { 
						cid: delToonCid, 
					},
					dataType: 'json',
					success: function(msg)
					{						
						if (ajaxErr(msg,'#toon_'+delToonCid)) return;

						$('#toon_'+delToonCid).remove();
						// Make sure to show or hide "NO TOONS" message now that the counts have changed
						checkToonCount();

					},
					error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
				});	
			}

				
			<?php if (ima_admin()) { ?>



			// CHARACTER NAME CHANGE in "document.on" format so it will work with dynamically added stuff
			$(document).on('click','.toon_name_change', function() {
				
				// We can use these vars safely since you can't delete multiple characters before the first request completes
				// So no chance of race conditions
				changeToonName = $(this).closest('.toon_tr').data('name');
				changeToonCid = $(this).closest('.toon_tr').data('cid');
				
				spinnerOn('#toon_'+changeToonCid);
				promptOn('Change Toon Name','To change the name for "'+changeToonName+'", enter the name and click the green button.','Yes, Change',changeNameCheck);

			});
			function changeNameCheck(promptResponse)
			{
				if (!promptOK(promptResponse,'#toon_'+changeToonCid,'Change Toon Name')) return;

				// Goodbye sucka!
				$.ajax({
					url: 'actions/change_name.php',
					type: 'POST',
					data: { 
						cid: changeToonCid, 
						new_name: promptResponse, 
					},
					dataType: 'json',
					success: function(msg)
					{			
						if (ajaxErr(msg,'#toon_'+changeToonCid)) return;
					
						$('#toon_'+changeToonCid).replaceWith(msg['result']);
						showMsg('#toon_'+changeToonCid,"Done!");

					},
					error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
				});	
			}


			
			// CHANGE USERLEVEL
			var oldAccessLevel = 0;
			$(document).on('focus','.toon_access_level',function(){
				oldAccessLevel = $(this).val();
			});
			$(document).on('change','.toon_access_level',function(){
				newAccessLevel = $(this).val();
				cid = $(this).closest('.toon_tr').data('cid');
				
				// Attach a spinner
				spinnerOn('#toon_'+cid);
				// Attempt a call to change the password
				$.ajax({
					url: 'actions/set_level.php',
					type: 'POST',
					data: { 
						cid: cid, 
						o_accesslevel: oldAccessLevel,
						n_accesslevel: newAccessLevel
					},
					dataType: 'json',
					success: function(msg)
					{
						// Why use msg cid? Because the cid var could change if someone clicks several exports in a row before this line executes
						// So the export code returns the cid of the character it completed so we don't lose track and get unpredictable behavior
						if (ajaxErr(msg,'#toon_'+msg['post']['cid']))
						{
							// Set the level back
							$('#toon_'+msg['post']['cid']+'"] .toon_access_level').val(''+msg['post']['o_accesslevel']);
							return;
						}

						// Show success message
						showMsg('#toon_'+msg['post']['cid'],'Updated!');

					},
					error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
				});
			});			
			
			// TRANSFER character
			$(document).on('change','.toon_moveto',function(){
				moveTo = $(this).val();
				cid = $(this).closest('.toon_tr').data('cid');
				
				// Attach a spinner
				spinnerOn('#toon_'+cid);
				// Attempt a call to change the password
				$.ajax({
					url: 'actions/transfer_toon.php',
					type: 'POST',
					data: { 
						cid: cid, 
						move_to: moveTo,
					},
					dataType: 'json',
					success: function(msg)
					{
						if (ajaxErr(msg,'#toon_'+msg['post']['cid'])) return;

						// Show success message
						$('#toon_'+['post']['cid']).remove();
						addTo = $('#toon_'+msg['post']['move_to']+'"]');
						$(addTo).append(msg['result']);
						// Make sure to show or hide "NO TOONS" message now that the counts have changed
						checkToonCount();
						showMsg('#toon_'+msg['post']['cid'],'Moved!');

					},
					error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
				});
			});

			// BAN HAMMER!
			$(document).on('click','.toon_ban_button',function(){
				cid = $(this).closest('.toon_tr').data('cid');
				
				// Attach a spinner
				spinnerOn('#toon_'+cid);
				// Attempt a call to change the password
				$.ajax({
					url: 'actions/ban_character.php',
					type: 'POST',
					data: { 
						cid: cid, 
					},
					dataType: 'json',
					success: function(msg)
					{
						// Why use msg cid? Because the cid var could change if someone clicks several exports in a row before this line executes
						// So the export code returns the cid of the character it completed so we don't lose track and get unpredictable behavior
						if (ajaxErr(msg,'#toon_'+msg['post']['cid'])) return;

						// Show success message
						showMsg('#toon_'+msg['post']['cid'],'Respect the Hammer!');
						$('#toon_'+msg['post']['cid']+' .toon_ban_button').replaceWith(msg['new_hammer']);

					},
					error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
				});						
			});
			
			// MARK ACTIVE CHARACTERS
			$('[data-active!=""][data-active]').each(function(){
				// What AT are they playing
				playingAT = $(this).attr('src');
				// Character deets
				toonDeets = $(this).closest('.toon_bk').attr('title');
				// Oh, and by the way WHO is playing?
				uid = $(this).closest('.toon_table').data('uid');
				
				// Mark their name in the user table
				$('#account_'+uid+'').attr('title','ONLINE: Playing '+toonDeets);
				$('#account_'+uid+' .account_online img').attr('src',playingAT);
			});
			<?php } ?>
			
		});
	</script>
	
	

	<h2>Client Download</h2>	
	<div class="greyblock">
		<p>To download the client and be able to log in, please follow these steps:</p>
		<ol>
			<li>Download the client <a href="https://127.0.0.1/tequila.exe">from here</a>.</li>
			<li>Run "Tequila.exe" and login with the account created above.</li>
		</ol>
		<p>If you already have the SCORE client downloaded, you can instead launch with the following command line:
		<pre class="blackblock" style="font-size:16px">start .\score.exe -auth <?php echo $_SERVER['REMOTE_ADDR'];?> -patchdir score -patchversion 2019.04.19 -noversioncheck</pre>
		</p>
	</div>

	
	<script>
	
		// In a function since toon count can change on adds and deletes during page operation
		function checkToonCount()
		{
			$('.toon_table').each(function(){
				if (!$(this).find('.toon_tr').length)	// NO TOONS!?
					$(this).find('.no_toons').show();
				else
					$(this).find('.no_toons').hide();
			});
		}
		checkToonCount();	// Run on inital page load

		function updateUserOptions(options)
		{
			// Update the user list for import toon
			$('#import_account').html(options);
			// Update the user lists for all character transfer controls
			$('.toon_moveto').each(function(){
				$(this).html('<option>Transfer to:</option>'.options);
			});
		}
		
	</script>

<?php
	require_once('footer.php');
?>