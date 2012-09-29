<?php
	require "Sessions.php";
	require "MediaSearch.php";

	if ( isset($_GET['s']) ) DoPost();

	function DoPost()
	{
//		$host =  $_SERVER['HTTP_HOST'];
		$host = "";
		$msearch = new MediaSearch();
		$search = rawurldecode($_GET['s']);
		$results = $msearch->Search( $search );


		echo '<div id="resultSet"><table style="width: 100%;text-align: left;"><tbody>';
		echo "<tr><th>Title</th><th>Album</th><th>Artist</th></tr>";
		foreach ($results as $r)
		{
			$addr = $host . str_replace('%2F', '/', rawurlencode($r->Path));

			$jTitle = rawurlencode($r->Title);
			$jArtist = rawurlencode($r->Artist);
			$jAlbum = rawurlencode($r->Album);

			$webTitle = htmlentities($r->Title,ENT_QUOTES, 'UTF-8');
			$webArtist = htmlentities($r->Artist,ENT_QUOTES, 'UTF-8');
			$webAlbum = htmlentities($r->Album,ENT_QUOTES, 'UTF-8');

			$hSerial = HashData( $r->Serial );

			echo <<<EOF
<tr><td><a href='javascript:addSong("$jTitle","$jArtist","$addr");'>$webTitle</a></td><td>$webAlbum</td><td>$webArtist</td><td><a href='javascript:showNotes($r->Serial,"$hSerial");'>Add Notes</a></td></tr>
EOF;
		}
		echo "</tbody><table></div>";
	}

?>
