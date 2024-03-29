#!/usr/bin/perl

# generation du plan de vente au format MYSQL
use Mysql;
use Win32::ODBC;
use Data::Dumper;
use strict ;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
$| = 1; # active le flush direct

print print_time()."START\n";

my $cfg = new Phpconst2perlconst(-file => '../inc/config.php');
my $prefix_base_rubis = $cfg->{LOGINOR_PREFIX_BASE};

my $loginor = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter � rubis";
print print_time()."Select des familles actives ...";
my $sql = <<EOT;
select		AFCNI,AFCAC,AFCFA,AFCSF,AFCCH,AFCSC,ACFLI
from		${prefix_base_rubis}GESTCOM.AFAMILP1
where		AFCTY='FA1' and AFC01=''
order by	AFCAC asc, AFCFA asc, AFCSF asc, AFCCH asc, AFCSC asc
EOT
$loginor->Sql($sql); # regarde les articles actifs
print " ok\n";


my $mysql = Mysql->connect($cfg->{MYSQL_HOST},$cfg->{MYSQL_BASE},$cfg->{MYSQL_USER},$cfg->{MYSQL_PASS}) or die "Peux pas se connecter a mysql";
$mysql->selectdb($cfg->{MYSQL_BASE}) or die "Peux pas selectionner la base mysql";

print print_time()."Suppression de la base ...";
$mysql->query(join('',<DATA>)); # construction de la table si elle n'existe pas
$mysql->query("TRUNCATE TABLE pdvente;");
print " ok\n";


# $libelle_complet{chemin} = "libelle_activit�,libelle_famille,...";
my %libelle_complet = ();

print print_time()."Insertion du plan de vente dans la base ... ";
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	map { $row{$_} = trim(quotify($row{$_})) ; } keys %row ;

	if		($row{'AFCNI'} eq 'ACT') { # activit�
		$libelle_complet{"$row{AFCAC}"} = "$row{ACFLI}";
		$mysql->query("INSERT INTO pdvente (code,libelle,chemin,niveau,libelle_complet)                                                           VALUES ('$row{AFCAC}','$row{ACFLI}','$row{AFCAC}',1,'".$libelle_complet{"$row{AFCAC}"}."');");
	}
	elsif	($row{'AFCNI'} eq 'FAM') { # famille
		$libelle_complet{"$row{AFCAC}.$row{AFCFA}"} = $libelle_complet{"$row{AFCAC}"}.">$row{ACFLI}";
		$mysql->query("INSERT INTO pdvente (code,libelle,chemin,niveau,libelle_complet,activite_pere)                                             VALUES ('$row{AFCFA}','$row{ACFLI}','$row{AFCAC}.$row{AFCFA}',2,'".$libelle_complet{"$row{AFCAC}.$row{AFCFA}"}."','$row{AFCAC}');");
	}

	elsif	($row{'AFCNI'} eq 'SFA') { # sous famille
		$libelle_complet{"$row{AFCAC}.$row{AFCFA}.$row{AFCSF}"} = $libelle_complet{"$row{AFCAC}.$row{AFCFA}"}.">$row{ACFLI}";
		$mysql->query("INSERT INTO pdvente (code,libelle,chemin,niveau,libelle_complet,activite_pere,famille_pere)                                VALUES ('$row{AFCSF}','$row{ACFLI}','$row{AFCAC}.$row{AFCFA}.$row{AFCSF}',3,'".$libelle_complet{"$row{AFCAC}.$row{AFCFA}.$row{AFCSF}"}."','$row{AFCAC}','$row{AFCFA}');");
	}

	elsif	($row{'AFCNI'} eq 'CHA') { # chapitre
		$libelle_complet{"$row{AFCAC}.$row{AFCFA}.$row{AFCSF}.$row{AFCCH}"} = $libelle_complet{"$row{AFCAC}.$row{AFCFA}.$row{AFCSF}"}.">$row{ACFLI}";
		$mysql->query("INSERT INTO pdvente (code,libelle,chemin,niveau,libelle_complet,activite_pere,famille_pere,sousfamille_pere)               VALUES ('$row{AFCCH}','$row{ACFLI}','$row{AFCAC}.$row{AFCFA}.$row{AFCSF}.$row{AFCCH}',4,'".$libelle_complet{"$row{AFCAC}.$row{AFCFA}.$row{AFCSF}.$row{AFCCH}"}."','$row{AFCAC}','$row{AFCFA}','$row{AFCSF}');");
	}
	elsif	($row{'AFCNI'} eq 'SCH') { # sous chapitre
		$libelle_complet{"$row{AFCAC}.$row{AFCFA}.$row{AFCSF}.$row{AFCCH}.$row{AFCSC}"} = $libelle_complet{"$row{AFCAC}.$row{AFCFA}.$row{AFCSF}.$row{AFCCH}"}.">$row{ACFLI}";
		$mysql->query("INSERT INTO pdvente (code,libelle,chemin,niveau,libelle_complet,activite_pere,famille_pere,sousfamille_pere,chapitre_pere) VALUES ('$row{AFCSC}','$row{ACFLI}','$row{AFCAC}.$row{AFCFA}.$row{AFCSF}.$row{AFCCH}.$row{AFCSC}',5,'".$libelle_complet{"$row{AFCAC}.$row{AFCFA}.$row{AFCSF}.$row{AFCCH}.$row{AFCSC}"}."','$row{AFCAC}','$row{AFCFA}','$row{AFCSF}','$row{AFCCH}');");
	}
}
close F;

print "ok\n";

END:
print print_time()."END\n\n";

#####################################################################################################################################################

sub trim {
	my $t = shift;
	$t =~ s/^\s+//g;
	$t =~ s/\s+$//g;
	$t =~ s/\n/ /g;
	return $t ;
}

sub quotify {
	my $t = shift;
	$t =~ s/'/''/g;
	return $t ;
}

sub print_time {
	print strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
	return '';
}

__DATA__
CREATE TABLE IF NOT EXISTS `pdvente` (
  `id` int(11) NOT NULL auto_increment,
  `code` varchar(3) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `activite_pere` varchar(3) default NULL,
  `famille_pere` varchar(3) default NULL,
  `sousfamille_pere` varchar(3) default NULL,
  `chapitre_pere` varchar(3) default NULL,
  `chemin` varchar(19) default NULL,
  `niveau` int(3) NOT NULL,
  `libelle_complet` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `chemin` (`chemin`),
  KEY `libelle` (`libelle`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;