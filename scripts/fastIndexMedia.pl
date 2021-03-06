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

my $checkExisting = PrepareStatement("select media_no from media_table where path=?");


print "Recursively indexing media in current directory\n";

$path = '../www';

$fileList = 'fileList.txt';
#$fileList = 'testFiles.txt';

`find -L $path -type f -name '*.m4a' -o -name '*.mp3' > $fileList`;

if ( -f 'fileListLast.txt')
{
	`fgrep -xv -f fileListLast.txt fileList.txt > fileListDiff.txt`;
	$fileList = 'fileListDiff.txt';
}

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

`cp fileList.txt fileListLast.txt`;

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

sub GetExistingMediaNo
{
	my ($path) = @_;
	$checkExisting->execute($path) or die("Could not insert path");
	if (my @row = $qe->fetchrow_array)
	{
		return $row[0];
	}
	return undef;
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
		CleanData($e);
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

sub CleanData
{
	my ($href) = @_;

	$href->{TrackNumber} = $href->{Track} if (defined $href->{Track});
	$href->{TrackNumber} =~ /$\D*(\d+)\D*/;
	$href->{TrackNumber} = $1;
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

	$data->{MediaNo} = InsertMediaRecord($data);
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

	my $q = PrepareStatement("select artist_no from artist_table where txt=?");
	$q->execute($x);

	if (my @row = $q->fetchrow_array)
	{
		return $row[0];
	}

	$q = PrepareStatement("insert into artist_table set txt=?");
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

	my $q = PrepareStatement("select album_no from album_table where txt=?");
	$q->execute($x);

	if (my @row = $q->fetchrow_array)
	{
		return $row[0];
	}

	$q = PrepareStatement("insert into album_table set txt=?");
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

	my $q = PrepareStatement("select genre_no from genre_table where txt=?");
	$q->execute($x);

	if (my @row = $q->fetchrow_array)
	{
		return $row[0];
	}

	$q = PrepareStatement("insert into genre_table set txt=?");
	unless ($q->execute($x))
	{
		return GetGenreNumber($x) if ($q->{ix_sqlerrd}[1] == 23000);
	}

	return GetGenreNumber($x);
}

sub InsertMediaRecord()
{
	my ($data) = @_;

	my $q = PrepareStatement("replace into media_rec (media_no,album_no,artist_no,genre_no,track_number,year,duration,filesize) values (?,?,?,?,?,?,?,?)");
	my $qt = PrepareStatement("insert ignore into title_rec (media_no,title) values (?,?)");
	my $qp = PrepareStatement("insert ignore into media_table (path) values (?)");
	my $qe = PrepareStatement("select media_no from media_table where path=?");

	my $serial = 0;
	$qe->execute($data->{path}) or die("Could not insert path");
	if (my @row = $qe->fetchrow_array)
	{
		$serial = $row[0];
	}
	else
	{
		$qp->execute($data->{path}) or die("Could not insert path");
		$serial = $dbh->{ q{mysql_insertid} }; #last insert ID
	}

	$q->execute($serial, $data->{AlbumNumber},$data->{ArtistNumber},$data->{GenreNumber},
		$data->{TrackNumber}, $data->{Year}, $data->{MediaDuration}, $data->{FileSize});

	$qt->execute($serial, $data->{Title}) or die("Could not insert title");

	return $serial;
}

sub CreateWordMap()
{
	my ($data) = @_;

	my $s = $data->{Title} . ' ' . $data->{Artist} . ' ' . $data->{Album} . ' ' . $data->{Genre} . ' ' . $data->{Year};
	$s =~ s/[^\w\s]//g;

#	print "$s\n";
	my @words = split(/\s+/, $s);
	chomp($words);
	my $q = PrepareStatement("insert ignore into word_table (txt) values (?)");
	my $q2 = PrepareStatement("insert ignore into word_map set media_no=?, word_no=(select word_no from word_table where txt=?)");

	foreach my $w (@words)
	{
		$q->execute($w) if ($w ne '');
		$q2->execute($data->{MediaNo},$w) if ($w ne '');
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

#Copyright (c) 2012 Joshua Allen
#
#Permission is hereby granted, free of charge, to any person obtaining a copy of
#this software and associated documentation files (the "Software"), to deal in
#the Software without restriction, including without limitation the rights to
#use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
#of the Software, and to permit persons to whom the Software is furnished to do
#so, subject to the following conditions:
#
#The above copyright notice and this permission notice shall be included in all
#copies or substantial portions of the Software.
#
#THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
#IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
#FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
#AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
#LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
#OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
#SOFTWARE.
