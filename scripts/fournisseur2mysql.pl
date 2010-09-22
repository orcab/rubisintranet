#!/usr/bin/perl
use POSIX qw(strftime floor);
use Data::Dumper;
use Mysql ;
use strict ;
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
$| = 1; # active le flush direct

# SCRIPT pour transformer les fichiers CSV des fournisseurs en fichier SQL pour la salle expo
open(LOG,"+>rapport-".strftime("%Y-%m-%d %Hh%Mm%Ss",localtime).'.txt') or die "Ne peux pas créer de fichier de rapport";

print LOG print_time()."START\n";

my	$cfg = new Phpconst2perlconst(-file => 'fournisseur2mysql-config.php');
my	$ip  = $cfg->{IP};
my	$mysql = Mysql->connect($cfg->{MYSQL_HOST},$cfg->{MYSQL_BASE},$cfg->{MYSQL_USER},$cfg->{MYSQL_PASS}) or die "Peux pas se connecter a mysql";
	$mysql->selectdb($cfg->{MYSQL_BASE}) or die "Peux pas selectionner la base mysql";

my $file = 'fournisseur2mysql.csv'; #nom du fichier a importer par défaut

if ($ARGV[0]) { # si un argument est passé --> nom du fichier
	$file = $ARGV[0] ;
}
open(F,$file) or die "Ne peux pas ouvrir $file ($!)";


my $i=0;
my %cols = ();
my @cols_skip = ();
my $first_ligne = <F> ;
$first_ligne = trim($first_ligne);
my @cols = split /;/,$first_ligne ;	# coupe sur les ;
# on tente de retrouver les valeurs des différentes colonnes
for ($i=0 ; $i<=$#cols ; $i++) {
	$_ = trim(lc $cols[$i]);
	s/[ _-]//g; # suppresion des espace entre les mots
	tr/éêèëàäâùüûôöòîïìç/eeeeaaauuuoooiiic/; # suppression des car non francais
	   if (/^act(?:ivite)?$/)						{	$cols{'activite'} = $i ;		print LOG "Colonne ACTIVITE=".index2lettre($i)."\n" ; }
	elsif (/fournisseur/)							{	$cols{'fournisseur'} = $i ;		print LOG "Colonne FOURNISSEUR=".index2lettre($i)."\n" ; }
	elsif (/^ref(?:erence)?$/)						{	$cols{'reference'} = $i ;		print LOG "Colonne REFERENCE=".index2lettre($i)."\n" ; }
	elsif (/designation\s*1/)						{	$cols{'designation1'} = $i ;	print LOG "Colonne DESIGNATION1=".index2lettre($i)."\n" ; }
	elsif (/designation\s*2/)						{	$cols{'designation2'} = $i ;	print LOG "Colonne DESIGNATION2=".index2lettre($i)."\n" ; }
	elsif (/designation\s*3/)						{	$cols{'designation3'} = $i ;	print LOG "Colonne DESIGNATION3=".index2lettre($i)."\n" ; }
	elsif (/designation\s*4/)						{	$cols{'designation4'} = $i ;	print LOG "Colonne DESIGNATION4=".index2lettre($i)."\n" ; }
	elsif (/designation\s*5/)						{	$cols{'designation5'} = $i ;	print LOG "Colonne DESIGNATION5=".index2lettre($i)."\n" ; }
	elsif (/gencode?|codebarre/)					{	$cols{'gencode'} = $i ;			print LOG "Colonne GENCODE=".index2lettre($i)."\n" ; }
	elsif (/^prixpublic|pxpublic|pp(?:ht)?$/)		{	$cols{'prix_public'} = $i ;		print LOG "Colonne PRIX_PUBLIC=".index2lettre($i)."\n" ; }
	elsif (/^prixnet|pxachat|prixachat|pa(?:ht)?$/)	{	$cols{'prix_achat'} = $i ;		print LOG "Colonne PRIX_ACHAT=".index2lettre($i)."\n" ; }
	elsif (/remise\s*1/)							{	$cols{'remise1'} = $i ;			print LOG "Colonne REMISE1=".index2lettre($i)."\n" ; }
	elsif (/remise\s*2/)							{	$cols{'remise2'} = $i ;			print LOG "Colonne REMISE2=".index2lettre($i)."\n" ; }
	elsif (/remise\s*3/)							{	$cols{'remise3'} = $i ;			print LOG "Colonne REMISE3=".index2lettre($i)."\n" ; }
	elsif (/remise\s*4/)							{	$cols{'remise4'} = $i ;			print LOG "Colonne REMISE4=".index2lettre($i)."\n" ; }
	elsif (/^hausse$/)								{	$cols{'hausse'} = $i ;			print LOG "Colonne HAUSSE=".index2lettre($i)."\n" ; }
	elsif (/coul(?:eur)?|color(?:is)?/)				{	$cols{'couleur'} = $i ;			print LOG "Colonne COULEUR=".index2lettre($i)."\n" ; }
	elsif (/taille/)								{	$cols{'taille'} = $i ;			print LOG "Colonne TAILLE=".index2lettre($i)."\n" ; }
	elsif (/poids?/)								{	$cols{'poids'} = $i ;			print LOG "Colonne POIDS=".index2lettre($i)."\n" ; }
	elsif (/coli(?:sage)?|colis/)					{	$cols{'collisage'} = $i ;		print LOG "Colonne COLLISAGE=".index2lettre($i)."\n" ; }
	elsif (/^url$/)									{	$cols{'url'} = $i ;				print LOG "Colonne URL=".index2lettre($i)."\n" ; }
	else  {  push @cols_skip,$cols[$i].' ('.index2lettre($i).')'; }
}

