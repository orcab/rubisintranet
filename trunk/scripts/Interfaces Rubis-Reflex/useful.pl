#!/usr/bin/perl
use strict;
#use warnings;

sub get_time {
	return strftime("[%Y-%m-%d %H:%M:%S]", localtime);
}

sub second2hms($) {
	my @gmtime = gmtime(shift);
	return sprintf ("%dh %dm %ds",@gmtime[2,1,0]);
}

sub fill_with_blank($$) {
	my ($value,$size_to_fill) = @_;
	if (length($value) > $size_to_fill) {
		return substr($value,0,$size_to_fill);
	} else {
		return $value . ' ' x ($size_to_fill - length($value));
	}
}

sub fill_with_zero($$) {
	my ($value,$size_to_fill) = @_;
	$value = 0 if $value eq '';
	return substr(sprintf('%0'.$size_to_fill.'u',$value),0,$size_to_fill);
}

sub dot2comma($) {
	my ($float) = shift;
	$float =~ s/\./,/;
	return $float;
}

sub remove_useless_zero($) {
	my ($number) = shift;
	$number =~ s/\.0+$//;
	return $number;
}


sub binary($) {
	my $value = shift;
	if ($value) {
		return 1;
	} else {
		return 0;
	}
}

sub in_array($$) {
     my ($search_for,$arr) = @_;
     my %items = map {$_ => 1} @$arr;
     return (exists($items{$search_for}))?1:0;
 }

sub trim($) {
	return ltrim(rtrim(shift));
}

sub ltrim($) {
	my $s = shift;
	$s =~ s/^\s+//g;
	return $s;
}

sub rtrim($) {
	my $s = shift;
	$s =~ s/\s+$//g;
	return $s;
}

sub quotify {
	my $t = shift;
	$t =~ s/'/''/g;
	return $t ;
}

# envoi un sms via la passerrelle
use LWP::Simple;
use URI::Encode qw(uri_encode);
sub sendSMS($$$) {
	my ($phone_number,$text,$url) = @_;
	$phone_number =~ s/[^\d\+]//; # remove wrong caractere in phone number
	if ($phone_number && $text) {
		my $reponse = get($url."phone=$phone_number&text=".uri_encode($text));
  		return ($reponse =~ m/Mesage\s+SENT\s*!/i ? 1:0);
	} else {
			return 0;
	}
}


sub isDriveMapped($) {
	local $_;
	my $letter = lc(shift);
	foreach (getLogicalDrives()) {
		if (lc(substr($_,0,1)) eq $letter) {
			return 1;
		}
	}
	return 0;
}


sub icaldate2sqldate($) {
	my $ical = shift;
	if ($ical =~ /^.*?:?(\d{4})(\d{2})(\d{2})(?:T(\d{2})(\d{2})(\d{2}))?/i) { # valid ical date	20110915T084903Z ou 20110915 ou Europe/Paris:20110916T163000
		return "$1-$2-$3 ".($4?$4:'00').':'.($5?$5:'00').':'.($6?$6:'00');	
	} else {
		return 0;
	}
}

=begin
sub send_mail(%) {
	use JSON;
	my $ref_param = shift;

	my $hash = {
		'server'	=>	$ref_param->{'smtp_serveur'},
	 	'user'		=>	$ref_param->{'smtp_user'},
	 	'password'	=>	$ref_param->{'smtp_password'},
	 	'port'		=>	$ref_param->{'smtp_port'},
	 	'tls'		=>	0,
	 	'from'		=>	$ref_param->{'from_email'},
	 	'html'		=>	$ref_param->{'message'},
	 	'subject'	=>	$ref_param->{'subject'},
	 	'to'		=>	$ref_param->{'to'},
	 	'debug'		=>	$ref_param->{'debug'},
	};

	my $json = new JSON ;
	my $json_text = $json->ascii->encode($hash);
	my $exit = system("echo $json_text | c:\\easyphp\\php\\php -c c:\\easyphp\\apache\\php.ini c:\\easyphp\\www\\intranet\\scripts\\sendmail.php");
	return $exit >= 0 ? 1 : 0 ;
}
=cut

sub send_mail(%) {
	my $ref_param = shift;

	use Data::Uniqid qw(luniqid);
	my $filename = luniqid().'.txt';
	open(F,"+>$filename") or die "Unable to create '$filename' ($!)";
	print F $ref_param->{'message'};
	close F;

	my $to = join(',',keys $ref_param->{'to'});

	# launch program
	my $cmd = 'mailsend.exe'.
				' -smtp '.$ref_param->{'smtp_serveur'}.
				' -port '.$ref_param->{'smtp_port'}.
				' -auth'.
				' -user '.$ref_param->{'smtp_user'}.
				' -pass '.$ref_param->{'smtp_password'}.
				" -t $to".
				' -f '.$ref_param->{'from_email'}.
				' -sub "'.$ref_param->{'subject'}.'"'.
				' -mime-type "text/html"'.
				' -disposition inline'.
				" -msg-body $filename";
	`$cmd`;
	#print $cmd;
	unlink($filename);
}

1;