<?php
	require "Sessions.php";
	require "MediaSearch.php";
	require "MediaSearch2.php";
	require "MediaSearch3.php";
	require_once('JSONH.class.php');

	if ( isset($_GET['s']) ) DoPost();

	function DoPost()
	{
//		$host =  $_SERVER['HTTP_HOST'];
		$host = "";
		$msearch = new MediaSearch3();
		$search = rawurldecode($_GET['s']);
		$results = $msearch->Search( $search );

		$resultArray = array();
		$count = 0;

		foreach ($results as $r)
		{
			$songData = array(	'title' => $r->Title,
								'artist' => $r->Artist,
								'album' => $r->Album,
								'serial' => $r->Serial,
								'hSerial' => HashData( $r->Serial ),
								'path' => $r->Path);

			array_push($resultArray,$songData);

			$count++;
		}
//		echo json_encode($resultArray);
		echo jsonh_encode($resultArray);
	}

?>
