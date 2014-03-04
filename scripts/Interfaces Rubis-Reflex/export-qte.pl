#!/usr/bin/perl
use strict;

require 'useful.pl';
use Data::Dumper;
use Win32::ODBC;
use File::Path qw(make_path);
use File::Copy;
use File::Basename;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
require 'useful.pl'; # load get_time / second2hms
use Phpconst2perlconst ;
use Getopt::Long;
use Net::FTP;

use constant OUTPUT_FILENAME => 'output/R5400001.txt';

my (@articles,$debug,$test,$help);
GetOptions('articles=s'=>\@articles, 'debug!'=> \$debug, 'test!' => \$test, 'help|usage!'=>\$help) ;

die <<EOT if ($help);
Liste des arguments :
--articles=xxx[,xxx,...]
	N'importe que les articles xxx
--test
	Exporte et importe depuis l'environnement de test
--usage
	Affiche ce message
--help
	Affiche ce message
--debug
	Affiche les requetes SQL
EOT


use constant {
	CODE_APPLICATION							=>	'HL',
	CODE_INTERFACE								=>	'54',
	CODE_RUBRIQUE_DESTINATAIRE					=>	'110',
	CODE_DEPOT_PHYSIQUE							=>	'AFA',
	CODE_TYPE_STOCK								=>	'200',
	CODE_ACTIVITE								=>	'MCS',
	CODE_VARIANTE_LOGISTIQUE_ARTICLE			=>	'30'
};

my %field_sizes = (qw/	CODE_ARTICLE		16
						CODE_PROPRIETAIRE	3
						CODE_QUALITE		3
						QUANTITE_STOCK		9
						SENS_STOCK			1
					/);

my $old_time = 0;
my $cfg 				= new Phpconst2perlconst(-file => 'config.php');
my $prefix_base_reflex 	= $test ? $cfg->{'REFLEX_PREFIX_BASE_TEST'} : $cfg->{'REFLEX_PREFIX_BASE'};
my $reflex 				= new Win32::ODBC('DSN='.$cfg->{'REFLEX_DSN'}.';UID='.$cfg->{'REFLEX_USER'}.';PWD='.$cfg->{'REFLEX_PASS'}.';') or die "Ne peux pas se connecter à REFLEX";


######################################## RECUPERATION DES INFOS ###########################################################
# CREATION DE LA REQUETE SQL
my @articles_to_export;
foreach (@articles) { #pour chaque argument --articles
	foreach my $article (split / *, */) { #on coupe sur la virgule (cas 05658998,5646987354,876,43436)
			#push @articles_to_export, "A.ARCART='$article'";
			push @articles_to_export, "ARCART='$article'";
	}
}

printf "%s Select des quantites REFLEX\n",get_time();	$old_time=time;


my $sql = <<EOT ;
select ARCART as CODE_ARTICLE,
(select SKQSTK  FROM RFXPRODDTA.reflex.HLSTOCP where SKCART=ARTICLE.ARCART and SKCTST=200 and SKCQAL='AFA') as QTE_AFA,
(select SKQSTK  FROM RFXPRODDTA.reflex.HLSTOCP where SKCART=ARTICLE.ARCART and SKCTST=200 and SKCQAL='AFZ') as QTE_AFZ
from
	RFXPRODDTA.reflex.HLARTIP ARTICLE
EOT

if (scalar(@articles)>0) {
	$sql .= " where (".join(' or ',@articles_to_export).") ";
}

if ($debug) { print $sql; exit; }

# on envoi la requete
if ($reflex->Sql($sql)) {
	print "$sql\n\nSQL Error: ".$reflex->Error()."\n";
	$reflex->Close();
	exit;
}
printf "%s OK. Delay %s\n",get_time(),second2hms(time - $old_time);

