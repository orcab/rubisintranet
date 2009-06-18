use strict;
use Mysql;
use Data::Dumper;
use DBI qw(:sql_types);
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
$| = 1; # active le flush direct
my $cfg = new Phpconst2perlconst(-file => '../inc/config.php');
use constant {
	DB_FILENAME		=> 'H:/boulot/perl/Base de donnee articles/articles.db',
} ;



my $mysql = Mysql->connect($cfg->{MYSQL_HOST},$cfg->{MYSQL_BASE},$cfg->{MYSQL_USER},$cfg->{MYSQL_PASS}) or die "Peux pas se connecter a mysql";
$mysql->selectdb($cfg->{MYSQL_BASE}) or die "Peux pas selectionner la base mysql";

my $dbh ;
if (-e DB_FILENAME) {
	$dbh = DBI->connect('dbi:SQLite:'.DB_FILENAME) or die("Connexion impossible à la BD"); # identifiant de connexion à la base SQLite
}

my $i = 0 ;
my $sth = $dbh->prepare("SELECT * FROM articles");
$sth->execute();
while (my $row = $sth->fetchrow_hashref()) {
	#$row->{'reference'};
	my @cols_name = keys %$row;
	my $cols_name = join(',',@cols_name);
	my @vals      = ();
	foreach (@cols_name) {
		$row->{$_} =~ s/'/''/g;
		push @vals , "'".$row->{$_}."'";
	}
	my $vals      = join(',',@vals);

	$mysql->query("REPLACE INTO devis_article2 ($cols_name) VALUES ($vals)");
	if (($i++ % 50) == 0) { print "." ;	}
}

$sth->finish();
$mysql->close();