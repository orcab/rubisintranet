#!/usr/bin/perl

# generation des articles
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

#connexion a loginor pour recuperer les infos
my $loginor = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";
print print_time()."Select des articles actifs ...";
my $sql = <<EOT;
select	A.NOART as CODE_ARTICLE,
		DESI1 as DESIGNATION1,DESI2 as DESIGNATION2,DESI3 as DESIGNATION3,
		GENCO as GENCOD,SERST as SERVI_SUR_STOCK,CONDI as CONDITIONNEMENT,SURCO as SURCONDITIONNEMENT,LUNTA as UNITE,
		ACTIV as ACTIVITE,FAMI1 as FAMILLE,SFAM1 as SOUSFAMILLE,ART04 as CHAPITRE,ART05 as SOUSCHAPITRE,
		NOMFO as FOURNISSEUR,REFFO as REF_FOURNISSEUR,AFOGE as REF_FOURNISSEUR_CONDENSEE,
		PVEN1 as PRIX_NET
from	AFAGESTCOM.AARTICP1 A,
		AFAGESTCOM.AARFOUP1 A_F,
		AFAGESTCOM.AFOURNP1 F,
		AFAGESTCOM.ATARIFP1 T
where	ETARE=''
	and A.NOART=A_F.NOART
	and A_F.AGENC='AFA'
	and T.AGENC  ='AFA'
	and A_F.NOFOU=F.NOFOU
	and A.NOART=T.NOART
	and A.FOUR1=F.NOFOU
EOT
$loginor->Sql($sql); # regarde les articles actifs
print " ok\n";

my $mysql = Mysql->connect($cfg->{MYSQL_HOST},$cfg->{MYSQL_BASE},$cfg->{MYSQL_USER},$cfg->{MYSQL_PASS}) or die "Peux pas se connecter a mysql";
$mysql->selectdb($cfg->{MYSQL_BASE}) or die "Peux pas selectionner la base mysql";

print print_time()."Suppression de la base ...";
$dbh->query("TRUNCATE TABLE article;");
print " ok\n";


print print_time()."Insertion des articles ...";
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	map { $row{$_} = trim(quotify($row{$_})) ; } keys %row ;

	my $servi_sur_stock = $row{'SERVI_SUR_STOCK'} eq 'OUI' ? 1:0;

	my @chemin = ();
	push @chemin, $row{'ACTIVITE'}		if $row{'ACTIVITE'} ;
	push @chemin, $row{'FAMILLE'}		if $row{'FAMILLE'} ;
	push @chemin, $row{'SOUSFAMILLE'}	if $row{'SOUSFAMILLE'} ;
	push @chemin, $row{'CHAPITRE'}		if $row{'CHAPITRE'} ;
	push @chemin, $row{'SOUSCHAPITRE'}	if $row{'SOUSCHAPITRE'} ;
	my $chemin = join('.',@chemin);
	
	$dbh->query("INSERT INTO article (code_article,designation,gencod,servi_sur_stock,conditionnement,surconditionnement,unite,activite,famille,sousfamille,chapitre,souschapitre,chemin,fournisseur,ref_fournisseur,ref_fournisseur_condensee,prix_brut,prix_net) VALUES ('$row{CODE_ARTICLE}','".join("\n",($row{'DESIGNATION1'},$row{'DESIGNATION2'},$row{'DESIGNATION3'}))."','$row{GENCOD}',$servi_sur_stock,'$row{CONDITIONNEMENT}','$row{SURCONDITIONNEMENT}','$row{UNITE}','$row{ACTIVITE}','$row{FAMILLE}','$row{SOUSFAMILLE}','$row{CHAPITRE}','$row{SOUSCHAPITRE}','$chemin','$row{FOURNISSEUR}','$row{REF_FOURNISSEUR}','$row{REF_FOURNISSEUR_CONDENSEE}','$row{PRIX_NET}','$row{PRIX_NET}');") or warn( Dumper(\%row) );
}
close F ;
print " ok\n";


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