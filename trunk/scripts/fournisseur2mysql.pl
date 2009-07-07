#!/usr/bin/perl
use POSIX qw(strftime);
use Data::Dumper;
use Mysql ;
use strict ;
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
use Win32::Console::ANSI;
$| = 1; # active le flush direct

# SCRIPT pour transformer les fichiers CSV des fournisseurs en fichier SQL pour la salle expo


print print_time()."START\n";

my	$cfg = new Phpconst2perlconst(-file => '../inc/config.php');
my	$mysql = Mysql->connect($cfg->{MYSQL_HOST},$cfg->{MYSQL_BASE},$cfg->{MYSQL_USER},$cfg->{MYSQL_PASS}) or die "Peux pas se connecter a mysql";
	$mysql->selectdb($cfg->{MYSQL_BASE}) or die "Peux pas selectionner la base mysql";


unless ($ARGV[0]) { die "USAGE : fournisseur2mysql fichier.csv\n" }
open(F,$ARGV[0]) or die "Ne peux pas ouvrir $ARGV[0] ($!)";

my %input ;

# debug
#%input = (
#	'col_poids' => 'M',
#	'col_collisage' => 'H',
#	'col_remise3' => '',
#	'activite' => '1',
#	'skip_first_line' => '',
#	'col_taille' => 'L',
#	'col_remise1' => '48',
#	'col_gencode' => 'I',
#	'col_prix_vente_coop' => '',
#	'col_designation' => 'E',
#	'col_remise2' => '',
#	'col_fournisseur' => 'PRESTO',
#	'col_url' => '',
#	'col_prix_achat_coop' => '',
#	'col_couleur' => 'O',
#	'col_prix_public' => 'F',
#	'col_marge_coop' => '18',
#    'col_remise4' => '',
#	'col_reference' => 'A'
#);
#goto DEBUG;

do { print "\e[0;33mSaute 1ere ligne ? (o/n) [o] : \e[0;37m";																	$input{'skip_first_line'} = <STDIN>;	$input{'skip_first_line'}	= trim($input{'skip_first_line'}); }	until $input{'skip_first_line'} eq '' || $input{'skip_first_line'} eq 'o' || $input{'skip_first_line'} eq 'O' || $input{'skip_first_line'} eq 'n' || $input{'skip_first_line'} eq 'N' ;
do { print "\e[0;33mLettre colonne fournisseur (ou tapez le nom du fournisseur directement) : \e[0;37m";						$input{'col_fournisseur'} = <STDIN>;	$input{'col_fournisseur'}	= uc trim($input{'col_fournisseur'}); }	until $input{'col_fournisseur'};
do { print "\e[0;33mSanitaire=1  Electricite=2  Plomberie=3  Outillage=4  Electromenager=5  Chauffage=6\nActivite : \e[0;37m";	$input{'activite'} = <STDIN>;			$input{'activite'} = trim($input{'activite'}); }					until $input{'activite'} >= 1 && $input{'activite'} <= 6 ;
do { print "\e[0;33mLettre colonne ref : \e[0;37m";																				$input{'col_reference'} = <STDIN>;		$input{'col_reference'}	= uc trim($input{'col_reference'});	}		until $input{'col_reference'} =~ /^[A-Z]+$/;
do { print "\e[0;33mLettre colonne(s) designation (separe par virgule) : \e[0;37m";					$input{'col_designation'} = <STDIN>;		$input{'col_designation'}	= uc trim($input{'col_designation'}); }			until $input{'col_designation'} =~ /^[A-Z,]+$/;
do { print "\e[0;33mLettre colonne prix public : \e[0;37m";											$input{'col_prix_public'} = <STDIN>;		$input{'col_prix_public'}	= uc trim($input{'col_prix_public'}); }			until $input{'col_prix_public'} =~ /^[A-Z]+$/;
do { print "\e[0;33mLettre colonne marge de la COOP (ou tapez la marge directement) : \e[0;37m";	$input{'col_marge_coop'} = <STDIN>;			$input{'col_marge_coop'}	= uc trim($input{'col_marge_coop'}); }			until $input{'col_marge_coop'} ;
print "\e[0;33mLettre colonne prix d'achat COOP : \e[0;37m";										$input{'col_prix_achat_coop'} = <STDIN>;	$input{'col_prix_achat_coop'} = uc trim($input{'col_prix_achat_coop'});
print "\e[0;33mLettre colonne prix de vente COOP : \e[0;37m";										$input{'col_prix_vente_coop'} = <STDIN>;	$input{'col_prix_vente_coop'} = uc trim($input{'col_prix_vente_coop'});
print "\e[0;33mLettre colonne remise 1 (ou tapez la remise directement) : \e[0;37m";				$input{'col_remise1'} = <STDIN>;			$input{'col_remise1'}		= uc trim($input{'col_remise1'});
print "\e[0;33mLettre colonne remise 2 (ou tapez la remise directement) : \e[0;37m";				$input{'col_remise2'} = <STDIN>;			$input{'col_remise2'}		= uc trim($input{'col_remise2'});
print "\e[0;33mLettre colonne remise 3 (ou tapez la remise directement) : \e[0;37m";				$input{'col_remise3'} = <STDIN>;			$input{'col_remise3'}		= uc trim($input{'col_remise3'});
print "\e[0;33mLettre colonne remise 4 (ou tapez la remise directement) : \e[0;37m";				$input{'col_remise4'} = <STDIN>;			$input{'col_remise4'}		= uc trim($input{'col_remise4'});
print "\e[0;33mLettre colonne couleur : \e[0;37m";													$input{'col_couleur'} = <STDIN>;			$input{'col_couleur'}		= uc trim($input{'col_couleur'});
print "\e[0;33mLettre colonne taille : \e[0;37m";													$input{'col_taille'} = <STDIN>;				$input{'col_taille'}		= uc trim($input{'col_taille'});
print "\e[0;33mLettre colonne poids : \e[0;37m";													$input{'col_poids'} = <STDIN>;				$input{'col_poids'}			= uc trim($input{'col_poids'});
print "\e[0;33mLettre colonne collisage : \e[0;37m";												$input{'col_collisage'} = <STDIN>;			$input{'col_collisage'}		= uc trim($input{'col_collisage'});
print "\e[0;33mLettre colonne gencode : \e[0;37m";													$input{'col_gencode'} = <STDIN>;			$input{'col_gencode'}		= uc trim($input{'col_gencode'});
print "\e[0;33mLettre colonne URL : \e[0;37m";														$input{'col_url'} = <STDIN>;				$input{'col_url'}			= uc trim($input{'col_url'});

