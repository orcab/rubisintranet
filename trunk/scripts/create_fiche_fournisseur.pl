#!/usr/bin/perl

use Data::Dumper;
use Win32::ODBC;
use strict ;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
require 'Interfaces Rubis-Reflex/useful.pl';
use Phpconst2perlconst ;
$|=1;

print get_time()."START\n";
my $cfg = new Phpconst2perlconst(-file => '../inc/config.php');

my $prefix_base_rubis = $cfg->{LOGINOR_PREFIX_BASE};
my $loginor  = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";
my $loginor2 = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";


$loginor2->{RaiseError} = 1;
sub err_handler {
	my($state, $msg, $native, $rc, $status) = @_;
	print Dumper(@_);
 	return 1; # propagate error
}
$loginor2->{odbc_err_handler} = \&err_handler;

my $yyyymmdd = strftime "%Y%m%d",localtime;
my $siecle	= substr($yyyymmdd , 0,2);
my $annee	= substr($yyyymmdd , 2,2);
my $mois	= substr($yyyymmdd , 4,2);
my $jour	= substr($yyyymmdd , 6,2);

# pour chaque article dans la DATA list
while(<DATA>) {
	chomp;
	my ($code_article,$old_fournisseur,$new_fournisseur) = split /\t/;
	$code_article 		= trim($code_article);
	$old_fournisseur 	= trim($old_fournisseur);
	$new_fournisseur 	= trim($new_fournisseur);

	# pour chaque fiche, on cree une copie sur le nouveau code fournisseur
	# selectionne l'ancien
	my $sql = <<EOT ;
select		*
from		${prefix_base_rubis}GESTCOM.AARFOUP1 AF
where			NOART='$code_article'
			and NOFOU='$old_fournisseur'
EOT

	my (@keys,@values);
	
	$loginor->Sql($sql);
	$loginor->FetchRow();
	my %row = $loginor->DataHash();
	#on stock les données dans l'ordre
	while(my($key,$value) = each(%row)) {
		$value = $new_fournisseur 	if $key eq 'NOFOU'; # change fournisseur
		$value = $siecle 			if $key eq 'DAFCS';
		$value = $siecle 			if $key eq 'DAFMS';
		$value = $annee 			if $key eq 'DAFCA';
		$value = $annee 			if $key eq 'DAFMA';
		$value = $mois 				if $key eq 'DAFCM';
		$value = $mois 				if $key eq 'DAFMM';
		$value = $jour 				if $key eq 'DAFCJ';
		$value = $jour 				if $key eq 'DAFMJ';
		$value = 'AFBP'				if $key eq 'USAFE';
		$value = 'AF14.46'			if $key eq 'WSAFR';
		$value = ''					if $key eq 'ETAFE';
		push(@keys,"$key");
		push(@values,"'$value'");
	}

	my $sql = "insert into ${prefix_base_rubis}GESTCOM.AARFOUP1 (".join(',',@keys).") values (".join(',',@values).")";
	print "CREATE  article-fournisseur $code_article / $new_fournisseur\n";
	#print $sql."\n";
	$loginor2->Sql($sql);

	my $sql = "update ${prefix_base_rubis}GESTCOM.AARFOUP1 set ETAFE='S' where NOART='$code_article' and NOFOU='$old_fournisseur' and AGENC='AFA'";
	print "SUSPEND article-fournisseur $code_article / $old_fournisseur\n";
	#print $sql."\n";
	$loginor2->Sql($sql);

	my $sql = "update ${prefix_base_rubis}GESTCOM.AARTICP1 set FOUR1='$new_fournisseur' where NOART='$code_article'";
	print "UPDATE FOUR1 article $code_article / $new_fournisseur\n";
	#print $sql."\n";
	$loginor2->Sql($sql);

	my $sql = "update ${prefix_base_rubis}GESTCOM.ASTOFIP1 set STFOU='$new_fournisseur' where NOART='$code_article'";
	print "UPDATE  stock $code_article / $new_fournisseur\n";
	#print $sql."\n";
	$loginor2->Sql($sql);

	my $sql = "update ${prefix_base_rubis}GESTCOM.AARTICP1 set DESI2='$new_fournisseur $row{REFFO}' where NOART='$code_article'";
	print "UPDATE DESI2 article $code_article / $new_fournisseur\n";
	#print $sql."\n";
	$loginor2->Sql($sql);

	#last;
}

$loginor->Close();
$loginor2->Close();

print get_time()."END\n\n";


