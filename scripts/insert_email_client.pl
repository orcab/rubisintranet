#!/usr/bin/perl

use Data::Dumper;
use Win32::ODBC;
use strict ;
use Config::IniFiles;

use constant {
	CODE_CLIENT	=> 0,
	EMAIL_CLIENT => 2
};

my $cfg = new Config::IniFiles( -file => "config.ini" );

my $loginor = new Win32::ODBC('DSN='.$cfg->val('RUBIS','DSN').';UID='.$cfg->val('RUBIS','USER').';PWD='.$cfg->val('RUBIS','PASS').';') or die "Ne peux pas se connecter à rubis";

open(CSV,"<user.csv") or die "Peux pas ouvrir le fichier user.csv ($!)";
while(<CSV>) {
	chomp;
	my @champs = split(/;/);
	@champs = map {unquote(trim($_))} @champs ;
	if ($champs[EMAIL_CLIENT]) {
		print "UPDATE '".$champs[CODE_CLIENT]."'->'".$champs[EMAIL_CLIENT]."'\n";
		$loginor->Sql("update AFAGESTCOM.ACLIENP1 set COMC1='".quotify($champs[EMAIL_CLIENT])."' where NOCLI='".$champs[CODE_CLIENT]."'"); # regarde les artisans actif
	}
}


$loginor->Close();

sub trim {
	my $t = shift;
	$t =~ s/^\s+|\s+$//g;
	return $t ;
}

sub unquote {
	my $t = shift;
	$t =~ s/^"+|"+$//g;
	return $t ;
}

sub quotify {
	my $t = shift;
	$t =~ s/'/''/g;
	return $t ;
}

sub ucwords {
	my $t = shift;
	$t =~ s/(^|[\s\-])(.)/$1\U$2/g;
	return $t;
}