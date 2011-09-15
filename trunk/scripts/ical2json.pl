#!/usr/bin/perl

my $VERSION = 1.0;

use constant ;
use Data::Dumper;
use POSIX qw(strftime);
use strict ;
use JSON;
use Config::IniFiles;
use iCal::Parser;
use Time::Local; # convert sec, min, hour, day, mounth, year into sec since 1970

my $now = time ;
my $ini	= new Config::IniFiles( -file => 'ical2json.ini' );

my $ical_data = parse_ical_file({	-file		=> $ini->val(qw/files ical_input/),
									-deltatime	=> $ini->val(qw/other delta_in_second/)
								});
open(F,'+>'.$ini->val(qw/files json_output/)) or die "Could not open ouput json file '".$ini->val(qw/files json_output/)."' ($!)";
print F to_json($ical_data);
close F;

####################################################################################################################

sub parse_ical_file($) {
	my $args = shift;
	my %data ;
	my %event_data ;
	my $in_event = 0;
	open(F,'<'.$args->{-file}) or die "Could not open ical file '".$args->{-file}."' ($!)";

	my $last_variable;
	while(<F>) {  # pour chaque ligne du fichier
		if		(/^BEGIN:VEVENT *$/i) { # debut d'un event
			#print "je suis au debut d'un event\n";
			$in_event = 1;		
		} elsif (/^END:VEVENT *$/i) { # fin d'un event
			#print "je sors d'un event\n\n";
			#print Dumper(\%event_data) if $event_data{'UID'} eq '0mkphlpu1t8gtgq2n37nbfl6fo@google.com';
			$in_event = 0;

			if (	(exists $event_data{'DTEND;TZID'} && exists $event_data{'DTSTART;TZID'}) ||
					(exists $event_data{'DTEND;VALUE'} && exists $event_data{'DTSTART;VALUE'}) ) {
					my ($delta_end,$delta_start);
					if ($event_data{'DTEND;TZID'}	=~ /^Europe\/Paris:(\d{4})(\d{2})(\d{2})/i ||		#DTEND;TZID=Europe/Paris:20110916T173000
						$event_data{'DTEND;VALUE'}	=~ /^DATE;TZID=Europe\/Paris:(\d{4})(\d{2})(\d{2})/i) {	
							$delta_end = timelocal(0,0,0,$3,$2 - 1,$1) - $now;
					}

					if ($event_data{'DTSTART;TZID'} =~ /^Europe\/Paris:(\d{4})(\d{2})(\d{2})/i ||		#DTSTART;TZID=Europe/Paris:20110916T173000
						$event_data{'DTSTART;VALUE'} =~ /^DATE;TZID=Europe\/Paris:(\d{4})(\d{2})(\d{2})/i) {	
							$delta_start = timelocal(0,0,0,$3,$2 - 1,$1) - $now;
					}

					if (($delta_end		<= $args->{-deltatime} && $delta_end > 0) ||	# si la date de fin de l'event est inférieur a une semaine d'aujourd'hui
						($delta_start	<= $args->{-deltatime} && $delta_start > 0)		# OU si la date de fin de l'event est inférieur a une semaine d'aujourd'hui
						) {
						#print "$event_end_timestamp - $now ($delta) <= ".$args->{-deltatime}."\n";
						$data{$event_data{'UID'}} = {%event_data}; # on stock l'evenement
					}			
					
			
			}
			%event_data = (); #on vide la mémoire temportaire
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
	return \%data;
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