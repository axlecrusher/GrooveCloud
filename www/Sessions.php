<?php
	require "Functions.php";
	session_name('normalClient');
	session_start();

	if ( !isset($_SESSION['salt']) )
	{
		$_SESSION['salt'] = randBytes(128);
	}
?>
