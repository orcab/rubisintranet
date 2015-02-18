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
01012476       	DAIKIN	DAIROT 
01012477       	DAIKIN	DAIROT 
01012478       	DAIKIN	DAIROT 
01012479       	DAIKIN	DAIROT 
01012480       	DAIKIN	DAIROT 
01012481       	DAIKIN	DAIROT 
01012482       	DAIKIN	DAIROT 
01012483       	DAIKIN	DAIROT 
01013343       	DAIKIN	DAIROT 
01013344       	DAIKIN	DAIROT 
01013345       	DAIKIN	DAIROT 
01013346       	DAIKIN	DAIROT 
01013347       	DAIKIN	DAIROT 
01013348       	DAIKIN	DAIROT 
01013770       	DAIKIN	DAIROT 
01013771       	DAIKIN	DAIROT 
01013772       	DAIKIN	DAIROT 
01013773       	DAIKIN	DAIROT 
01013774       	DAIKIN	DAIROT 
01013775       	DAIKIN	DAIROT 
01013776       	DAIKIN	DAIROT 
01014190       	DAIKIN	DAIROT 
01014207       	DAIKIN	DAIROT 
01014443       	DAIKIN	DAIROT 
01014444       	DAIKIN	DAIROT 
01014553       	DAIKIN	DAIROT 
01015446       	DAIKIN	DAIROT 
01015447       	DAIKIN	DAIROT 
01015448       	DAIKIN	DAIROT 
01015449       	DAIKIN	DAIROT 
01015450       	DAIKIN	DAIROT 
01015458       	DAIKIN	DAIROT 
01015459       	DAIKIN	DAIROT 
01015460       	DAIKIN	DAIROT 
01015461       	DAIKIN	DAIROT 
01015462       	DAIKIN	DAIROT 
01015635       	DAIKIN	DAIROT 
01015855       	DAIKIN	DAIROT 
01018751       	DAIKIN	DAIROT 
01018752       	DAIKIN	DAIROT 
01018753       	DAIKIN	DAIROT 
01018754       	DAIKIN	DAIROT 
01018762       	DAIKIN	DAIROT 
01018763       	DAIKIN	DAIROT 
01018764       	DAIKIN	DAIROT 
01018807       	DAIKIN	DAIROT 
01018810       	DAIKIN	DAIROT 
01018811       	DAIKIN	DAIROT 
01018812       	DAIKIN	DAIROT 
01018814       	DAIKIN	DAIROT 
01018817       	DAIKIN	DAIROT 
01018896       	DAIKIN	DAIROT 
01018943       	DAIKIN	DAIROT 
01018951       	DAIKIN	DAIROT 
01020065       	DAIKIN	DAIROT 
01020067       	DAIKIN	DAIROT 
01020076       	DAIKIN	DAIROT 
01020077       	DAIKIN	DAIROT 
01020078       	DAIKIN	DAIROT 
01020181       	DAIKIN	DAIROT 
01020355       	DAIKIN	DAIROT 
01020407       	DAIKIN	DAIROT 
01020411       	DAIKIN	DAIROT 
01020412       	DAIKIN	DAIROT 
01020535       	DAIKIN	DAIROT 
01020536       	DAIKIN	DAIROT 
01020537       	DAIKIN	DAIROT 
01020538       	DAIKIN	DAIROT 
01020578       	DAIKIN	DAIROT 
01020712       	DAIKIN	DAIROT 
01020713       	DAIKIN	DAIROT 
01020715       	DAIKIN	DAIROT 
01020716       	DAIKIN	DAIROT 
01020717       	DAIKIN	DAIROT 
01020718       	DAIKIN	DAIROT 
01020719       	DAIKIN	DAIROT 
01020833       	DAIKIN	DAIROT 
01020834       	DAIKIN	DAIROT 
01020874       	DAIKIN	DAIROT 
01021319       	DAIKIN	DAIROT 
01021545       	DAIKIN	DAIROT 
01021546       	DAIKIN	DAIROT 
01021547       	DAIKIN	DAIROT 
01021548       	DAIKIN	DAIROT 
01021549       	DAIKIN	DAIROT 
01021798       	DAIKIN	DAIROT 
01021799       	DAIKIN	DAIROT 
01021800       	DAIKIN	DAIROT 
01021801       	DAIKIN	DAIROT 
01021802       	DAIKIN	DAIROT 
01021803       	DAIKIN	DAIROT 
01021804       	DAIKIN	DAIROT 
02020701       	DAIKIN	DAIROT 