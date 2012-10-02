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
