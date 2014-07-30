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

my $sql = <<EOT ;
select		NOART
from		${prefix_base_rubis}GESTCOM.ASTOFIP1
where		DEPOT='AFL' and STFOU='CESAFA'
EOT
#print $sql;
$loginor->Sql($sql);
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash();
	#print %row;
	#exit;
	$row{'NOART'} =~ s/^\s*|\s*$//g; # trim

	$loginor2->Sql("select FOUR1 from ${prefix_base_rubis}GESTCOM.AARTICP1 where NOART='$row{NOART}'");
	$loginor2->FetchRow();
	my %row2 = $loginor2->DataHash();
	
	print print_time()."UPDATE STOCK AFL '$row{NOART}' to fournisseur '$row2{FOUR1}'\n";
	if ($loginor2->Sql("update ${prefix_base_rubis}GESTCOM.ASTOFIP1 set STFOU='$row2{FOUR1}' where NOART='$row{NOART}' and DEPOT='AFL'")) {
		print "erreur 2 sur '$row{NOART}'\n";
	}
	#exit;
}

$loginor->Close();
$loginor2->Close();

print print_time()."END\n\n";

sub print_time {
	print strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
	return '';
}