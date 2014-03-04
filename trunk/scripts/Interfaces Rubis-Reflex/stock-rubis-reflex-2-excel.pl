#!/usr/bin/perl
use strict;

use Data::Dumper;
use Win32::ODBC;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
use File::Path;
use File::Copy;
use File::Basename;
require 'useful.pl'; # load get_time / second2hms
use Phpconst2perlconst ;

use constant OUTPUT_FILENAME => 'output/stock-rubis-reflex-2-excel.csv';

###################################################################################################################################################
my $old_time = 0;
my $cfg = new Phpconst2perlconst(-file => 'config.php');
my $prefix_base_rubis = $cfg->{LOGINOR_PREFIX_BASE_PROD};
my $rubis = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";
my $prefix_base_reflex = $cfg->{REFLEX_PREFIX_BASE};
my $reflex = new Win32::ODBC('DSN='.$cfg->{REFLEX_DSN}.';UID='.$cfg->{REFLEX_USER}.';PWD='.$cfg->{REFLEX_PASS}.';') or die "Ne peux pas se connecter à Reflex";


################# SELECT RUBIS ####################################################################################################################
printf "%s Select des stock rubis\n",get_time(); $old_time=time;
my $sql_rubis = <<EOT ;
select ARTICLE.NOART as ARTICLE,ARTICLE.DESI1 as DESIGNATION1,ARTICLE.DESI2 as DESIGNATION2,ARTICLE.FOUR1 as FOURNISSEUR,AF.REFFO as REF_FOURNISSEUR,STOCK.QTINV as QTE_RUBIS,CONDI as CONDITIONNEMENT_PREPA,ARTD4 as UNITE_PREPA,CDCON as CONDITIONNEMENT_DIVISIBLE,CDKIT as KIT,ETARE as SUSPENDU
from			${prefix_base_rubis}GESTCOM.AARTICP1 ARTICLE
	left join	${prefix_base_rubis}GESTCOM.AARFOUP1 AF
		on ARTICLE.NOART=AF.NOART and AF.NOFOU=ARTICLE.FOUR1
	left join	${prefix_base_rubis}GESTCOM.ASTOCKP1 STOCK
				on ARTICLE.NOART=STOCK.NOART and STOCK.DEPOT='AFA'
where
		ARTICLE.ARDIV='NON'
	and	ARTICLE.CDKIT='NON'
--	and (ARTICLE.NOART='06000312')
ORDER BY ARTICLE.NOART ASC
EOT

my %stock = ();
if ($rubis->Sql($sql_rubis))  { die "SQL Rubis STOCK failed : ", $rubis->Error(); }
while ($rubis->FetchRow()) {
	my %row = $rubis->DataHash() ;
	$stock{$row{'ARTICLE'}} = \%row;
	if ($row{'CONDITIONNEMENT_PREPA'} && $row{'CONDITIONNEMENT_DIVISIBLE'} eq 'NON') {
		$stock{$row{'ARTICLE'}}->{'QTE_RUBIS_PREPA'} = $row{'QTE_RUBIS'} / $row{'CONDITIONNEMENT_PREPA'};
	} else {
		$stock{$row{'ARTICLE'}}->{'QTE_RUBIS_PREPA'} = $row{'QTE_RUBIS'} ;
	}
	$stock{$row{'ARTICLE'}}->{'PRESENT_DANS_REFLEX'} = 0; # on le marque non présent dans reflex
}

printf "%s OK. Delay %s\n",get_time(),second2hms(time - $old_time);


################# SELECT REFLEX #########################################################"
printf "%s Select des stock reflex\n",get_time(); $old_time=time;
my $sql_reflex = <<EOT ;
select GECART,GEQGEI as QTE_REFLEX,(EMC1EM + ' '+ EMC2EM + ' '+ EMC3EM + ' ' + EMC4EM + ' ' + EMC5EM) as EMP
	from		${prefix_base_reflex}.HLGEINP GEI
	left join	${prefix_base_reflex}.HLSUPPP SUPPORT
		on GEI.GENSUP=SUPPORT.SUNSUP
	left join ${prefix_base_reflex}.HLEMPLP EMPLACEMENT
		on SUPPORT.SUNEMP=EMPLACEMENT.EMNEMP
where
		GEI.GECTST='200'		-- obligatoire pour le stock réel
--	and (GEI.GECART='06000312')
EOT

if ($reflex->Sql($sql_reflex))  { die "SQL Reflex STOCK failed : ", $reflex->Error(); }
while ($reflex->FetchRow()) {
	my %row = $reflex->DataHash() ;
	if (exists $stock{$row{'GECART'}}) { # l'article a du stock dans rubis
		$stock{$row{'GECART'}}->{'REFLEX'}->{$row{'EMP'}} = $row{'QTE_REFLEX'} ;

		#$stock{$row{'GECART'}}->{'EMPLACEMENT'} = $row{'EMP'};
		$stock{$row{'GECART'}}->{'QTE_REFLEX'} = exists $stock{$row{'GECART'}}->{'QTE_REFLEX'} ? $stock{$row{'GECART'}}->{'QTE_REFLEX'} + $row{'QTE_REFLEX'} : $row{'QTE_REFLEX'} ;
	} else { # l'article n'a pas de stock rubis
		$stock{$row{'GECART'}} = {	'REFLEX' => {	$row{'EMP'} => $row{'QTE_REFLEX'} 	} 	};
		$stock{$row{'GECART'}}->{'QTE_REFLEX'} = $row{'QTE_REFLEX'};
	}	
}

