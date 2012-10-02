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

/* Copyright (c) 2012 Joshua Allen

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

?>
