#!/usr/bin/perl

my $VERSION = 1.0;

use constant ;
use Data::Dumper;
use POSIX qw(strftime);
use strict ;
use DBI qw(:sql_types);				# pour gérer SQLite
use Config::IniFiles;
use Time::Local; # convert sec, min, hour, day, mounth, year into sec since 1970

my $ini	= new Config::IniFiles( -file => 'ical2sqlite.ini' );
my $sqlite = DBI->connect('dbi:SQLite:'.$ini->val(qw/files sqlite_output/),'','',{ RaiseError => 0, AutoCommit => 0 }) or die("Pas de DB");
my $now = time ;

init_sqlite();

print print_time()."Insertion des events...";

my %data ;
my %event_data ;
my $in_event = 0;
open(F,'<'.$ini->val(qw/files ical_input/)) or die "Could not open ical file '".$ini->val(qw/files ical_input/)."' ($!)";
my $last_variable = '';
my $buffer = '';
while(<F>) {  # pour chaque ligne du fichier
	
	$buffer .= $_ if ($in_event);
	
	if		(/^BEGIN:VEVENT *$/i) { # debut d'un event
		#print "je suis au debut d'un event\n";
		$in_event = 1;
	} elsif (/^END:VEVENT *$/i) { # fin d'un event
		$buffer =~ s/END:VEVENT$//i;
		#print "je sors d'un event\n\n";
		$in_event = 0;

		$sqlite->do("INSERT OR REPLACE INTO events ('uid','created','lastmodified','dtstamp','start','end','location','summary','description','original_text') VALUES (".
			"'".$event_data{'UID'}."',".
			"'".icaldate2sqldate($event_data{'CREATED'}			? $event_data{'CREATED'}		: $event_data{'CREATED;TZID'})."',".
			"'".icaldate2sqldate($event_data{'LAST-MODIFIED'})."',".
			"'".icaldate2sqldate($event_data{'DTSTAMP'})."',".
			"'".icaldate2sqldate($event_data{'DTSTART;TZID'}	? $event_data{'DTSTART;TZID'}	: $event_data{'DTSTART;VALUE'})."',".
			"'".icaldate2sqldate($event_data{'DTEND;TZID'}		? $event_data{'DTEND;TZID'}		: $event_data{'DTEND;VALUE'})."',".
			"'".quotify($event_data{'LOCATION'})."',".
			"'".quotify($event_data{'SUMMARY'})."',".
			"'".quotify($event_data{'DESCRIPTION'})."',".
			"'".quotify($buffer)."'".
		")");

		%event_data = (); #on vide la mémoire temportaire
		$buffer = '';
	} elsif (/^ (.+?)$/) { # si la ligne commence par un espace, c'est la continuation de la ligne précédente
		$event_data{$last_variable} .= $1;
	} else {
		if ($in_event) { # on est dans un evenement, donc on stock l'attribut
			if (/^ *(.+?)[:=](.+?) *$/) {	#match DTSTART;TZID=Europe/Paris:20110910T151500   or    DTSTAMP:20110909T124842Z
				#print "je stock '$1' => '$2'\n" ;
				$event_data{$1} = $2;
				$last_variable = $1;
			}
		}
	}
}
close F;


print " ok\n";

$sqlite->commit;
$sqlite->disconnect();
print print_time()."END\n\n";

####################################################################################################################

sub init_sqlite {
# creation des tables articles + fournisseur + index + triggers #####################################################################################""
	my $sql = <<EOT ;
CREATE TABLE [events] (
  [uid] CHAR, 
  [created] DATETIME NOT NULL, 
  [lastmodified] DATETIME, 
  [dtstamp] DATETIME, 
  [start] DATETIME NOT NULL, 
  [end] DATETIME NOT NULL, 
  [location] TEXT, 
  [summary] TEXT, 
  [description] TEXT, 
  [original_text] TEXT,
  CONSTRAINT [] PRIMARY KEY ([uid])
);
EOT
	$sqlite->do("DROP TABLE IF EXISTS [events];");
	$sqlite->do($sql);

	#index sur les events
	$sqlite->do('CREATE INDEX [date_start]	ON [event] ([start]);');
	$sqlite->do('CREATE INDEX [date_end]	ON [event] ([end]);');

	$sqlite->commit; # valide les table et les trigger
	#$sqlite->commit; $sqlite->disconnect(); exit;
}


####################################################################################################################
sub print_time {
	print strftime('[%Y-%m-%d %H:%M:%S] ', localtime);
	return '';
}

sub trim {
	my $t = shift;
	$t =~ s/^\s+//g;
	$t =~ s/\s+$//g;
	return $t ;
}

sub quotify {
	my $t = shift;
	$t =~ s/'/''/g;
	return $t ;
}

sub icaldate2sqldate($) {
	my $ical = shift;
	if ($ical =~ /^.*?:?(\d{4})(\d{2})(\d{2})(?:T(\d{2})(\d{2})(\d{2}))?/i) { # valid ical date			20110915T084903Z ou 20110915 ou Europe/Paris:20110916T163000
		return "$1-$2-$3 ".($4?$4:'00').':'.($5?$5:'00').':'.($6?$6:'00');	
	} else {
		return 0;
	}
	
}