#!/usr/bin/perl
use Data::Dumper;
use strict ;
$| = 1; # active le flush direct

# SCRIPT pour transformer les fichiers CSV des fournisseurs en fichier SQL pour la salle expo

unless ($ARGV[0]) { die "USAGE : fournisseur2expo fichier.csv\n" }
open(F,$ARGV[0]) or die "Ne peux pas ouvrir $ARGV[0] ($!)";

my ($fournisseur,$skip_first_line,$col_ref,$col_prix,$col_designation) ;

#my ($fournisseur,$skip_first_line,$col_ref,$col_prix,$col_designation) = ('OLFA','o','A','D','B , C') ;

do { print "Nom du fournisseur : ";									$fournisseur = <STDIN>;		$fournisseur = trim($fournisseur); }			until $fournisseur;
open(OUT_UPDATE,"+>$fournisseur-update.sql") or die "Ne peux pas creer '$fournisseur-update.sql' ($!)";
open(OUT_INSERT,"+>$fournisseur-insert.sql") or die "Ne peux pas creer '$fournisseur-insert.sql' ($!)";
do { print "Saute 1ere ligne ? (o/n) [n] : ";						$skip_first_line = <STDIN>; $skip_first_line = trim($skip_first_line); }	until $skip_first_line eq '' || $skip_first_line eq 'o' || $skip_first_line eq 'O' || $skip_first_line eq 'n' || $skip_first_line eq 'N' ;
do { print "Lettre colonne ref : ";									$col_ref = <STDIN>;			$col_ref = uc trim($col_ref); }					until $col_ref =~ /^[A-Z]+$/;
do { print "Lettre colonne prix public : ";							$col_prix = <STDIN>;		$col_prix = uc trim($col_prix); }				until $col_prix =~ /^[A-Z]+$/;
do { print "Lettre colonne(s) designation (separe par virgule) : ";	$col_designation = <STDIN>; $col_designation = uc trim($col_designation); }	until $col_designation =~ /^[A-Z,]+$/;

$fournisseur = quotify($fournisseur);

# on transforme les lettre en num de col
$col_ref = ord($col_ref) - 65;
$col_prix = ord($col_prix) - 65;
my @col_designation = split(/,/,$col_designation);
foreach (@col_designation) { $_ = ord(trim($_)) - 65; }

if ($skip_first_line eq 'o' || $skip_first_line eq 'O' ) { <F>; }

my $i=0;
while(<F>) {
	my @cols = split /;/ ;
	if ($#cols < 2) { # 3 col minimum
		warn "Ligne $i : nombre de colonne insuffisant ($#cols)\n"; next;
	}

	#trim + quotify
	for(my $j=0 ; $j<=$#cols ; $j++) { $cols[$j] = trim(quotify($cols[$j])) ; $cols[$j] =~ s/[ ]//g; }

	# transforme les ',' du prix en '.'
	$cols[$col_prix] =~ s/,/./;

	my $designation = '';
	foreach (my $j=0 ; $j<=$#col_designation ; $j++) {
		my $tmp = trim($cols[$col_designation[$j]]) ;
		if ($tmp) {
			$designation .= "$tmp\\n"
		}
	}
	$designation =~ s/(?:\\n)*$//;

	#print Dumper(\@cols);

	print OUT_UPDATE "UPDATE devis_article SET date_maj=NOW(), prix_public_ht=".$cols[$col_prix]." WHERE ref_fournisseur='".$cols[$col_ref]."' AND fournisseur='$fournisseur';\n";
	print OUT_INSERT "INSERT INTO devis_article (ref_fournisseur,fournisseur,designation,prix_public_ht,date_creation) VALUES ('".$cols[$col_ref]."','$fournisseur','$designation',".$cols[$col_prix].",NOW());\n";
	print "Ref : ".$cols[$col_ref]."\n";
	
	$i++;
	#last;
}
close F;
close OUT_UPDATE;
close OUT_INSERT;


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
	print strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
	return '';
}

