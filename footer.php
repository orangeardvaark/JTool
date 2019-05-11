
		<div id="author_area">
			<img id="j_avatar" src="graphics/avatars/jordan.jpg"/>
			<p>
				JTool is a CoHDBTool variant by "Jordan Yen"<br>
				<a href=https://ourowiki.ouro-comdev.com/index.php?title=City_of_Heroes_Database_Tool>CoHDBTool</a> by "DarkSynopsis" <br/>
				For support, please join us on <a href="https://discord.gg/rPvHX26">Discord</a>.
			</p>
		</div>
	</div #content>
</div #center_col>

<script>
	
	// This is mostly for readability
	// Basically, send the outer object or ID and it will find the contained spinner and turn it on.
	function spinnerOn(outer)
	{
		$(outer).find('.spinner').show();
	}
	
	function spinnerOff(outer)
	{
		$(outer).find('.spinner').hide();
	}

	// When an ajax operation fails, print some useful data to console.
	// Put this in a function in case we also wanted to do pop up errors or other stuff
	function ajaxFail(jqXHR, textStatus, errorThrown)
	{
		console.log(jqXHR.responseText+":"+textStatus+":"+errorThrown);
	}
	
	// Abstracted here simply to make message behavior consistent
	function showMsg(outer, what)
	{
		// Spinners aren't needed once a message is shown
		spinnerOff(outer);
		$(outer).find('.msg').html(what).show().delay(3000).fadeOut('slow');
	}
	
	// For readability and a bit of code consistency
	// When our pop-up prompt returns a value, just check to see if it's not empty (which would mean it was cancelled)
	function promptOK(prompt,outer,which)
	{
		if (!prompt)
		{
			showMsg(outer,which+' action cancelled');
			console.log('bad prompt:'+prompt);
			return;
		}
		return 1;
	}
	
	// For readability and consistency. Only used in cases where ALL we want to do on an ajax fail is show a message to the user, but make no other changes
	function ajaxErr(msg,outer)
	{
		if (msg['error']) 
		{
			console.log(msg['diag']);
			showMsg(outer,msg['error']);
			return 1;
		}
		return 0;
	}
	
	
</script>

</body>
</html>