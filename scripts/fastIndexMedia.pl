#!/usr/bin/perl
use utf8;
use Encode qw< encode decode >;
use Encode;
use Encode::Guess; # ascii/utf8/BOMed UTF
use Time::HiRes qw(usleep nanosleep);
use DBI;
use JSON::XS;
use POSIX qw(:signal_h :errno_h :sys_wait_h);

$sqlHash = {};

$dbh = DBI->connect('dbi:mysql:media_streamer','mediaStreamer','JjhFSfjhRLmMKTbT')
	or die "Connection Error: $DBI::errstr\n";

$dbh->do("SET NAMES UTF8")
	or die "could not use UTF8\n";
#$dbo->{'mysql_enable_utf8'} = 1;

print "Recursively indexing media in current directory\n";

$path = '../www';

$fileList = 'fileList.txt';
#$fileList = 'testFiles.txt';

`find -L $path -type f -name '*.m4a' -o -name '*.mp3' > $fileList`;

$processCounter = 0;

$SIG{CHLD} = \&ReapProcess;

my $startPos = length($path);
my $len = `wc -l $fileList`;
for (my $i = 0; $i<$len; $i+=100)
{
	ProcessFiles([$i,$i+100]);
#	WaitForProcesses();
#	ForkProcess(\&ProcessFiles, [$i,$i+100]);
}


WaitForProcesses(0);

sub ReapProcess
{
	#http://docstore.mik.ua/orelly/perl/cookbook/ch16_20.htm
	while ((my $child = waitpid(-1, WNOHANG)) > 0)
	{
		if (WIFEXITED($?))
		{
			print "killed $child\n";
			$processCounter--;
		}
	}
	
#	$SIG{CHLD} = \&ReapProcess;
}

sub ForkProcess
{
	my ($callback,$data) = @_;

	my $pid = fork();

	if (!defined($pid))
	{
		print("could not fork\n");
		exit(1);
	}
	elsif ($pid == 0)
	{
		$dbh = $dbh->clone();
		$dbh->do("SET NAMES UTF8") or die "could not use UTF8\n";
		$callback->($data);

		my @data = @{$data_ref};

		exit(0);
	}
	elsif ($pid>0)
	{
		#parent process
		print "spawned $pid\n";
		$processCounter++;
	}
}

sub ProcessFiles
{
	my ($begin,$end) = @{$_[0]};
	$begin++;
	($ENV{f1})="$begin,$end".'p';

	#batch process media
	print "reading data\n";

	my $json_text = `sed -n "\$f1" $fileList | exiftool -J -fast -charset UTF8 -@ -`;

	eval
	{
		my $json_bytes = encode('UTF-8', $json_text);
		my $data = decode_json($json_bytes); #data comes out as UTF-8, needs to be converted to perl's internal format

		WaitForProcesses(3);
		ForkProcess(\&ProcessData, $data);
	};
	if ($@)
	{
		print STDERR $@ . "\n";
	}
}

sub ProcessData()
{
	my ($data_ref) = @_;
	my @data = @{$data_ref};

	foreach my $e (@data)
	{
		DecodeData($e);
		ProcessSQL($e);
	}
}

sub DecodeData
{
	my ($href) = @_;
	foreach my $k (keys %$href)
	{
		my $a = decode("utf-8", $href->{$k}); #switch back to perl's native encoding
#		print "$k: $a " . length($href->{$k}) . ' ' . length($a) . "\n";
#		$href->{$k} = "\x{53a0} " . $a; # test to see if utf8 characters are handled properly
		$href->{$k} = $a;
	}
}

sub WaitForProcesses()
{
	my ($c) = @_;

	while ($processCounter > $c)
	{
		usleep(10000);
	};
}

