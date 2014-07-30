#!/usr/bin/perl
use strict;
use Data::Dumper;
use Win32::ODBC;
use POSIX qw(strftime);
use DateTime;
require 'Phpconst2perlconst.pm';
use File::Path;
use File::Copy;
use File::Basename;
require 'useful.pl'; # load get_time / second2hms
use Phpconst2perlconst ;
use Getopt::Long;
$|=1;

my ($rebuild,$help);
GetOptions('rebuild!'=>\$rebuild, 'help|usage!'=>\$help) ;
die <<EOT if ($help);
Liste des arguments :
--rebuild
	Reconstruit les index fragmentés a plus de 5%

--usage ou --help
	Affiche ce message
EOT


########################################################################################
my $old_time = 0;
my $cfg 				= new Phpconst2perlconst(-file => 'config.php');
my $reflex 				= new Win32::ODBC('DSN='.$cfg->{'REFLEX_DSN'}.';UID='.$cfg->{'REFLEX_USER'}.';PWD='.$cfg->{'REFLEX_PASS'}.';') or die "Ne peux pas se connecter à REFLEX";


################### CREATION DU FICHIER DE SORTIE ######################################
my $now = strftime('%Y-%m-%d %Hh%Mm%Ss', localtime);
my $OUTPUT_FILENAME = "output/check-fragmentation-index ($now).csv";
mkpath(dirname($OUTPUT_FILENAME)) if !-d dirname($OUTPUT_FILENAME) ;
open(CSV,'+>'.$OUTPUT_FILENAME) or die "ne peux pas creer le fichier de sortie '".$OUTPUT_FILENAME."' ($!)";
print CSV join(';',qw/Table Index_Name Index_ID Frag/)."\n";

################# SELECT DES COMMANDES REFLEX ########################################################
printf "%s Select des tables reflex\n",get_time(); $old_time=time;
my $sql_reflex = <<EOT ;
SELECT 	name
FROM 	RFXPRODDTA.dbo.sysobjects
WHERE 	xtype='U'
ORDER 	BY name ASC
EOT

if ($reflex->Sql($sql_reflex))  { die "SQL Reflex TABLE failed : ", $reflex->Error(); }
my @tables = ();
while ($reflex->FetchRow()) {
	my %row_reflex = $reflex->DataHash() ;

	push @tables, $row_reflex{'name'} ;
} # fin while reflex


my @index_need_rebuild = ();
foreach (@tables) {
	next if $_ eq 'sysdiagrams';
	printf "%s Checking index for table $_ ... ",get_time(); $old_time=time;

	my $sql_reflex = <<EOT ;
SELECT a.index_id, name, avg_fragmentation_in_percent, '$_' as table_name
FROM sys.dm_db_index_physical_stats (
						DB_ID('RFXPRODDTA'),
						OBJECT_ID('reflex.$_'), NULL, NULL, NULL) AS a
    JOIN sys.indexes AS b ON a.object_id = b.object_id AND a.index_id = b.index_id
    where avg_fragmentation_in_percent > 5;
EOT

	if ($reflex->Sql($sql_reflex))  { warn "SQL Reflex TABLE-INDEX failed : ", $reflex->Error(); }

	while ($reflex->FetchRow()) {
		my %row_reflex = $reflex->DataHash() ;
		$row_reflex{'avg_fragmentation_in_percent'} =~ s/\./,/g;
		print CSV join(';',@row_reflex{qw/table_name name index_id avg_fragmentation_in_percent/})."\n";

		push @index_need_rebuild, "ALTER INDEX $row_reflex{name} ON RFXPRODDTA.reflex.$row_reflex{table_name} REBUILD";
	} # fin while reflex
	print "done\n";
}

close(CSV);

# Lancement de la reconstruction
if ($rebuild) {
	foreach (@index_need_rebuild) {
		my($index_name,$table_name) = (m/^ALTER +INDEX +(\w+) +ON RFXPRODDTA\.reflex\.(\w+) +REBUILD/i);
		if ($index_name && $table_name) {
			printf "%s Rebuild index $index_name on table $table_name ... ",get_time(); $old_time=time;
			if ($reflex->Sql($_))  { warn "SQL Reflex REBUILD-INDEX failed : ", $reflex->Error(); }
			print "done\n";
		}
	}
}