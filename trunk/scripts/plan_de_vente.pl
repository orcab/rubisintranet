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

my $loginor = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";
print print_time()."Select des familles actives ...";
my $sql = <<EOT;
select		AFCNI,AFCAC,AFCFA,AFCSF,AFCCH,AFCSC,ACFLI
from		AFAGESTCOM.AFAMILP1
where		AFCTY='FA1'
order by	AFCAC asc, AFCFA asc, AFCSF asc, AFCCH asc, AFCSC asc
EOT
$loginor->Sql($sql); # regarde les articles actifs
print " ok\n";


print print_time()."Insertion du plan de vente dans la base ... ";
my $mysql = Mysql->connect($cfg->{MYSQL_HOST},$cfg->{MYSQL_BASE},$cfg->{MYSQL_USER},$cfg->{MYSQL_PASS}) or die "Peux pas se connecter a mysql";
$mysql->selectdb($cfg->{MYSQL_BASE}) or die "Peux pas selectionner la base mysql";


# drop table
$dbh->query("DROP TABLE IF EXISTS pdvente;");

# create table
my $create_table = <<EOT;
CREATE TABLE IF NOT EXISTS pdvente (
  id int(11) NOT NULL auto_increment,
  `code` varchar(3) NOT NULL,
  libelle varchar(255) NOT NULL,
  activite_pere varchar(3) default NULL,
  famille_pere varchar(3) default NULL,
  sousfamille_pere varchar(3) default NULL,
  chapitre_pere varchar(3) default NULL,
  chemin varchar(19) default NULL,
  niveau int(3) NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
EOT
$dbh->query($create_table);

# create index sur le chemin
$dbh->query("CREATE UNIQUE INDEX chemin ON pdvente (`chemin`)");

#my ($code_activite,$libelle_activite,$code_famille,$libelle_famille,$code_sousfamille,$libelle_sousfamille,$code_chapitre,$libelle_chapitre,$code_souschapitre,$libelle_souschapitre);

while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	map { $row{$_} = trim(quotify($row{$_})) ; } keys %row ;

	if		($row{'AFCNI'} eq 'ACT') { # activité
		$dbh->query("INSERT INTO pdvente (code,libelle,chemin,niveau)                                                           VALUES ('$row{AFCAC}','$row{ACFLI}','$row{AFCAC}',                                                1);");
	}
	elsif	($row{'AFCNI'} eq 'FAM') { # famille 
		$dbh->query("INSERT INTO pdvente (code,libelle,chemin,niveau,activite_pere)                                             VALUES ('$row{AFCFA}','$row{ACFLI}','$row{AFCAC}.$row{AFCFA}',                                    2,'$row{AFCAC}');");
	}
	elsif	($row{'AFCNI'} eq 'SFA') { # sous famille 
		$dbh->query("INSERT INTO pdvente (code,libelle,chemin,niveau,activite_pere,famille_pere)                                VALUES ('$row{AFCSF}','$row{ACFLI}','$row{AFCAC}.$row{AFCFA}.$row{AFCSF}',                        3,'$row{AFCAC}','$row{AFCFA}');");
	}
	elsif	($row{'AFCNI'} eq 'CHA') { # chapitre 
		$dbh->query("INSERT INTO pdvente (code,libelle,chemin,niveau,activite_pere,famille_pere,sousfamille_pere)               VALUES ('$row{AFCCH}','$row{ACFLI}','$row{AFCAC}.$row{AFCFA}.$row{AFCSF}.$row{AFCCH}',            4,'$row{AFCAC}','$row{AFCFA}','$row{AFCSF}');");
	}
	elsif	($row{'AFCNI'} eq 'SCH') { # sous chapitre 
		$dbh->query("INSERT INTO pdvente (code,libelle,chemin,niveau,activite_pere,famille_pere,sousfamille_pere,chapitre_pere) VALUES ('$row{AFCSC}','$row{ACFLI}','$row{AFCAC}.$row{AFCFA}.$row{AFCSF}.$row{AFCCH}.$row{AFCSC}',5,'$row{AFCAC}','$row{AFCFA}','$row{AFCSF}','$row{AFCCH}');");
	}
}
close F;

print "ok\n";

END:
print print_time()."END\n\n";



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