printf "%s Select des articles reflex\n",get_time(); $old_time=time;
my $sql_reflex = <<EOT ;
select ARCART from ${prefix_base_reflex}.HLARTIP ARTICLE
EOT

if ($reflex->Sql($sql_reflex))  { die "SQL Reflex Article failed : ", $rubis->Error(); }
while ($reflex->FetchRow()) {
	my %row = $reflex->DataHash() ;
	if (exists $stock{$row{'ARCART'}}) { # l'article a du stock dans rubis
		$stock{$row{'ARCART'}}->{'PRESENT_DANS_REFLEX'} = 1; # on le marque présent dans reflex
	}	
}

printf "%s OK. Delay %s\n",get_time(),second2hms(time - $old_time);

#print Dumper(\%stock);

printf "%s Generation du fichier\n",get_time(); $old_time=time;

mkpath(dirname(OUTPUT_FILENAME)) if !-d dirname(OUTPUT_FILENAME) ;
open(CSV,'+>'.OUTPUT_FILENAME) or die "ne peux pas creer le fichier de sortie '".OUTPUT_FILENAME."' ($!)";
print CSV join(';',qw/ARTICLE QTE_REFLEX QTE_RUBIS_PREPA DELTA PRESENT_DANS_REFLEX SUSPENDU KIT DESIGNATION1 DESIGNATION2 FOURNISSEUR REF_FOURNISSEUR QTE_RUBIS CONDIOTIONNEMENT_PREPA UNITE_PREPA EMPLACEMENTS/)."\n";

my $i=0;
my $j=0;
while(my ($article,$data) = each(%stock) ) {
	my %tmp = %$data;
	# le sprintf est obligatoire car sinon les différence de nombre égaux ne donne pas 0
	if (sprintf('%0.4f',$tmp{'QTE_REFLEX'}) != sprintf('%0.4f',$tmp{'QTE_RUBIS_PREPA'})) {
		$tmp{'DELTA'}	 		= abs($tmp{'QTE_REFLEX'} - $tmp{'QTE_RUBIS_PREPA'});
		$tmp{'QTE_REFLEX'} 		= dot2comma($tmp{'QTE_REFLEX'});
		$tmp{'QTE_RUBIS_PREPA'} = dot2comma($tmp{'QTE_RUBIS_PREPA'});
		$tmp{'DELTA'} 			= dot2comma($tmp{'DELTA'});
		$tmp{'QTE_RUBIS'} 		= dot2comma($tmp{'QTE_RUBIS'});
		print CSV join(';',@tmp{qw/ARTICLE QTE_REFLEX QTE_RUBIS_PREPA DELTA PRESENT_DANS_REFLEX SUSPENDU KIT DESIGNATION1 DESIGNATION2 FOURNISSEUR REF_FOURNISSEUR QTE_RUBIS CONDIOTIONNEMENT_PREPA UNITE_PREPA/});

		my @emplacements = ();
		while(my ($emp,$qte) = each(%{$tmp{'REFLEX'}}) ) {
			push @emplacements, "$emp ($qte)";
		}
		print CSV ';'.join('  /  ',@emplacements)."\n";
		$i++;

	} else { # si il n'y a pas de différence de stock
		if ($tmp{'PRESENT_DANS_REFLEX'} == 0 && $tmp{'ARTICLE'} !~ /[a-z]/i) { # si l'article n'est pas dans reflex et n'est pas un article, on l'affiche quand meme
			print CSV join(';',@tmp{qw/ARTICLE QTE_REFLEX QTE_RUBIS_PREPA DELTA PRESENT_DANS_REFLEX SUSPENDU KIT DESIGNATION1 DESIGNATION2 FOURNISSEUR REF_FOURNISSEUR QTE_RUBIS CONDIOTIONNEMENT_PREPA UNITE_PREPA/});
			print CSV ";\n";
			$j++;
		}
	}
}

close(CSV);

printf "%s %d difference(s) exported\n",get_time(),$i;
printf "%s + %d article not in Reflex\n",get_time(),$j;

# cree une sauvegarde du fichier
printf "%s Create save file\n",get_time();
mkpath(dirname(OUTPUT_FILENAME).'/sauvegarde') if !-d dirname(OUTPUT_FILENAME).'/sauvegarde' ;
copy(	OUTPUT_FILENAME,
		dirname(OUTPUT_FILENAME).'/sauvegarde/'.strftime("%Y-%m-%d %Hh%Mm%Ss ", localtime).basename(OUTPUT_FILENAME)
	);