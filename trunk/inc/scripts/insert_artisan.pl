#!/usr/bin/perl

use Data::Dumper;
use Win32::ODBC;
use Mysql ;
use strict ;
use Config::IniFiles;
use POSIX qw(strftime);

print print_time()."START\n";

my $cfg = new Config::IniFiles( -file => "config.ini" );

my $loginor = new Win32::ODBC('DSN='.$cfg->val('RUBIS','DSN').';UID='.$cfg->val('RUBIS','USER').';PWD='.$cfg->val('RUBIS','PASS').';') or die "Ne peux pas se connecter � rubis";

print print_time()."Select des artisans actif ...";
$loginor->Sql("select NOCLI,NOMCL,COMC1 from AFAGESTCOM.ACLIENP1 where CATCL='1' and ETCLE<>'S'"); # regarde les artisans actif
print "OK\n";


my $mysql = Mysql->connect($cfg->val('MYSQL','HOST'),$cfg->val('MYSQL','BASE'),$cfg->val('MYSQL','USER'),$cfg->val('MYSQL','PASS')) or die "Peux pas se connecter a mysql";
$mysql->selectdb($cfg->val('MYSQL','BASE')) or die "Peux pas selectionner la base mysql";

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