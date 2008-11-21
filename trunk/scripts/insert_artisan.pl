#!/usr/bin/perl

use Data::Dumper;
use Win32::ODBC;
use Mysql ;
use strict ;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;

print print_time()."START\n";

my $cfg = new Phpconst2perlconst(-file => '../inc/config.php');

my $loginor = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";

print print_time()."Select des artisans actif ...";
$loginor->Sql("select NOCLI,NOMCL,COMC1 from AFAGESTCOM.ACLIENP1 where CATCL='1' and ETCLE<>'S'"); # regarde les artisans actif
print "OK\n";


my $mysql = Mysql->connect($cfg->{MYSQL_HOST},$cfg->{MYSQL_BASE},$cfg->{MYSQL_USER},$cfg->{MYSQL_PASS}) or die "Peux pas se connecter a mysql";
$mysql->selectdb($cfg->{MYSQL_BASE}) or die "Peux pas selectionner la base mysql";

print print_time()."Suppression de la base ...";
$mysql->query("TRUNCATE TABLE artisan;");
print " ok\n";


print print_time()."MAJ des artisans dans la base\n";

while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;

	if ($mysql->quote($row{'NOCLI'}) == '056077') { # patch pour email trop long
		$mysql->quote($row{'COMC1'}) = 'bretagne-plomberie-chauffage@aliceadsl.fr';
	}

	$mysql->query("INSERT INTO artisan (numero,nom,suspendu,email) VALUES (".$mysql->quote($row{'NOCLI'}).",".$mysql->quote($row{'NOMCL'}).",0,".$mysql->quote($row{'COMC1'}).")") or warn "Ne peux pas inserer le client ".$row{'NOMCL'};
}

$loginor->Close();


print print_time()."END\n\n";

sub print_time {
	print strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
	return '';
}