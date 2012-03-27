#!/usr/bin/perl

my $VERSION = 1.0;

use constant ;
use Data::Dumper;
use POSIX qw(strftime);
use strict ;
use DBI qw(:sql_types);				# pour gérer SQLite
use Config::IniFiles;
use Time::Local; # convert sec, min, hour, day, mounth, year into sec since 1970
use Data::Uniqid qw ( luniqid );
use File::Copy; # move

my $ini	= new Config::IniFiles( -file => 'ical2sqlite.ini' );
my $sqlite = DBI->connect('dbi:SQLite:'.$ini->val(qw/files sqlite_output/),'','',{ RaiseError => 0, AutoCommit => 0 }) or die("Pas de DB");
my $now = time ;

init_sqlite();

print print_time()."Insertion des events...";

my %data ;
my %event_data ;
my $in_event = 0;
my $uniqid = luniqid;
copy($ini->val(qw/files ical_input/), "$uniqid.ics") or die "Copy failed: $!";
open(F,"<$uniqid.ics") or die "Could not open ical file '$uniqid.ics' ($!)";
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

		$sqlite->do("INSERT OR REPLACE INTO events ('uid','created','lastmodified','dtstamp','start','end','location','summary','description','frequency','original_text') VALUES (".
			"'".$event_data{'UID'}."',".
			"'".icaldate2sqldate($event_data{'CREATED'}			? $event_data{'CREATED'}		: $event_data{'CREATED;TZID'})."',".
			"'".icaldate2sqldate($event_data{'LAST-MODIFIED'})."',".
			"'".icaldate2sqldate($event_data{'DTSTAMP'})."',".
			"'".icaldate2sqldate($event_data{'DTSTART;TZID'}	? $event_data{'DTSTART;TZID'}	: $event_data{'DTSTART;VALUE'})."',".
			"'".icaldate2sqldate($event_data{'DTEND;TZID'}		? $event_data{'DTEND;TZID'}		: $event_data{'DTEND;VALUE'})."',".
			"'".quotify($event_data{'LOCATION'})."',".
			"'".quotify($event_data{'SUMMARY'})."',".
			"'".quotify($event_data{'DESCRIPTION'})."',".
			"'".quotify(exists $event_data{'FREQUENCY'} ? $event_data{'FREQUENCY'}:'')."',".
			"'".quotify($buffer)."'".
		")");

		%event_data = (); #on vide la mémoire temportaire
		$buffer = '';
	} elsif (/^ (.+?)$/) { # si la ligne commence par un espace, c'est la continuation de la ligne précédente
		$event_data{$last_variable} .= $1;
	} else {
		if ($in_event) { # on est dans un evenement, donc on stock l'attribut
			if (/^ *RRULE *: *FREQ *= *(.+?) *$/) { # gestion de la frequence
				$event_data{'FREQUENCY'} = $1;

			} elsif (/^ *(.+?)[:=](.+?) *$/) {	#match DTSTART;TZID=Europe/Paris:20110910T151500   or    DTSTAMP:20110909T124842Z
				#print "je stock '$1' => '$2'\n" ;

				$event_data{$1} = $2;
				$last_variable = $1;
			}
		}
	}
}
close F;
unlink("$uniqid.ics");

print " ok\n";

$sqlite->commit;
$sqlite->disconnect();
print print_time()."END\n\n";

####################################################################################################################

sub init_sqlite {
	my $sql = <<EOT ;
CREATE TABLE [events] (
  [uid] CHAR, 
  [created] DATETIME NOT NULL, 
  [lastmodified] DATETIME, 
  [dtstamp] DATETIME, 
  [start] DATETIME NOT NULL, 
  [end] DATETIME NOT NULL, 
  [location] TEXT DEFAULT NULL, 
  [summary] TEXT DEFAULT NULL, 
  [description] TEXT DEFAULT NULL,
  [frequency] CHAR DEFAULT NULL,
  [original_text] TEXT DEFAULT NULL,
  CONSTRAINT [] PRIMARY KEY ([uid])
);
EOT
	$sqlite->do("DROP TABLE IF EXISTS [events];");
	$sqlite->do($sql);

	#index sur les events
	$sqlite->do('CREATE INDEX [date_start]	ON [events] ([start]);');
	$sqlite->do('CREATE INDEX [date_end]	ON [events] ([end]);');

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