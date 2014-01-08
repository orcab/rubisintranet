#!/usr/bin/perl

use Data::Dumper;
use Net::FTP;
use strict ;
use File::Find;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
use Config::IniFiles;

print print_time()."START\n";

my $ini = new Config::IniFiles( -file => 'insert_cde_rubis_internet.ini' );

# on compress la base pour l'envoyé sur le serveur FTP
print print_time()."Sending originals files on FTP ... ";
my 	$ftp = Net::FTP->new($ini->val(qw/FTP host/), Debug => 0) or die "Cannot connect to ".$ini->val(qw/FTP host/).": $@";
	$ftp->login($ini->val(qw/FTP user/), $ini->val(qw/FTP pass/)) or die "Cannot login ", $ftp->message;
	$ftp->cwd("/www") or die "Cannot change working directory ", $ftp->message;

	find(\&wanted, 'hack_www.coopmcs.com');
	sub wanted {
		if (-f) {
			my 	$remote_filename = $File::Find::name;
				$remote_filename =~ s/^hack_www\.coopmcs\.com\///;
				#print "local '$_' : remote :'$remote_filename'\n";
				$ftp->put($_,$remote_filename) or warn "put failed ", $ftp->message;
		}
	}

$ftp->quit;
print "OK\n";

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