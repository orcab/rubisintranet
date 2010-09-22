#!/usr/bin/perl

use Data::Dumper;
use Win32::ODBC;
use strict ;
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
use constant PROGRESSBAR_SIZE => 20;
$|=1;

my $cfg     = new Phpconst2perlconst(-file => '../inc/config.php');
my $loginor  = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";

open(SQL,"<requete_divers.sql") or die "Peux pas ouvrir le fichier sql.sql ($!)";
my $count=1;
while(<SQL>) { $count++; } # compte le nombre de ligne dans le fichier
close SQL ;


#my $progress = Term::ProgressBar->new ({count => $count});

open(SQL,"<requete_divers.sql") or die "Peux pas ouvrir le fichier sql.sql ($!)";
my $i=1;
while(<SQL>) {
	chomp;
	s/;$//g;
	my $pourcent = $i*100 / $count;
	my $size	 = int($pourcent * PROGRESSBAR_SIZE / 100);
	printf "\rUPDATE [".('@' x $size).(' ' x (PROGRESSBAR_SIZE - $size))."] %0.1f%% ($i/$count)", $pourcent;
	unless($loginor->Sql($_)) { $loginor->Error() } ;
	$i++;
}

$loginor->Close();
close SQL ;