#################################################################################################
__DATA__
01012476       	ROTEX 	DAIKIN
01012477       	ROTEX 	DAIKIN
01012478       	ROTEX 	DAIKIN
01012479       	ROTEX 	DAIKIN
01012480       	ROTEX 	DAIKIN
01012481       	ROTEX 	DAIKIN
01012482       	ROTEX 	DAIKIN
01012483       	ROTEX 	DAIKIN
01013343       	ROTEX 	DAIKIN
01013344       	ROTEX 	DAIKIN
01013345       	ROTEX 	DAIKIN
01013346       	ROTEX 	DAIKIN
01013347       	ROTEX 	DAIKIN
01013348       	ROTEX 	DAIKIN
01013770       	ROTEX 	DAIKIN
01013771       	ROTEX 	DAIKIN
01013772       	ROTEX 	DAIKIN
01013773       	ROTEX 	DAIKIN
01013774       	ROTEX 	DAIKIN
01013775       	ROTEX 	DAIKIN
01013776       	ROTEX 	DAIKIN
01014190       	ROTEX 	DAIKIN
01014207       	ROTEX 	DAIKIN
01014443       	ROTEX 	DAIKIN
01014444       	ROTEX 	DAIKIN
01014553       	ROTEX 	DAIKIN
01015446       	ROTEX 	DAIKIN
01015447       	ROTEX 	DAIKIN
01015448       	ROTEX 	DAIKIN
01015449       	ROTEX 	DAIKIN
01015450       	ROTEX 	DAIKIN
01015458       	ROTEX 	DAIKIN
01015459       	ROTEX 	DAIKIN
01015460       	ROTEX 	DAIKIN
01015461       	ROTEX 	DAIKIN
01015462       	ROTEX 	DAIKIN
01015635       	ROTEX 	DAIKIN
01015855       	ROTEX 	DAIKIN
01018751       	ROTEX 	DAIKIN
01018752       	ROTEX 	DAIKIN
01018753       	ROTEX 	DAIKIN
01018754       	ROTEX 	DAIKIN
01018762       	ROTEX 	DAIKIN
01018763       	ROTEX 	DAIKIN
01018764       	ROTEX 	DAIKIN
01018807       	ROTEX 	DAIKIN
01018810       	ROTEX 	DAIKIN
01018811       	ROTEX 	DAIKIN
01018812       	ROTEX 	DAIKIN
01018814       	ROTEX 	DAIKIN
01018817       	ROTEX 	DAIKIN
01018896       	ROTEX 	DAIKIN
01018943       	ROTEX 	DAIKIN
01018951       	ROTEX 	DAIKIN
01020065       	ROTEX 	DAIKIN
01020067       	ROTEX 	DAIKIN
01020076       	ROTEX 	DAIKIN
01020077       	ROTEX 	DAIKIN
01020078       	ROTEX 	DAIKIN
01020181       	ROTEX 	DAIKIN
01020355       	ROTEX 	DAIKIN
01020407       	ROTEX 	DAIKIN
01020411       	ROTEX 	DAIKIN
01020412       	ROTEX 	DAIKIN
01020535       	ROTEX 	DAIKIN
01020536       	ROTEX 	DAIKIN
01020537       	ROTEX 	DAIKIN
01020538       	ROTEX 	DAIKIN
01020578       	ROTEX 	DAIKIN
01020712       	ROTEX 	DAIKIN
01020713       	ROTEX 	DAIKIN
01020714       	ROTEX 	DAIKIN
01020715       	ROTEX 	DAIKIN
01020716       	ROTEX 	DAIKIN
01020717       	ROTEX 	DAIKIN
01020718       	ROTEX 	DAIKIN
01020719       	ROTEX 	DAIKIN
01020833       	ROTEX 	DAIKIN
01020834       	ROTEX 	DAIKIN
01020874       	ROTEX 	DAIKIN
01021319       	ROTEX 	DAIKIN
01021545       	ROTEX 	DAIKIN
01021546       	ROTEX 	DAIKIN
01021547       	ROTEX 	DAIKIN
01021548       	ROTEX 	DAIKIN
01021549       	ROTEX 	DAIKIN
01021798       	ROTEX 	DAIKIN
01021799       	ROTEX 	DAIKIN
01021800       	ROTEX 	DAIKIN
01021801       	ROTEX 	DAIKIN
01021802       	ROTEX 	DAIKIN
01021803       	ROTEX 	DAIKIN
01021804       	ROTEX 	DAIKIN
02020701       	ROTEX 	DAIKIN