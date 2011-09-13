#!/usr/bin/perl

use Data::Dumper;
use Win32::ODBC;
use strict ;
use constant PROGRESSBAR_SIZE => 20;
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
$|=1;

my $start_time = time();

my $cfg = new Phpconst2perlconst(-file => '../inc/config.php');
my $loginor  = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";

open(SQL,"<requette_divers.sql") or die "Peux pas ouvrir le fichier requette_divers.sql ($!)";
my $count=1;
while(<SQL>) { $count++; } # compte le nombre de ligne dans le fichier
close SQL ;

#my $progress = Term::ProgressBar->new ({count => $count});
my @cursors = ('-',"\\",'|','/');
open(SQL,"<requette_divers.sql") or die "Peux pas ouvrir le fichier requette_divers.sql ($!)";
my $i=1;
my $loop = 0;
while(<SQL>) {
	chomp;
	s/;$//g;
	my $pourcent	= $i*100 / $count;
	my $size		= int($pourcent * PROGRESSBAR_SIZE / 100);
	my $pass_time	= time() - $start_time;
#	my $days		= int($pass_time/(24*60*60));
	my $hours		= ($pass_time/(60*60))%24;
	my $mins		= ($pass_time/60)%60;
	my $secs		=  $pass_time%60;
	my	$pass_time_formated = "${hours}h${mins}m${secs}s";

	my $estimated_time = int($pass_time * $count / $i);
	my $esti_hours		= ($estimated_time/(60*60))%24;
	my $esti_mins		= ($estimated_time/60)%60;
	my $esti_secs		=  $estimated_time%60;
	my	$esti_time_formated = "${esti_hours}h${esti_mins}m${esti_secs}s";
	
	printf "\rUPDATE [".('+' x $size).($cursors[$loop]).('-' x (PROGRESSBAR_SIZE - $size - 1))."] %0.1f%% ($i/$count)  Total : $pass_time_formated   Estimated : $esti_time_formated", $pourcent;
	unless($loginor->Sql($_)) { $loginor->Error() } ;
	#usleep(6000);
	$i++;
	if ($loop >= 3) {
		$loop=0;
	} else {
		$loop++;
	}
}

$loginor->Close();
close SQL ;