DEBUG:

my $activite = 0;
SWITCH: {
		if ($input{'activite'} == 1) { $activite = 'sanitaire';		last SWITCH; }
		if ($input{'activite'} == 2) { $activite = 'electricite';	last SWITCH; }
		if ($input{'activite'} == 3) { $activite = 'plomberie';		last SWITCH; }
		if ($input{'activite'} == 4) { $activite = 'outillage';		last SWITCH; }
		if ($input{'activite'} == 5) { $activite = 'electromenager';last SWITCH; }
		if ($input{'activite'} == 6) { $activite = 'chauffage';		last SWITCH; }
		$activite = 'non renseignée';
	}

my @col_designation = split(/,/,$input{'col_designation'});
foreach (@col_designation) { $_ = lettre2index($_); }


unless ($input{'skip_first_line'} eq 'n' || $input{'skip_first_line'} eq 'N' ) { <F>; }

my $i=1;
while(<F>) {
	my %val;
	my @cols = split /;/ ;				# coupe sur les ;
	if ($#cols + 1 < 3) { # 5 col minimum
		warn "Ligne $i : nombre de colonne insuffisant (seulement ".($#cols+1).")\n"; next;
	}

	#trim des valeurs + quotify
	for(my $j=0 ; $j<=$#cols ; $j++) { $cols[$j] =~ s/^"|"$//g;  $cols[$j] = quotify(trim($cols[$j])) ; $cols[$j] =~ s/ //g; }

	$val{'fournisseur'} = '';
	$val{'reference'}	= $cols[lettre2index($input{'col_reference'})];
	$val{'reference_simple'} = uc $val{'reference'};	$val{'reference_simple'} =~ s/[^A-Z0-9]//g; # on nettoi la reference officiel du fournisseur pour faciliter les recherches
	$val{'prix_public'} = $cols[lettre2index($input{'col_prix_public'})]; $val{'prix_public'} =~ s/€//g; $val{'prix_public'} = trim($val{'prix_public'});
	$val{'remise1'}		= 0;	$val{'remise2'} = 0;	$val{'remise3'} = 0;	$val{'remise4'}	= 0;
	$val{'couleur'}		= $input{'col_couleur'} ? $cols[lettre2index($input{'col_couleur'})] : '';
	$val{'taille'}		= $input{'col_taille'}		? $cols[lettre2index($input{'col_taille'})] : '';
	$val{'collisage'}	= $input{'col_collisage'}	? $cols[lettre2index($input{'col_collisage'})] : '';
	if ($input{'col_gencode'}) {
		$val{'gencode'}	= $cols[lettre2index($input{'col_gencode'})];
		$val{'gencode'} =~ s/ +//g;
	} else {
		$val{'gencode'} = '';
	}
	$val{'url'}			= $input{'col_url'}		? $cols[lettre2index($input{'col_url'})] : '';
	$val{'poids'}		= $input{'col_poids'}	? $cols[lettre2index($input{'col_poids'})] : '';

	# gestion des fournisseurs remise et de la marge (colonne ou nombre)
	if ($input{'col_fournisseur'} && $input{'col_fournisseur'} =~ /^.{2,}$/)	{ # on a rentré au moins deux caractere
		$val{'fournisseur'} =  $input{'col_fournisseur'} ;
	} elsif ($input{'col_fournisseur'} && $input{'col_fournisseur'} =~ /^[A-Z]{1}$/) { # on a rentré une colonne
		$val{'fournisseur'} = $cols[lettre2index(uc $input{'col_fournisseur'})];
	}
	if ($input{'col_remise1'} && $input{'col_remise1'} =~ /^[0-9\.\,]+$/)	{ # on a rentré un nombre
		$val{'remise1'} =  $input{'col_remise1'} ;
	} elsif ($input{'col_remise1'} && $input{'col_remise1'} =~ /^[A-Z]+$/i) { # on a rentré une colonne
		$val{'remise1'} = $cols[lettre2index(uc $input{'col_remise1'})];
	}
	if ($input{'col_remise2'} && $input{'col_remise2'} =~ /^[0-9\.\,]+$/)	{ # on a rentré un nombre
		$val{'remise2'} =  $input{'col_remise2'} ;
	} elsif ($input{'col_remise2'} && $input{'col_remise2'} =~ /^[A-Z]+$/i) { # on a rentré une colonne
		$val{'remise2'} = $cols[lettre2index(uc $input{'col_remise2'})];
	}
	if ($input{'col_remise3'} && $input{'col_remise3'} =~ /^[0-9\.\,]+$/)	{ # on a rentré un nombre
		$val{'remise3'} =  $input{'col_remise3'} ;
	} elsif ($input{'col_remise3'} && $input{'col_remise3'} =~ /^[A-Z]+$/i) { # on a rentré une colonne
		$val{'remise3'} = $cols[lettre2index(uc $input{'col_remise3'})];
	}
	if ($input{'col_remise4'} && $input{'col_remise4'} =~ /^[0-9\.\,]+$/)	{ # on a rentré un nombre
		$val{'remise4'} =  $input{'col_remise4'} ;
	} elsif ($input{'col_remise4'} && $input{'col_remise4'} =~ /^[A-Z]+$/i) { # on a rentré une colonne
		$val{'remise4'} = $cols[lettre2index(uc $input{'col_remise4'})];
	}
	if ($input{'col_marge_coop'} && $input{'col_marge_coop'} =~ /^[0-9\.\,]+$/)	{ # on a rentré un nombre
		$val{'marge_coop'} =  $input{'col_marge_coop'} ;
	} elsif ($input{'col_marge_coop'} && $input{'col_marge_coop'} =~ /^[A-Z]+$/i) { # on a rentré une colonne
		$val{'marge_coop'} = $cols[lettre2index(uc $input{'col_marge_coop'})];
	}

	#corretion des valeurs (virgule au lieu de point...)
	foreach my $col_name (qw/remise1 remise2 remise3 remise4 prix_public marge_coop poids/) {
		$val{$col_name} =~ s/,/./;
	}	

	# cacule du prix d'achat de la coop (lettre ou calcule depuis les remise et le prix public)
	$val{'prix_achat_coop'} = 0;
	if ($input{'col_prix_achat_coop'} && $cols[lettre2index($input{'col_prix_achat_coop'})] =~ /^[0-9\.\,]+$/) { # une colonne est renseigné pour le prix d'achat
		$val{'prix_achat_coop'} = $cols[lettre2index($input{'col_prix_achat_coop'})];
		$val{'prix_achat_coop'} = 0 if ($val{'prix_achat_coop'} eq '#N/D'); # #N/D est valeur non reconnu --> vide
		foreach my $col_name (qw/remise1 remise2 remise3 remise4/) {
			$val{$col_name} = 0; # on remet les remise à 0 car on a un prix net
		}	
	}
	if (!$val{'prix_achat_coop'}) { # rien n'est renseigné, on le calcule a partir des remise et du prix public
		$val{'prix_achat_coop'} = $val{'prix_public'};
		for(my $j=1 ; $j<=4 ; $j++) { # application des 4 remises successives
			$val{'prix_achat_coop'} = $val{'prix_achat_coop'} - ($val{'prix_achat_coop'} * $val{'remise'.$j} / 100);
		}
	}

	# cacule du prix de vente de la coop (lettre ou calcule depuis le prix d'achat et de la marge)
	$val{'prix_vente_coop'} = 0;
	if ($input{'col_prix_vente_coop'} && $cols[lettre2index($input{'col_prix_vente_coop'})] =~ /^[0-9\.\,]+$/) { # une colonne est renseigné pour le prix d'achat
		$val{'prix_vente_coop'} = $cols[lettre2index($input{'col_prix_vente_coop'})];
		$val{'prix_vente_coop'} = 0 if ($val{'prix_vente_coop'} eq '#N/D'); # #N/D est valeur non reconnu --> vide
	}
	if (!$val{'prix_vente_coop'}) { # rien n'est renseigné, on le calcule a partir du prix d'achat et de la marge
		$val{'prix_vente_coop'} = $val{'prix_achat_coop'} + ($val{'prix_achat_coop'} * $val{'marge_coop'} / 100);
	}

	#corretion des valeurs (virgule au lieu de point...)
	foreach my $col_name (qw/prix_achat_coop prix_vente_coop/) {
		$val{$col_name} =~ s/,/./;
	}	
	
	$val{'designation'} = '';
	foreach (my $j=0 ; $j<=$#col_designation ; $j++) {
		my $tmp = trim($cols[ $col_designation[$j] ]) ;
		$val{'designation'} .= "$tmp\n" if ($tmp);
	}
	$val{'designation'} =~ s/(?:\n)*$//;

	#print Dumper(\%val,\%input);
	#exit;

	if (!$val{'fournisseur'}) {
		warn "Ligne $i : fournisseur non renseigne\n"; next;
	}

	print sprintf("[\e[0;36m%05d\e[0;37m]",$i)." Ref : ".$cols[lettre2index(uc $input{'col_reference'})];

	my $sql = <<EOT ;
INSERT IGNORE INTO devis_article2 (activite,fournisseur,reference,reference_simple,designation,couleur,taille,poids,colisage,gencode,url,px_public,px_coop,remise1,remise2,remise3,remise4,px_achat_coop,marge_coop,date_creation) VALUES ('$activite','$val{fournisseur}','$val{reference}','$val{reference_simple}','$val{designation}','$val{couleur}','$val{taille}','$val{poids}','$val{collisage}','$val{gencode}','$val{url}','$val{prix_public}','$val{prix_vente_coop}','$val{remise1}','$val{remise2}','$val{remise3}','$val{remise4}','$val{prix_achat_coop}','$val{marge_coop}',NOW())
EOT
	#print "$sql\n";
	my $sth = $mysql->query($sql) ;
	if ($mysql->errmsg) {
		warn $mysql->errmsg."\n".$input{'col_reference'};
		print "\n\e[0;33m$sql\e[0;37m\n INSERT [\e[0;31mBAD\e[0;37m]\t";
	} else {
		print " INSERT [".($sth->affectedrows ? "\e[0;32mOK":"\e[0;31mNO")."\e[0;37m]\t" ;
	}
	
	if (!$sth->affectedrows) { # aucun insert de fait --> on update la ligne existante
		my $sql = <<EOT ;
UPDATE devis_article2 SET
	px_public='$val{prix_public}',
	px_coop='$val{prix_vente_coop}',
	remise1='$val{remise1}', remise2='$val{remise2}', remise3='$val{remise3}', remise4='$val{remise4}',
	px_achat_coop='$val{prix_achat_coop}',
	marge_coop='$val{marge_coop}',
	couleur='$val{couleur}',
	taille='$val{taille}',
	poids='$val{poids}',
	colisage='$val{collisage}',
	gencode='$val{gencode}'
WHERE fournisseur LIKE '$val{fournisseur}' and reference='$val{reference}'
EOT
		#print "$sql\n";
		$mysql->query($sql) ;
		if ($mysql->errmsg) {
			warn $mysql->errmsg."\n".$input{'col_reference'};
			print "\n\e[0;33m$sql\e[0;37m UPDATE [\e[0;31mBAD\e[0;37m]";
		} else {
			print " UPDATE [\e[0;32mOK\e[0;37m]" ;
		}
	} # fin update necessaire

	print "\n";
	$i++;
#	last;
}
close F;

#unset $mysql->disconnect;

print print_time()."STOP\n";
exit;


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

sub print_time {
	print strftime "[\e[0;36m%Y-%m-%d %H:%M:%S\e[0;37m] ", localtime;
	return '';
}

sub lettre2index($) {
	return ord(shift) - 65;
}

