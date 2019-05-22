<?php
	// pop up commands for stuff too big or detailed to sit in the regular page flow
	// Why in it's own file? Readability basically
	
	function go_cancel($go,$cancel='Cancel')
	{
		return '
			<div class="go_cancel_area">
				<input type="button" class="green_button" value="'.$go.'">
				<input type="button" class="pop_cancel_button orange_button" value="'.$cancel.'">
				<div class="spinner"></div>
				<div class="msg covers"></div>
			</div>
		';
	}
?>
	<style>
		.go_cancel_area {
			position: relative;
		}
	</style>
	<script>
		// General stuff -- applies to all
		$(document).ready(function() {
			
			// Cancel by default simply closes everything
			$('.pop_cancel_button').click(function(){
				$('#popups_area').hide();
			});
			
			// Close by clicking away
			$('#popups_area').click(function(e) {
				if (e.target != this)
					return false;
				$('.pop_cancel_button:visible').click();
			});
		});
	</script>

	<div id="popups_area">
		
		
		<div id="popup" class=greyblock>


			<div id="import_options" class="popup_area">
				<style>
					.import_option {
						display: inline-block;
						width: 247px;
						position: relative;
						margin: 8px 3px 9px 3px;
						text-align: left;
					}
					#import_owner {
						display: block;
						border-bottom: 2px solid #444;
						margin-bottom: 10px;
					}
					#import_owner>*, .import_option input, .import_option select {
						display: inline-block;
					}
					.import_option .toon_bk {
						display:block;
						margin: 0 auto 5px auto;
					}
					.import_option input {
						width: 50px;
						height: 26px;
						float: right;
					}
					.import_option img{
						width: 40px;
						cursor: pointer;
					}
					#InfluencePoints input {
						width: 145px;
					}
					.enable_disable {
						width: 29px;
						height: 29px;
						background: url('graphics/icons/enabled.png');
						background-size: 29px 29px;
						display: block;
						float: right;
						cursor: pointer;
						position: relative;
						top: -4px;
					}
					.disabled .enable_disable {
						background: url('graphics/icons/disabled.png');
						background-size: 29px 29px;
					}
					.import_deets {
						display: block;
						border-bottom: 1px solid #444;
						line-height: 30px;
					}
					.import_deets span {
						float: right;
						border-radius: 6px;
					}
					#import_name {
						height: 26px;
						width: 140px;
						margin-right: 10px;
					}
				</style>
				
				<h2>Import Toon</h2>
				<p>Select import options then click "IMPORT" to continue or "CANCEL" to back away slowly and do nothing.</p>
			
				<div id="pop_import_form"  class="blackblock">

					
					<div id='import_owner'>
						<label>Toon</label>
						<input type=text maxlength=14 id="import_name" />
						<label>New Owner:</label>
						<select id="import_account"><?php echo accounts_for_import();?></select>
					</div>
					
					<p>To protect your local economy, feel free to disable import of any of the below:</p>
					
					<?php 
						// $title - what it will be called
						// $str_search - what string to search for to kill for this kind of attribute
						function write_import_option($key,$value)
						{
							echo '
								<div id="'.$key.'" class="import_option">
									<div class="toon_bk">'.$value['title'].'<div class="enable_disable"></div></div>
									<div class="current import_deets">Has: <span class="amount"></span></div>
								</div>
							';
						}
						
						// db name, our title, our max, and whether it's a subclass of inventory
						$import_config = array(
							"InfluencePoints" => array('title' => "Influence", 'value'=>0),
							"RespecTokens" => array('title' => "Respec Tokens", 'value'=>0),
							"FreeTailorSessions" => array('title' => "Tailor Sessions", 'value'=>0),

							"InvRecipeInvention" => array('title' => "Allow Recipies", 'value'=>0),
							"InvSalvage" => array('title' => "Allow Salvage", 'value'=>0),

							"S_IncarnateThread" => array('title' => "Allow Incarnate Threads", 'inv'=>true,'value'=>0),
							"S_EndgameMerit" => array('title' => "Allow &quot;Endgame Merits&quot;",'inv'=>true,'value'=>0),
							"S_MeritReward" => array('title' => "Allow Merits", 'inv'=>true,'value'=>0),
							"S_HeroMerit" => array('title' => "Allow Hero Merits", 'inv'=>true,'value'=>0),		
							"S_VillainMerit" => array('title' => "Allow Villain Merits", 'inv'=>true,'value'=>0),		
							"S_VanguardMerit" => array('title' => "Allow Vanguard Merits", 'inv'=>true,'value'=>0),		
						);
												
						foreach ($import_config as $key => $value)
							write_import_option($key,$value,$max);
												
						?>
					<div class="spinner"></div>
					<div class="msg covers"></div>
				</div>
				
				<?php echo go_cancel('Import'); ?>

				<h3 style='margin: 30px 0 15px 0'>Raw Import Data</h3>
				<p>If you want to see what the raw import file looks like (minus // commented lines), see below:</p>
				<div id="toon_import_raw" class="blackblock">
				</div>
				
				<script>
								
					// Special categories of salvage
					var import_guide = <?php echo json_encode($import_config); ?>;
					// Global for whatever character we're importing at the time
					var import_data = Array();

					function prepareImport()
					{									
						spinnerOn('#import_form');
						// Attempt a call to change the password
						$.ajax({
							url: 'actions/import_character.php',
							type: 'POST',
							data: { 
								which: 'prepare',
								name: $('#import_which').val(), 
							},
							dataType: 'json',
							success: function(msg)
							{
								if (ajaxErr(msg,'#import_form')) return;

								spinnerOff('#import_form');
								popOn('#import_options');

								// Clear from last time (if there was a last)
								$('.import_deets .amount').html(0);
								$.each(import_guide, function(key,attr_arr) {
									attr_arr['value'] = 0;
								});

								import_data = JSON.parse(msg['character_data']);
								prettify = '';
								
								$(import_data).each(function(lineno,value) {
									// value is a " name value" array
									import_attr = value[0];
									import_value = value[1];
									
									if (import_attr == 'Name')
										$('#import_name').val(import_value.replace("\\s","'"));
									
									// For our pretty printout for users to look over the raw data
									if (import_attr && import_value)
										prettify += import_attr+' '+import_value+'<br>';
									
									// We're going through all the values anyway, might as well check for our controlled data
									$.each(import_guide,function(guide_key,guide_attr_arr) 
									{
										if (import_attr.indexOf(guide_key)!=-1)		// The import key includes one of our control strings
										{

											// Salvage but not one of our special salvage types
											if (import_attr.indexOf('InvSalvage') != -1 && !import_guide[guide_key]['inv'])
												import_guide[guide_key]['value'] += parseInt(import_value);		// add it to the pile
											
											// Recipie is broken into two lines - type and ammount. We only care about the amount
											else if (import_attr.indexOf('RecipeInv') != -1) 
											{
												// have to do this in two ifs or else it hits the else clause below and becomes NaN
												if (import_attr.indexOf('Amount') != -1)
													import_guide[guide_key]['value'] += 0+parseInt(import_value);		// add it to the pile
											}
											// There are five endgame merit categories
											else if (import_attr.indexOf('Endgame') != -1) 
												import_guide[guide_key]['value'] += parseInt(import_value);		// add it to the pile

											else 
											{
												import_guide[guide_key]['lineno'] = lineno;				// Store this for easy updating the character data later (we'll know exactly what line it's on)
												import_guide[guide_key]['value'] = parseInt(import_value);		// What's the total? For all our settings this will be a number of some sort.
											}
											return;
										}
									});
								});
								$('#toon_import_raw').html(prettify);
								
								// Now we load up the controls area
								$.each(import_guide, function(key,attr_arr) {
									$('#'+key+' .current .amount').text(attr_arr['value']);
									$('#'+key+' .max .amount').val(attr_arr['max']);
									
									if (attr_arr['value'] > attr_arr['max'])
										$('#'+key+' .total .amount').val(attr_arr['max']);
									else
										$('#'+key+' .total .amount').val(attr_arr['value']);
								});
							},
							error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
						});		
					}
					
					$('#import_options .go_cancel_area .green_button').click(function() {
						spinnerOn('#import_options .go_cancel_area');

						//Rebuild the file
						
						toExport = '';
						disabledStuff = Array();
						$.each(import_guide,function(key,value){
							if (value['disabled'])
								disabledStuff[disabledStuff.length] = key;
						});
						for (i=0;i<import_data.length;i++)
						{
							// Some overrides
							if (import_data[i][0] == 'AuthId')
								addIt = 'AuthId '+$('#import_account').val()+'\n'; 	// The auth id of the new owner
							else if (import_data[i][0] == 'AuthName')
								addIt = 'AuthName "'+$('#import_account option:selected').text()+'"\n'; 	// The authname of the new owner
							else if (import_data[i][0] == 'Name')
								addIt = 'Name "'+$('#import_name').val()+'"\n'; 	// The name of the character (collision verified)
							else if (import_data[i][0].length && import_data[i][1].length)
								// Default - Name value pairs separated by a space
								addIt = import_data[i][0]+' '+import_data[i][1]+'\n';
							
							dontPrint = false;
														
							// Check Inventory.  If disabled AND it's not a special inventory type, kill it.
							if (import_data[i][0].indexOf('InvSalvage') != -1 && import_guide['InvSalvage']['disabled'] && !import_guide['InvSalvage']['inv'])
								dontPrint = true;
							// Everything else works simply - if disabled, don't add it.
							else 
							{
								for (y=0;y<disabledStuff.length;y++)
									if (import_data[i][0].indexOf(disabledStuff[y]) != -1)
										dontPrint = true;
							}
							
							if (!dontPrint)
								toExport += addIt;
						}
												
						$.ajax({
							url: 'actions/import_character.php',
							type: 'POST',
							data: { 
								which: 'doit',
								char_data: toExport, 
							},
							dataType: 'json',
							success: function(msg)
							{
								if (ajaxErr(msg,'#import_options .go_cancel_area')) return;

								spinnerOff('#import_options .go_cancel_area');
								popOff();
								addTo = $('.toon_table[data-uid="'+$('#import_account').val()+'"]');
								$(addTo).append(msg['result']);
								// Make sure to show or hide "NO TOONS" message now that the counts have changed
								checkToonCount();
								console.log(msg['diag']);
							},
							error: function(jqXHR, textStatus, errorThrown){ajaxFail(jqXHR, textStatus, errorThrown)}
						});		
						
					});


					$(document).ready(function() {
						
						$('.enable_disable').click(function(){
							theID = $(this).closest('.import_option').attr('id');
							$('#'+theID).toggleClass('disabled');
							import_guide[theID]['disabled'] = $(this).hasClass('disabled') ? 0 : 1;
						});
					});
				</script>
			</div>
			
		</div>
	</div>