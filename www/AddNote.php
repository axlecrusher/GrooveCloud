<?php
	require "Sessions.php";

	if ( !(isset($_POST['s']) && isset($_POST['h']) & isset($_POST['note'])) ) die("incomplete data");

	if ( CheckHash($_POST['s'],$_POST['h']) === false ) die("Invalid Hash");

	$mysqli = new mysqli("localhost", "mediaStreamer", "JjhFSfjhRLmMKTbT", "media_streamer")
				or die("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
	$mysqli->set_charset('utf8');
	$mysqli->query("SET NAMES UTF8") or die("could not use UTF8");

	$stmt = $mysqli->prepare("replace into notes_rec (media_no,txt) values (?,?)")
			or die("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);

	$stmt->bind_param("ss", $_POST['s'], $_POST['note'])
		or die("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);

	$stmt->execute()
		or die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);

	echo "Note Posted";
?>
