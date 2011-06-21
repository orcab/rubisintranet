#!/usr/bin/perl

use Data::Dumper;
use Win32::ODBC;
use strict ;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
$|=1;

print print_time()."START\n";
my $cfg = new Phpconst2perlconst(-file => '../inc/config.php');

my $prefix_base_rubis = $cfg->{LOGINOR_PREFIX_BASE};
my $loginor  = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";
my $loginor2 = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";

my $yyyymmdd = strftime "%Y%m%d",localtime;
my $siecle	= substr($yyyymmdd , 0,2);
my $annee	= substr($yyyymmdd , 2,2);
my $mois	= substr($yyyymmdd , 4,2);
my $jour	= substr($yyyymmdd , 6,2);

# selectionne les lignes active sans gencode
my $sql = <<EOT ;
select		A.NOART, AF.NOFOU, A.LUACH
from		${prefix_base_rubis}GESTCOM.AARTICP1 A
left join	${prefix_base_rubis}GESTCOM.AARFOUP1 AF
	on A.NOART=AF.NOART and AF.NOFOU='CESAFA'
EOT
#print $sql;
$loginor->Sql($sql);
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash();
	#print %row;
	#exit;

	if ($row{'NOFOU'} ne 'CESAFA') { # Si la fiche article-fournisseur de l'article sur CESAFA n'existe pas
		$loginor2->Sql("insert INTO ${prefix_base_rubis}GESTCOM.AARFOUP1 (NOART,AFUNI,USAFE,DAFCS,DAFCA,DAFCM,DAFCJ,WSAFR,DAFMS,DAFMA,DAFMM,DAFMJ,AGENC,NOFOU,SFOUR,AFDAS,AFDAA,AFDAM,AFDAJ,GESAF,ARF01,ARF03) VALUES ('$row{NOART}','$row{LUACH}','AFBP','$siecle','$annee','$mois','$jour','AF14.46','$siecle','$annee','$mois','$jour','AFL','CESAFA','1','$siecle','$annee','$mois','$jour','OUI','1','OUI')");
		#print "insert ${prefix_base_rubis}GESTCOM.AARFOUP1 (NOART,AFUNI,USAFE,DAFCS,DAFCA,DAFCM,DAFCJ,WSAFR,DAFMS,DAFMA,DAFMM,DAFMJ,AGENC,NOFOU,SFOUR,AFDAS,AFDAA,AFDAM,AFDAJ,GESAF,ARF01,ARF03) VALUES ('$row{NOART}','$row{LUACH}','AFBP','$siecle','$annee','$mois','$jour','AF14.46','$siecle','$annee','$mois','$jour','AFL','CESAFA','1','$siecle','$annee','$mois','$jour','OUI','1','OUI')\n";
		print print_time()."INSERT '$row{NOART}'\n";
	}	
}

$loginor->Close();
$loginor2->Close();

print print_time()."END\n\n";

sub print_time {
	print strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
	return '';
}