sub ProcessSQL()
{
	my ($data) = @_;

#	chomp($f);

#	$f = substr($f, $startPos);
#	print "file: $f\n";
#	my $data = GetFileInfo($f);
#	$data->{path} = $f;
	$data->{path} = substr($data->{SourceFile},$startPos);

	$data->{ArtistNumber} = GetArtistNumber($data->{Artist});
#	print $data->{Artist} ."\n";
	$data->{AlbumNumber} = GetAlbumNumber($data->{Album});
	$data->{GenreNumber} = GetGenreNumber($data->{Genre});

	InsertMediaRecord($data);
	CreateWordMap($data);
}

sub GetFileInfo()
{
	my ($filePath) = @_;

	print "file: $filePath\n";
	($ENV{f1})=("$filePath");

	my @data = `exiftool -S "\$f1"`;
	my $dhash = {};

	foreach my $line (@data)
	{
		chomp($line);
		my $pos = index($line, ":");
		$dhash->{substr($line,0,$pos)} = substr($line,$pos+2);
	}

	return $dhash;
}

sub GetArtistNumber()
{
	my ($x) = @_;

	return null if ($x eq '');

	my $q = PrepareStatement("select artist_no from artist_rec where txt=?");
	$q->execute($x);

	if (my @row = $q->fetchrow_array)
	{
		return $row[0];
	}

	$q = PrepareStatement("insert into artist_rec set txt=?");
	unless ($q->execute($x))
	{
		return GetArtistNumber($x) if ($q->{ix_sqlerrd}[1] == 23000);
	}

	return GetArtistNumber($x);
}

sub GetAlbumNumber()
{
	my ($x) = @_;

	return null if ($x eq '');

	my $q = PrepareStatement("select album_no from album_rec where txt=?");
	$q->execute($x);

	if (my @row = $q->fetchrow_array)
	{
		return $row[0];
	}

	$q = PrepareStatement("insert into album_rec set txt=?");
	unless ($q->execute($x))
	{
		return GetAlbumNumber($x) if ($q->{ix_sqlerrd}[1] == 23000);
	}

	return GetAlbumNumber($x);
}

sub GetGenreNumber()
{
	my ($x) = @_;

	return null if ($x eq '');

	my $q = PrepareStatement("select genre_no from genre_rec where txt=?");
	$q->execute($x);

	if (my @row = $q->fetchrow_array)
	{
		return $row[0];
	}

	$q = PrepareStatement("insert into genre_rec set txt=?");
	unless ($q->execute($x))
	{
		return GetGenreNumber($x) if ($q->{ix_sqlerrd}[1] == 23000);
	}

	return GetGenreNumber($x);
}

sub InsertMediaRecord()
{
	my ($data) = @_;

	my $q = PrepareStatement("insert ignore into media_rec (path,title,album_no,artist_no,genre_no,track_number,year,duration,filesize) values (?,?,?,?,?,?,?,?,?)");
	$q->execute($data->{path}, $data->{Title},$data->{AlbumNumber},$data->{ArtistNumber},$data->{GenreNumber},
		$data->{TrackNumber}, $data->{Year}, $data->{MediaDuration}, $data->{FileSize});

	my $r = $q->{ix_sqlerrd}[1];
	return $r;
}

sub CreateWordMap()
{
	my ($data) = @_;

	my $s = $data->{Title} . ' ' . $data->{Artist} . ' ' . $data->{Album} . ' ' . $data->{Genre} . ' ' . $data->{Year};

#	print "$s\n";
	my @words = split(/\s+/, $s);
	chomp($words);
	my $q = PrepareStatement("insert ignore into word_table (txt) values (?)");
	my $q2 = PrepareStatement("insert ignore into word_map set media_no=(select media_no from media_rec where path=?), word_no=(select word_no from word_table where txt=?)");

	foreach my $w (@words)
	{
		$q->execute($w) if ($w ne '');
		$q2->execute($data->{path},$w) if ($w ne '');
	}
}

sub PrepareStatement($sql)
{
	my ($sql) = @_;

	return $sqlHash->{$sql} if (exists $sqlHash->{$sql});

	my $x = $dbh->prepare($sql) or die "could not prepare $sql\n";
	$sqlHash->{$sql} = $x;

	return $x;
}
