#!/usr/bin/perl
use Time::HiRes qw(usleep nanosleep);
use DBI;
use JSON::XS;

$sqlHash = {};

$dbh = DBI->connect('dbi:mysql:media_streamer','mediaStreamer','JjhFSfjhRLmMKTbT')
	or die "Connection Error: $DBI::errstr\n";

print "Recursively indexing media in current directory\n";

$path = './';

@files = `find $path -type f -name '*.m4a'`;

$processCounter = 0;

$SIG{CHLD} = sub {
        while ((my $child = waitpid(-1, WNOHANG)) > 0)
		{
			$processCounter--;
        }
    };

my $startPos = length($path);
foreach my $f (@files)
{
	ForkProcess($f);
#	ProcessFile($f);
}

WaitForProcesses();

sub ForkProcess()
{
	my ($f) = @_;

	WaitForProcesses();

	my $pid = fork();

	if (not defined $pid)
	{
		exit(0);
	}
	elsif ($pid == 0)
	{
		#child process
		$dbh = $dbh->clone();
#		$dbh->{InactiveDestroy} = 1;
		ProcessFile($f);
		exit(0);
	}
	else
	{
		#parent process
		$processCounter++;
		push(@processes, $pid);
	}
}

sub WaitForProcesses()
{
	while ($processCounter >= 10)
	{
		usleep(10000);
	};
}

sub ProcessFile()
{
	my ($f) = @_;

	chomp($f);

	$f = substr($f, $startPos);
#	print "file: $f\n";
	my $data = GetFileInfo($f);
	$data->{path} = $f;

	$data->{ArtistNumber} = GetArtistNumber($data->{Artist});
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
