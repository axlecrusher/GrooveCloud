<?php

	class MediaSearch
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
			$search = trim($searchString);
			$terms = preg_split('/\s+/', $search);

			$this->mysqli->query("create temporary table result(media_no smallint not null, primary key(media_no)) engine=MEMORY");

			$join = "";
			$num = 0;
			$query;
			foreach($terms as $t)
			{
				if ($num>0) $join .= " join temp$num using (media_no) ";

				$this->mysqli->query("create temporary table temp$num like result")
					or die("Query failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);

				$stmt = $this->mysqli->prepare("insert ignore into temp$num (media_no)
					select wm.media_no from word_table wt, word_map wm
					where wt.txt like ? and wt.word_no=wm.word_no")
					or die("Prepare failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);

//				$t .= '%';

				$stmt->bind_param("s", $t)
					or die("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);

				$stmt->execute()
					or die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);

				$num++;
			}

			if ($num>1)
			{
				$xxx = "insert into result SELECT media_no FROM temp0 $join;";
//				echo "<br>$xxx";
				$this->mysqli->query($xxx)
					or die("Query failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);

				$table = 'result';
			}
			else
			{
				$table = 'temp0';
			}

			$sql = "select $table.media_no,title,artist_table.txt,album_table.txt,genre_table.txt,path
				from $table,
				media_table,title_rec,media_rec left outer join artist_table on media_rec.artist_no=artist_table.artist_no
				left outer join album_table on media_rec.album_no=album_table.album_no
				left outer join genre_table on media_rec.genre_no=genre_table.genre_no
				where $table.media_no=media_rec.media_no
				and media_rec.media_no=title_rec.media_no
				and media_rec.media_no=media_table.media_no";
				
			$stmt = $this->mysqli->prepare($sql)
				or die ("Prepare failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error . $sql);

			$stmt->execute()
				or die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);

			$results = array();

			$media = new MediaRecord();
			$stmt->bind_result ( $media->Serial, $media->Title,$media->Artist,$media->Album,$media->Genre,$media->Path );

			while ($stmt->fetch())
			{
				$results[] = $media;
				$media = new MediaRecord();
				$stmt->bind_result ( $media->Serial, $media->Title,$media->Artist,$media->Album,$media->Genre,$media->Path );
			}

			return $results;
		}

	}

	class MediaRecord
	{
		public $serial;
		public $title;
		public $album;
		public $artist;
		public $genre;
		public $path;
		public $trackNumber;
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
