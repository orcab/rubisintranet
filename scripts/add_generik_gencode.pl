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


# selectionne les lignes active sans gencode
my $sql = <<EOT ;
select	NOART
from	${prefix_base_rubis}GESTCOM.AARTICP1
where	GENCO = ''	-- pas de code barre
EOT
#print $sql;
$loginor->Sql($sql);
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash();
	#print %row;
	#exit;

	# calcul le code barre générique a partir du code article
	if ($row{'NOART'} =~ /^\d+$/) { # si le code article est composé uniquement de chiffre
		my $gencod_mcs	= '2'.$row{'NOART'} . ('0' x (12 - length('2'.$row{'NOART'})))  ;
		my @gencod	= split //,$gencod_mcs ;
		my $cle		= 3*($gencod[1]+$gencod[3]+$gencod[5]+$gencod[7]+$gencod[9]+$gencod[11]) + ($gencod[0]+$gencod[2]+$gencod[4]+$gencod[6]+$gencod[8]+$gencod[10])  ;
		$gencod_mcs .= substr((10 - substr($cle,-1)),-1); # calcul de la clé de check

		$loginor2->Sql("update ${prefix_base_rubis}GESTCOM.AARTICP1 set GENCO='$gencod_mcs' where NOART='".$row{'NOART'}."'");
		$loginor2->Sql("update ${prefix_base_rubis}GESTCOM.AARFOUP1 set AFOG3='$gencod_mcs' where NOART='".$row{'NOART'}."' and NOFOU='CESAFA'");
		print print_time()."UPDATE '$row{NOART}'\n";
	} else {
		print print_time()."SKIP '$row{NOART}'\n";
	}
	
}

$loginor->Close();
$loginor2->Close();

print print_time()."END\n\n";

sub print_time {
	print strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
	return '';
}