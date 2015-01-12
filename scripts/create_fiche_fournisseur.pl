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
01014148	FRISQU	ORCFRI 
01014133	FRISQU	ORCFRI 
01014132	FRISQU	ORCFRI 
01014130	FRISQU	ORCFRI 
01014129	FRISQU	ORCFRI 
01014128	FRISQU	ORCFRI 
01014125	FRISQU	ORCFRI 
01014124	FRISQU	ORCFRI 
01014122	FRISQU	ORCFRI 
01014121	FRISQU	ORCFRI 
01014120	FRISQU	ORCFRI 
01014119	FRISQU	ORCFRI 
01014118	FRISQU	ORCFRI 
01014114	FRISQU	ORCFRI 
01014113	FRISQU	ORCFRI 
01014112	FRISQU	ORCFRI 
01014111	FRISQU	ORCFRI 
01014108	FRISQU	ORCFRI 
01014107	FRISQU	ORCFRI 
01014106	FRISQU	ORCFRI 
01014105	FRISQU	ORCFRI 
01014104	FRISQU	ORCFRI 
01014103	FRISQU	ORCFRI 
01014100	FRISQU	ORCFRI 
01014099	FRISQU	ORCFRI 
01014096	FRISQU	ORCFRI 
01014095	FRISQU	ORCFRI 
01014094	FRISQU	ORCFRI 
01014093	FRISQU	ORCFRI 
01013625	FRISQU	ORCFRI 
01013437	FRISQU	ORCFRI 
01013116	FRISQU	ORCFRI 
01013115	FRISQU	ORCFRI 
01013114	FRISQU	ORCFRI 
01013113	FRISQU	ORCFRI 
01013112	FRISQU	ORCFRI 
01013111	FRISQU	ORCFRI 
01013110	FRISQU	ORCFRI 
01013109	FRISQU	ORCFRI 
01013108	FRISQU	ORCFRI 
01013107	FRISQU	ORCFRI 
01013106	FRISQU	ORCFRI 
01013105	FRISQU	ORCFRI 
01011396	FRISQU	ORCFRI 
01011160	FRISQU	ORCFRI 
01010355	FRISQU	ORCFRI 
01008832	FRISQU	ORCFRI 
01008155	FRISQU	ORCFRI 
01008078	FRISQU	ORCFRI 
01008077	FRISQU	ORCFRI