print LOG "Colonne Ignorées : ".join(', ',@cols_skip)."\n";

if (!exists $cols{'fournisseur'})	{ print LOG "Il manque la colonne FOURNISSEUR\n"; exit; }
if (!exists $cols{'reference'})		{ print LOG "Il manque la colonne REFERENCE\n"; exit; }
if (!exists $cols{'designation1'})	{ print LOG "Il manque la colonne DESIGNATION\n"; exit; }
if (!exists $cols{'prix_public'} && !exists $cols{'prix_achat'})	{ print LOG "Il n'y a ni colonne PRIX_PUBLIC ni colonne PRIX_ACHAT\n"; exit; }

#print Dumper(\%cols);



$i=1;
while(<F>) {
	chomp;
	s/ //g;
	my %val;
	my @values = split /;/ ;	# coupe sur les ;
	
	#trim des valeurs + quotify
	for(my $j=0 ; $j<=$#values ; $j++) { $values[$j] =~ s/^"|"$//g;  $values[$j] = quotify(trim($values[$j])) ; }

	#print Dumper(@cols);

	$val{'activite'}			= exists $cols{'activite'} ? $values[$cols{'activite'}] : 'sanitaire';
	$val{'fournisseur'}			= uc $values[$cols{'fournisseur'}];
	$val{'reference'}			= $values[$cols{'reference'}];
	$val{'reference_simple'}	= uc $val{'reference'};			$val{'reference_simple'} =~ s/[^A-Z0-9]//g; # on nettoi la reference officiel du fournisseur pour faciliter les recherches
	$val{'gencode'}				= exists $cols{'gencode'} ? $values[$cols{'gencode'}] : '' ;		$val{'gencode'} =~ s/ +//g;
	$val{'prix_public'}			= exists $cols{'prix_public'} ? $values[$cols{'prix_public'}] : 0 ; $val{'prix_public'}	=~ s/€//g;			$val{'prix_public'} = trim($val{'prix_public'});
	$val{'prix_achat'}			= exists $cols{'prix_achat'}  ? $values[$cols{'prix_achat'}] : 0 ;  $val{'prix_achat'} 	=~ s/€//g;			$val{'prix_achat'}  = trim($val{'prix_achat'});
	$val{'remise1'}				= 0;							$val{'remise2'} = 0;
	$val{'remise3'}				= 0;							$val{'remise4'}	= 0;
	$val{'remise1'}				= $values[$cols{'remise1'}]		if exists $cols{'remise1'};	$val{'remise2'}	= $values[$cols{'remise2'}] if exists $cols{'remise2'};
	$val{'remise3'}				= $values[$cols{'remise3'}]		if exists $cols{'remise3'};	$val{'remise4'}	= $values[$cols{'remise4'}] if exists $cols{'remise4'};
	$val{'hausse'}				= exists $cols{'hausse'} ? $values[$cols{'hausse'}] : 0 ;  $val{'hausse'} 	=~ s/\%//g;						$val{'hausse'}  = trim($val{'hausse'});
	$val{'couleur'}				= $values[$cols{'couleur'}]		if exists $cols{'couleur'};
	$val{'taille'}				= $values[$cols{'taille'}]		if exists $cols{'taille'};
	$val{'poids'}				= $values[$cols{'poids'}]		if exists $cols{'poids'};
	$val{'collisage'}			= $values[$cols{'collisage'}]	if exists $cols{'collisage'};
	$val{'url'}					= $values[$cols{'url'}]			if exists $cols{'url'};

	if (length($val{'reference'}) <= 0 || length($val{'fournisseur'}) <= 0) { next ; } # ligne vide
	

	#corretion des valeurs (virgule au lieu de point...)
	foreach my $col_name (qw/remise1 remise2 remise3 remise4 prix_public prix_achat hausse poids/) {
		$val{$col_name} =~ s/,/./;
	}	


	$val{'designation'} = '';
	foreach (my $j=1 ; $j<=5 ; $j++) {
		my $tmp = trim(exists $cols{'designation'.$j} ? $values[$cols{'designation'.$j}] : '') ;
		$val{'designation'} .= "$tmp\n" if ($tmp);
	}
	$val{'designation'} =~ s/(?:\n)*$//;


	print LOG sprintf("[%05d]",$i)." Ref : ".$val{'reference'};
	my $prix_achat = '';
	if (exists $cols{'prix_achat'} && $val{'prix_achat'}) {
		$prix_achat = $val{'prix_achat'};
	} else {
		$prix_achat = 0;
	}

	my $mysql_user = $cfg->{MYSQL_USER};

	my $sql = <<EOT ;
INSERT IGNORE INTO devis_article2 (
	activite,fournisseur,reference,reference_simple,designation,
	couleur,taille,poids,colisage,gencode,url,
	px_public,remise1,remise2,remise3,remise4,px_achat_coop,
	date_creation,qui,ip)
VALUES (
	'$val{activite}','$val{fournisseur}','$val{reference}','$val{reference_simple}','$val{designation}',
	'$val{couleur}','$val{taille}','$val{poids}','$val{collisage}','$val{gencode}','$val{url}',
	'$val{prix_public}','$val{remise1}','$val{remise2}','$val{remise3}','$val{remise4}','$prix_achat',
	NOW(),'$mysql_user','$ip'
)
EOT


	print Dumper(\%val);exit;

	#print "$sql\n";
	my $sth = $mysql->query($sql) ;
	if ($mysql->errmsg) {
		warn $mysql->errmsg."\n".$val{'reference'};
		print LOG "\n$sql\n INSERT [BAD]\t";
	} else {
		print LOG " INSERT [".($sth->affectedrows ? "OK":"NO")."]\t" ;
	}
	
	if (!$sth->affectedrows) { # aucun insert de fait --> on update la ligne existante
		# on ne spécifie un prix d'achat que si il est forcé --> sinon calcul depuis le prix public
		my $prix_achat = '';
		if (exists $cols{'prix_achat'} && $val{'prix_achat'}) {
			$prix_achat = "px_achat_coop='$val{prix_achat}',";
		} else {
			$prix_achat = "px_achat_coop='0',";
		}

		# si une hausse tarifaire est proposé (exemple : +5%) --> on modifie le prix d'achat et le prix public
		my $prix_public = '';
		my $remise = '';
		if (exists $cols{'hausse'} && $val{'hausse'} != 0) { # la hausse peut etre négative
			# on ne touche pas au remise, ni au prix d'achat
			$prix_public= "px_public=px_public + (px_public * $val{hausse}/100),";
		} else { # pas de hausse mais des prix spécifié
			$prix_public= "px_public='$val{prix_public}',";
			$remise		= "remise1='$val{remise1}', remise2='$val{remise2}', remise3='$val{remise3}', remise4='$val{remise4}',";
		}

		my $sql = <<EOT ;
UPDATE devis_article2 SET
	$prix_public
	$remise
	$prix_achat
	px_coop=0,					-- remet le prix expo forcé à 0
	couleur='$val{couleur}',
	taille='$val{taille}',
	poids='$val{poids}',
	colisage='$val{collisage}',
	gencode='$val{gencode}',
	date_modification=NOW(),
	qui='$mysql_user',
	ip='$ip'
WHERE fournisseur LIKE '$val{fournisseur}' and reference='$val{reference}'
EOT
		#print "$sql\n";
		$mysql->query($sql) ;
		if ($mysql->errmsg) {
			warn $mysql->errmsg."\n".$val{'reference'};
			print LOG "\n$sql UPDATE [BAD]";
		} else {
			print LOG " UPDATE [OK]" ;
		}
	} # fin update necessaire

	print LOG "\n";
	print "$i " if ($i % 100)==0 ;
	$i++;
}
close F;

#unset $mysql->disconnect;

print LOG print_time()."STOP\n";

close LOG;
exit;


########################################### METHOD USEFUL #################################################

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
	return strftime("[%Y-%m-%d %H:%M:%S] ", localtime);
}

sub lettre2index($) {
	return ord(shift) - 65;
}

sub index2lettre($) {
	my $col_number = shift;
	if(($col_number < 0) || ($col_number > 701)) { die "func 'index2lettre' : Column must be between 0(A) and 701(ZZ)"; }
	if($col_number < 26) {
		return(chr(ord('A') + $col_number));
	} else {
		my $remainder = floor($col_number / 26) - 1;
		return(chr(ord('A') + $remainder) . index2lettre($col_number % 26));
	}
}