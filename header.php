<?php
	@session_start();
	header("Cache-control: private"); 			// Makes IE re-fill a from with the entered data when you hit the back button
	require_once("functions.php");
	require_once('db.php');
?>
<html>
<head>
    <title>City of Heroes Freedom Account Portal</title>
    <link href="style.css" rel="stylesheet" type="text/css" />
	<script src="jquery-3.4.1.min.js"></script>
</head>
<body>

<div id="blueside">
	<img src="graphics/renders/psyche.png" class="char_img_glow">
</div>

<div id="redside">
	<img src="graphics/renders/widow.png" class="char_img_glow">
</div>

<div id="center_col">

	<div id="logo_area">
		<img id="logoc" src="graphics/logos/CoHlogo_sm.png">
		<img id="logov" src="graphics/logos/CoVlogo_sm.png">
	</div>

	<div id="content">
	