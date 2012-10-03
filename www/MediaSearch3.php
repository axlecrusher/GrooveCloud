<?php
	require "SqlHelpers.php";

	class MediaSearch3
	{
		private $mysqli;

		function __construct()
		{
			$this->mysqli = new mysqli("localhost", "mediaStreamer", "JjhFSfjhRLmMKTbT", "media_streamer")
				or die("Failed to connect to MySQL: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error);
			$this->mysqli->set_charset('utf8');
		}

		function Search($searchString)
		{
			$searchString = preg_replace("/[^\w\s]/", '', $searchString);
			$search = trim($searchString);

			$terms = preg_split('/\s+/', $search);
			$termsCount = count($terms);

			$paramsQ = str_repeat("?,", $termsCount);
			$params = str_repeat("s", $termsCount);

			$paramsQ = substr( $paramsQ, 0, -1);

			if ($termsCount > 20) return "Too many search terms";

//			$this->mysqli->query("create temporary table word_matches(word_no smallint not null, primary key(word_no)) engine=MEMORY");
			$this->mysqli->query("create temporary table word_matches(word_no smallint not null) engine=MEMORY"); //so small key probably not needed
			$this->mysqli->query("create temporary table results(media_no smallint not null, primary key(media_no)) engine=MEMORY");

			$sql = "insert ignore into word_matches
					select word_no from word_table WHERE txt in ($paramsQ)";

			$stmt = $this->mysqli->prepare($sql)
					or die("Prepare failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);

			SqlHelpers\BindParam($stmt, $terms);

			$r = $stmt->execute()
				or die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);

			$stmt = $this->mysqli->prepare("insert ignore into results
				select media_no from word_map,word_matches where word_map.word_no=word_matches.word_no
				group by media_no having count(media_no)=?");

			$stmt->bind_param("s", $termsCount)
				or die("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);

			$stmt->execute()
				or die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);

			$sql = "select results.media_no,title,artist_table.txt,album_table.txt,genre_table.txt,path,track_number
				from results,media_table,title_rec,
				media_rec left outer join artist_table on media_rec.artist_no=artist_table.artist_no
				left outer join album_table on media_rec.album_no=album_table.album_no
				left outer join genre_table on media_rec.genre_no=genre_table.genre_no
				where results.media_no=media_rec.media_no
				and media_rec.media_no=title_rec.media_no
				and media_rec.media_no=media_table.media_no";
				
			$stmt = $this->mysqli->prepare($sql)
				or die ("Prepare failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error . $sql);

			$stmt->execute()
				or die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);

			$results = array();

			$media = new MediaRecord();
			$stmt->bind_result ( $media->serial, $media->title,$media->artist,$media->album,$media->genre,$media->path,$media->trackNumber );

			while ($stmt->fetch())
			{
				$results[] = $media;
				$media = new MediaRecord();
				$stmt->bind_result ( $media->serial, $media->title,$media->artist,$media->album,$media->genre,$media->path,$media->trackNumber );
			}

			return $results;
		}

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
