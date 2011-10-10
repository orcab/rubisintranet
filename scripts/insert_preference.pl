#!/usr/bin/perl

use Data::Dumper;
use Mysql ;
use strict ;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
use Config::IniFiles;

print print_time()."START\n";

my	$ini = new Config::IniFiles( -file => 'insert_cde_rubis_internet.ini' );
my	$cfg = new Phpconst2perlconst(-file => '../inc/config.php');
my	$mysql = Mysql->connect($cfg->{MYSQL_HOST},$cfg->{MYSQL_BASE},$cfg->{MYSQL_USER},$cfg->{MYSQL_PASS}) or die "Peux pas se connecter a mysql";
	$mysql->selectdb($cfg->{MYSQL_BASE}) or die "Peux pas selectionner la base mysql";


open(SQL,'+>preference.sql') or die "ne peux pas creer le fichier SQL ($!)" ;
# SQL de creation de la table
print SQL <<EOT ;
CREATE TABLE IF NOT EXISTS `send_document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_artisan` varchar(255) NOT NULL,
  `AR` varchar(255) NOT NULL,
  `BL` varchar(255) NOT NULL,
  `RELIQUAT` varchar(255) NOT NULL,
  `AVOIR` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_artisan` (`numero_artisan`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
EOT

print print_time()."Select des preferences send_document ...";
my $res = $mysql->query("SELECT * FROM send_document") or die "Impossible de selectionner les preferences";
print "OK\n";

while(my %row = $res->fetchhash) {
	#print Dumper(\%row);
	print SQL "REPLACE INTO send_document (numero_artisan,AR,BL,RELIQUAT,AVOIR) VALUES ('$row{numero_artisan}','$row{AR}','$row{BL}','$row{RELIQUAT}','$row{AVOIR}');\n";
}

undef $mysql; # close the connection
close SQL;


# on compress la base pour l'envoyé sur le serveur FTP
print print_time()."Compression du fichier SQL ... ";
system("bzip2 -zkf8 preference.sql");
print "OK\n";

print print_time()."Transfert ... ";
my $cmd = join(' ',	'pscp',
					'-scp',
					'-pw',
					$ini->val(qw/SSH pass/),
					'preference.sql.bz2',
					$ini->val(qw/SSH user/).'@'.$ini->val(qw/SSH host/).':preference.sql.bz2'
			);
`$cmd`;
print "OK\n";

print print_time()."Decompression ... ";
my $cmd = join(' ',	'plink',
					'-pw',
					$ini->val(qw/SSH pass/),
					$ini->val(qw/SSH user/).'@'.$ini->val(qw/SSH host/),
					'./insert-preference.sh'
			);
`$cmd`;
print "OK\n";
END_FTP: ;

END: ;

print print_time()."END\n\n";

sub print_time {
	print strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
	return '';
}

sub trim {
	my $t = shift;
	$t =~ s/^\s+//g;
	$t =~ s/\s+$//g;
	$t =~ s/\n/ /g;
	return $t ;
}

sub quotify {
	my $t = shift;
	$t =~ s/'/''/g;
	return $t ;
}