######################################## GENERATION DU FICHIER ###########################################################
my %occurences ;
printf "%s Generation du fichier\n",get_time(); $old_time=time;
my $i = 0;
open (F,'+>'.OUTPUT_FILENAME) or die "Ne peux pas générer le fichier de sortie des stocks ".OUTPUT_FILENAME." ($!)";
while($reflex->FetchRow()) {
	my %row = $reflex->DataHash() ;
	my $code_article = trim($row{'CODE_ARTICLE'});

#	if (trim($row{'CODE_ARTICLE'}) eq '07007363') {
#		print "DEBUG 0707363\n";	
#	}
#	print STDERR Dumper(\%row);

	# if (exists $occurences{$code_article.'.AFA'}) { # on a deja vu passé le code article pour cette qualité --> c'est pas normal
	# 	# le code étant deja existant, on prend la valeur qui est rempli.
	# 	if ($occurences{$code_article.'.AFA'} > 0) {
	# 		$row{'QTE_AFA'} = $occurences{$code_article.'.AFA'};
	# 	}

	# }

	# qualité AFA
	my $num_sequence = fill_with_zero($i+1,7);
	print F $num_sequence.CODE_APPLICATION.CODE_INTERFACE.CODE_RUBRIQUE_DESTINATAIRE.CODE_DEPOT_PHYSIQUE.CODE_TYPE_STOCK.CODE_ACTIVITE.
			fill_with_blank($code_article,$field_sizes{'CODE_ARTICLE'}).
			CODE_VARIANTE_LOGISTIQUE_ARTICLE.
			fill_with_blank('MCS',$field_sizes{'CODE_PROPRIETAIRE'}).
			fill_with_blank('AFA',$field_sizes{'CODE_QUALITE'}).
			fill_with_zero(abs($row{'QTE_AFA'}),$field_sizes{'QUANTITE_STOCK'}).
			fill_with_blank($row{'QTE_AFA'} < 0 ? '-':'+',$field_sizes{'SENS_STOCK'}).
			"\n" ;
	$i++;

	# qualité AFZ
	$num_sequence = fill_with_zero($i+1,7);
	print F $num_sequence.CODE_APPLICATION.CODE_INTERFACE.CODE_RUBRIQUE_DESTINATAIRE.CODE_DEPOT_PHYSIQUE.CODE_TYPE_STOCK.CODE_ACTIVITE.
			fill_with_blank($code_article,$field_sizes{'CODE_ARTICLE'}).
			CODE_VARIANTE_LOGISTIQUE_ARTICLE.
			fill_with_blank('MCS',$field_sizes{'CODE_PROPRIETAIRE'}).
			fill_with_blank('AFZ',$field_sizes{'CODE_QUALITE'}).
			fill_with_zero(abs($row{'QTE_AFZ'}),$field_sizes{'QUANTITE_STOCK'}).
			fill_with_blank($row{'QTE_AFZ'} < 0 ? '-':'+',$field_sizes{'SENS_STOCK'}).
			"\n" ;
	$i++;

	
}
close F;

printf "%s OK. Delay %s\n",get_time(),second2hms(time - $old_time);

#print STDERR Dumper(\%occurences); exit;

######################################## ENVOI SUR LE FTP ###########################################################
printf "%s Upload du fichier sur FTP\n",get_time(); $old_time=time;
 my $ftp = Net::FTP->new($cfg->{'FTP_HOST'}, Debug => 0) 			or die "Cannot connect to some.host.name: $@";
    $ftp->login($cfg->{'FTP_USER'},$cfg->{'FTP_PASS'})				or die "Cannot login ", $ftp->message;
    $ftp->cwd($test ? $cfg->{'FTP_PATH_TEST'} : $cfg->{'FTP_PATH'})	or die "Cannot change working directory ", $ftp->message;
    print STDERR "# = 100Ko\n";
    $ftp->hash(\*STDERR, 1024 * 100);
    $ftp->put(OUTPUT_FILENAME)										or die "get failed ", $ftp->message;
    $ftp->quit;
printf "%s OK. Delay %s\n",get_time(),second2hms(time - $old_time);

require 'save-file-to-zip.pl';