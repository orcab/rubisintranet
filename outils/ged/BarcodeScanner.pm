#!/usr/bin/perl

# package de reconnaissance des codes barres dans une image
package BarcodeScanner;
use Carp;
use Data::Dumper;

sub new {
	my($class)  = shift;
	my(%params) = @_;
	my $self  = {
		'filename'	=> $params{-filename},
		'zbar'		=> $params{-zbar} || 'zbar/zbarimg.exe'
	};

	crap("Ne peux pas trouver l'application '".$self->{'zbar'}."' ($!)") if !-e $self->{'zbar'};

	bless ($self, $class);
	return $self;
}

# scan a code bar of qw/ean13 upca upce isbn13 isbn10 i25 code39 code128 qrcode/
sub scan { #type of barcode, if nothing --> all
	my $self  = shift;
	my @types = @_;
	
	my @type_param = ();
	if ($#types > -1) { # si un type est spécifié
		push @type_param, '-Sdisable'; # désactive tous les types de reconnaissance
		foreach (@types) {
			push @type_param, '-S'.$_.'.enable'; # Active tous les types spécifiés
		}
	}

	my $filename = $self->{'filename'};
	$filename =~ s/\//\\/g; # correction des / en \ pour windows

	my $cmd = join(' ',		'"'.$self->{'zbar'}.'"',	# programme de scan
							@type_param,				# on autorise les differents type de code barres
							'--quiet',					# n'affiche que les infos trouvées
							'"'.$filename.'"'			# fichier a analyser
					);
	my @output = `$cmd` ;

	my @results = ();
	if ($#output > -1) { # on a trouvé au moins un code barre
		foreach my $ligne (@output) {
			chomp($ligne);							# supprime le dernier \n de la commande dos
			if ($ligne =~ /^(CODE-128|CODE-39|EAN-13|I2\/5|QR-Code):(.*)/) {		#si c'est un code reconnu
				push @results , {'type'=>$1, 'code'=>$2 };
			}
		}
	}

	return \@results;
}

1;