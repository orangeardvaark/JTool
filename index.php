<?php
	@session_start();		// We have to have this here to access sessions variables (which we need on this page before calling header
 	require_once("header.php"); 					// Writes the same top half of the page for every page
	$_SESSION['page'] = 'login';
?>

	<style>
		#login_form {
			position: relative;
			padding: 50px;
		}
		.leftright {
			width: 46%; 
			display: inline-block;
			vertical-align: top;
		}
		input[type=button] {
			width: 80px;
		}
		#join_button {
			float: right;
		}
		.greyblock {
			margin: 20px 0;
		}
		form input[type=text] {
			width: 290px;	
		}
		#connstr {
			width: 633px
		}
		.error {
			margin: 30px;
		}
	</style>

	<div class="blockarea">
		<h1>Welcome home!</h1>

<?php

	if (!file_exists('config.php'))	
	{
		$mode = 'config';
		if (!count($_POST))
		{
			$connString = "DRIVER={SQL Server Native Client 11.0};Server=localhost\SQLEXPRESS;Uid=sa;Pwd=password;";
			$authDB = "cohauth";
			$cohDB = "cohdb";
			$aucDB = "cohauc";
			$chatDB = "cohchat";
			$admins = '';	
		}
		else 
		{
			$connString = $_POST['connString'];
			$authDB = $_POST['authDB'];
			$cohDB = $_POST['cohDB'];
			$chatDB = $_POST['chatDB'];
			$aucDB = $_POST['aucDB'];
			$admins = $_POST['admins'];
		
			if (strlen($connString) && strlen($authDB) && strlen($cohDB) && strlen($aucDB) && strlen($chatDB) && strlen($admins))		
			{

				// Lets cut up that string and get useful data out of it
				$parts = explode(';',$connString);
				
				// Now split by = and get the second index (the part on the right side of the equal)
				$host = explode('=',$parts[1])[1];
				$user = explode('=',$parts[2])[1];
				$password = explode('=',$parts[3])[1];
				// Get rid of spaces or extra commas then split to array
				$tmp_admins = explode(',',trim(str_replace(' ','',$admins),','));
				
				if (strlen($host) && strlen($user) && strlen($password) && count($tmp_admins))
				{

					$myfile = fopen('config.php', "w") or die("Unable to open file!");
					$txt = '
						<?php
							$_SESSION["HOST"] = "'.$host.'"; 
							$_SESSION["USER"] = "'.$user.'";
							$_SESSION["PASSWORD"] = "'.$password.'";
							$_SESSION["DATABASE"] = "'.$cohDB.'";
							$_SESSION["DATABASEAUTH"] = "'.$authDB.'";
							$_SESSION["CHATDB"] = "'.$chatDB.'";
							$_SESSION["AUCDB"] = "'.$aucDB.'";
							// Which account names are admins?
							// There are LOTS of ways to handle this, but using this simple array was easiest for a lot of reasons
							$_SESSION["ADMINS"] = array("'.implode('","',$tmp_admins).'");
						?>
					';
					fwrite($myfile, $txt);
					fclose($myfile);
				
					echo "<script type='text/javascript'>window.location.href='index.php';</script>";
				}
				else
					$error = "Couldn't parse your connect string or list of admins. Make sure you copied it correctly and try again.";
			}
			else
				$error = 'You must enter data in each field to continue! You wanna run this server or what!?';
		}	// File created, but doesn't work
	}
	else if (file_exists('config.php') && (!db_connect(1) || !auth_connect(1) || !auc_connect(1) || !chat_connect(1)))
	{
		$contents = file_get_contents('config.php');
		unlink('config.php');
		
		// shave off the left side
		$host = explode('HOST"] = "',$contents);
		// Now the right:
		$host = explode('";',$host[1])[0];
		
		$user = explode('USER"] = "',$contents);
		$user = explode('";',$user[1])[0];
		
		$password = explode('PASSWORD"] = "',$contents);
		$password = explode('";',$password[1])[0];
		
		$connString = 'DRIVER={SQL Server Native Client 11.0};Server='.$host.';Uid='.$user.';Pwd='.$password.';';

		$cohDB = explode('DATABASE"] = "',$contents);
		$cohDB = explode('";',$cohDB[1])[0];
		
		$chatDB = explode('CHATDB"] = "',$contents);
		$chatDB = explode('";',$chatDB[1])[0];
		
		$aucDB = explode('AUCDB"] = "',$contents);
		$aucDB = explode('";',$aucDB[1])[0];
		
		$authDB = explode('AUTH"] = "',$contents);
		$authDB = explode('";',$authDB[1])[0];
		
		$admins = explode('ADMINS"] = array("',$contents);
		$admins = explode('");',$admins[1])[0];
		$admins = str_replace('","',',',$admins);
			
		$error = "The connection information didn't work. Please check your db names, username, and password";	
		$mode = 'config';
		
	}
	
	if ('config' == $mode)
	{
	
		if ($error)
			echo '<div class="error" style="display:block">'.$error.'</div>';
?>

		<form method="post" autocomplete="off">
			<p>Before you can use the tool, we need just a lil' bit of config data.</p>
			<div class="greyblock">
				<h2>Connection String</h2>
				<p>Update this with the string from the one in servers.cfg.</p>
				<input id="connstr" type="text" value="<?php echo $connString; ?>" name="connString">
			</div>
			
			<div class="greyblock">
				<h2>Database Names</h2>
				<p>You shouldn't need to change these, so try leaving them alone the first time through. If that doesn't work, check the listed config files to validate the values.</p>
				<div style="margin: 0 50px">
					<h4>AuthDB</h4>
					<p>I actually have no idea where to verify this other than looking at the database itself. If it helps, mine is "cohauth" (which I've left as default).
					<input type="text" size="20" value="<?php echo $authDB; ?>" name="authDB">
					<h4>COHDB</h4>
					<p>Check the servers.cfg. It should say "SqlDbName <b style="color:orange">cohdb</b>", but if not, enter the bolded part from the file here (it won't be bold in the file... that's just to make it obvious which part to copy):</p>
					<input type="text" size="20" value="<?php echo $cohDB; ?>" name="cohDB">
					<h4>AuctionDB</h4>
					<p>I actually have no idea where to verify this other than looking at the database itself. If it helps, mine is "cohauc" (which I've left as default).
					<input type="text" size="20" value="<?php echo $aucDB; ?>" name="aucDB">
					<h4>ChatDB</h4>
					<p>Check the servers.cfg. It should say "SqlDbName <b style="color:orange">cohdb</b>", but if not, enter the bolded part from the file here (it won't be bold in the file... that's just to make it obvious which part to copy):</p>
					<input type="text" size="20" value="<?php echo $chatDB; ?>" name="chatDB">
				</div>
			</div>
			
			<div class="greyblock">
				<h2>Admin usernames</h2>
				<p>Normal users can make accounts and do limited things with them, but you'll need some admins (most likely you at the least). Enter a list of usernames separated by commas for any users who should be admins. This can be users who already exist or that you haven't made yet. The key is that if someone logs in with one of these names, they'll be an admin. 
				Note that you can update this list at any time in the <i>config.php</i> file later.</p>
				<input type="text" size="20" value="<?php echo $admins; ?>" name="admins" placeholder="example1,otheradmin,somedude">
			</div>
			
			<div class="greyblock">
				<p>Press this to make a "config.php" file. If you need to change values later (like adding or subtracting admin users), just edit the file directly.</p>
				<input type="submit" class="green_button" value="Create Config File">
			</div>
		</form>
<?php
	}
	else
	{
?>
		
		<p>Enter a name and password. Then click either Login to login or Join to join.</p>
		
		<?php 
			if ($error) echo '<div class=error style="margin-bottom:25px;">'.$error.'</div>';
		?>
		
		<div id="login_form" class=leftright >
			<label for=account>Account</label>
			<input type=text value="<?php echo $_POST['account'];?>" name="account" maxlength=14 id=account placeholder="Enter a account"/>
			<label for=password>Password</label>
			<input type=password value="<?php echo $_POST['password'];?>" name="password" maxlength=16 id=password placeholder="Password goes here"/>
			<input id="login_button" type=button value="Log In" class="green_button"/><input id="join_button" type=button value="Join"/>
		</div>
		
		<div id="msg_area" class="greyblock leftright" style="padding:15px">
			<p>Use the name and password you want to use to log into the game:</p>
			<img style="width: 280px; border-radius:8px" src="graphics/help/login.jpg" class=center />
			<div class="spinner"></div>
			<div class="msg covers"></div>
		</div>
		
		<script>
			$(document).ready(function() {
				
				$('input[type=text],input[type=password]').keyup(function(e){
					if (e.keyCode == 13)
						$('#login_button').click();
				});
				
				$('#login_button').click(function(){
					$('#msg_area .spinner').show();
					$.ajax({
						url: 'actions/login.php',
						type: 'POST',
						data: { 
							account: $('#account').val(), 
							password: $('#password').val(), 
						},
						dataType: 'json',
						success: function(msg)
						{
							// Pass or fail, restore the area
							$('#msg_area .spinner').hide();
							
							if (msg['error']) 
							{
								console.log(msg['diag']);
								showMsg('#msg_area .msg',msg['error']);
							}
							else
								window.location = 'home.php';
						},
						error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
					});		
				});
				
				$('#join_button').click(function(){
					$('#msg_area .spinner').show();
					$.ajax({
						url: 'actions/add_account.php',
						type: 'POST',
						data: { 
							account: $('#account').val(), 
							password: $('#password').val(),
						},
						dataType: 'json',
						success: function(msg)
						{
							// Pass or fail, restore the area
							$('#msg_area .spinner').hide();
							
							if (msg['error']) 
							{
								console.log(msg['diag']);
								showMsg('#msg_area .msg',msg['error']);
							}
							else
								// Name and password are still there... let's use them to log in
								$('#login_button').click();
						},
						error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
					});		
				});
				
			});
		</script>
		
	</div>
<?php
	}
	
	require_once('footer.php');
?>
