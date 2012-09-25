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

			$this->mysqli->query("create temporary table result(media_no bigint not null, primary key(media_no)) engine=MEMORY");

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

				$tables = 'result';
			}
			else
			{
				$tables = 'temp0';
			}

			$stmt = $this->mysqli->prepare("select $tables.media_no,title,artist_rec.txt,album_rec.txt,genre_rec.txt,media_rec.path
				from $tables,media_rec,artist_rec,album_rec,genre_rec
				where $tables.media_no=media_rec.media_no
				and media_rec.artist_no=artist_rec.artist_no
				and media_rec.album_no=album_rec.album_no
				and media_rec.genre_no=genre_rec.genre_no")
				or die ("Prepare failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);

			$stmt->execute()
				or die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);

			$results = array();

			$media = new MediaRecord();
			$stmt->bind_result ( $media_no, $media->Title,$media->Artist,$media->Album,$media->Genre,$media->Path );

			while ($stmt->fetch())
			{
				$results[] = $media;
				$media = new MediaRecord();
				$stmt->bind_result ( $media_no, $media->Title,$media->Artist,$media->Album,$media->Genre,$media->Path );
			}

			return $results;
		}

	}

	class MediaRecord
	{
		public $Title;
		public $Album;
		public $Artist;
		public $Genre;
		public $Path;
	}
?>
