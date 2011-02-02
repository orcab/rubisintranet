#!/usr/bin/perl

# Ce programme doit analyser les QRCode des documents et les ranger dans une table MySQL et dans un répertoire

BEGIN {
	push @INC, "../../scripts/" ;
}
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;

use strict ;
use Data::Dumper;
use Config::IniFiles;
use Cwd;
use JSON;
use POSIX qw(strftime);
use File::Basename;
use File::Find;
use File::Copy; #move
use File::Path;	#mkpath
use Mysql;
use threads;
use Thread::Semaphore;
use BarcodeScanner;
use Win32::Console::ANSI;
use Data::Uniqid qw ( luniqid );
my %tattri = qw/all_attributes_off 0 bold_on 1 underscore_on 4 reverse_video_on 7 concealed_on 8 bold_off 21 underscore_off 24 reverse_video_off 27 concealed_off 28/;
my %fcolor = qw/black 30 red 31 green 32 yellow 33 blue 34 magenta 35 cyan 36 white 37/;
my %bcolor = qw/black 40 red 41 green 42 yellow 43 blue 44 magenta 45 cyan 46 white 47/;

use constant {
	DIRECTORY_TO_SCAN	=>	'temp',
	DIRECTORY_TO_STORE	=>	'documents',
	DIRECTORY_THUMBNAIL	=>	'thumbs',
	DIRECTORY_REJECTED	=>	'rejected'
};


die "Ne peux pas trouver le fichier de configuration 'decode_qrcode.ini' ($!)"	if !-e 'decode_qrcode.ini';
my $ini	= new Config::IniFiles (-file => 'decode_qrcode.ini') or die "erreur dans la lecture du fichier ini";

die "Ne peux pas trouver l'application 'IrfanView' dans '".	$ini->val(qw/PATH irfanview/)."' ($!)"	if !-e $ini->val(qw/PATH irfanview/);
die "Ne peux pas trouver l'application 'zbar' dans '".		$ini->val(qw/PATH zbar/)."' ($!)"		if !-e $ini->val(qw/PATH zbar/);

my	$irfanview = $ini->val(qw/PATH irfanview/);
	$irfanview =~ s/\//\\/g;

my	$getcwd = getcwd;
	$getcwd =~ s/\//\\/g;

print print_time()."START\n";

# chargement des id de connexion
my	$cfg = new Phpconst2perlconst(-file => '../../inc/config.php');

# le sémaphore permet de réguler le nombre de jobs en simultané, bloquant le processus principal tant qu'il n'y a pas de place de libre
my $semaphore = Thread::Semaphore->new($ini->val(qw/THEARDS number_of_threads/)); # precise le nombre de jobs simultanés

# on fait le traitement en boucle
while(1) {
	# ouvre le répertoire de scanne et examine chaque document
	find({ 'wanted' => \&wanted , 'no_chdir'=>1 }, DIRECTORY_TO_SCAN);
	sleep($ini->val('WAIT','for') || 10); # patiente Nsec pour ne pas surcharger le system
}


# chaque fois que l'on trouve un fichier
sub wanted {
	if (/\.(?:jpe?g|png|gif)$/i) { #fichier JPEG ou PNG

		# avons nous une place de libre ?
		$semaphore->down();

		# si le sémaphore est a 0, le processus principal va se bloquer en attendant une nouvelle place
		my $thr = threads->create("scanne_image", \$semaphore );
		# détache le job du thread principal, rend la main au thread principal
		$thr->detach();

	} # fin jpeg
} # fin wanted

print print_time()."END\n\n";





sub scanne_image {
		my $sema_ref = shift;

		# premiere chose à faire --> renommer le fichier avec un uniqid pour éviter les conflits de fichiers
		my $uniqid = luniqid;
		move($_,DIRECTORY_TO_SCAN."/$uniqid.jpg") or die "Impossible de renommer le fichier '$_' ($!)";
		$_ = DIRECTORY_TO_SCAN."/$uniqid.jpg";

		my $filename_escape = quotify(basename($_));
		
		my $directory_date_unix_style = strftime("%Y/%m/%d", localtime);
		my	$directory_date_windows_style = $directory_date_unix_style;
			$directory_date_windows_style =~ s/\//\\/g;
		################################### CREE REPERTOIRE DE LA MINIATURE #########################
		my $directory_thumbnail = '';

		################################### ON LANCE UNE RECHERCHE DE CODE BARRE #########################
		print print_time()."Scanning '".basename($_)."'\n";
		my $scan		= new BarcodeScanner(-filename => $_, -zbar => $ini->val(qw/PATH zbar/));
		my $barcodes	= $scan->scan(qw/qrcode/);
		
		################################### ON A TROUVE UN CODE BARRE #########################
		if (defined $barcodes->[0]) { # on a trouvé au moins un code barre
			print print_time()."\e[$tattri{bold_on};$fcolor{white};$bcolor{green}m+QRCode\e[m found for '".basename($_)."'\n";
			my $qrcode = $barcodes->[0]->{'code'}; # on ne récupere que le 1er barcode
			#print "\e[$fcolor{cyan}m$qrcode\e[m\n";

			################################### ON DECODE LE JSON DU CODE BARRE #########################
			my $json = decode_json($qrcode);
			map { $json->{$_} =~ s/'/''/g; } keys %$json;


			################################### INSERT LES VALEURS DANS MYSQL #########################
			my $sql = <<EOT ;
REPLACE INTO ged_document
(id,filename,code_type_document,print_by,date_print,date_scan,page,key1,key2,manual,deleted)
VALUES
('','$filename_escape','$$json{t}','$$json{u}',FROM_UNIXTIME('$$json{d}'),NOW(),'$$json{p}','$$json{b}','$$json{c}',0,0)
EOT
			print print_time()."Inserting in Database\n";
			my	$mysql = Mysql->connect($cfg->{MYSQL_HOST},$cfg->{MYSQL_BASE},$cfg->{MYSQL_USER},$cfg->{MYSQL_PASS})	or die "Peux pas se connecter a mysql";
				$mysql->selectdb($cfg->{MYSQL_BASE})																	or die "Peux pas selectionner la base mysql";
				$mysql->query($sql); # insertion dans MySQL
			undef($mysql);
			$directory_thumbnail = $getcwd.'\\'.DIRECTORY_THUMBNAIL.'\\'.$directory_date_windows_style;
			#print "OK\n";

		} else {
			################################### AUCUNE CODE BARRE DE TROUVE --> DEPLACE DANS REPERTOIRE DE REJET#########################
			print print_time()."\e[$tattri{bold_on};$fcolor{white};$bcolor{red}m-QRCode\e[m not found for '".basename($_)."'\n";
			$directory_thumbnail = $getcwd.'\\'.DIRECTORY_THUMBNAIL;
		}

		################################### ON GENERE UNE MINIATURE #########################
		print print_time()."Create thumb '$_'\n";
		my	$filename = $getcwd.'/'.$_;
			$filename =~ s/\//\\/g;

		mkpath($directory_thumbnail) if !-d $directory_thumbnail; # cree le répertoire d'accueil des thumbnail s'il n'existe pas
		my $cmd = join(' ',
						'"'.$irfanview.'"',										# le programme de traitement d'image
						'"'.$filename.'"',										# le fichier a convertir
						'/resample',											# meilleur qualité
						'/aspectratio',											# garde les proportions de l'image
						'/resize_short=100',									# 100px de large sur le petit coté
						'/gray',												# en niveau de gris
						'/contrast=10',											# auguement le contraste de 10pt
						'"/convert='.$directory_thumbnail.'\\$N_thumb.jpg"',	# le fichier de sortie
						'/jpgq=70'												# fixe la qualité jpeg a 70
				);
		`$cmd`;
		#print "\n$cmd\n";
		#print "OK\n";

	
		################################### DEPLACE LE FICHIER TRAITE DANS UN SOUS REPERTOIRE #########################
		if (defined $barcodes->[0]) {
			mkpath(DIRECTORY_TO_STORE.'/'.$directory_date_unix_style) if !-d DIRECTORY_TO_STORE.'/'.$directory_date_unix_style; # cree le répertoire d'accueil
			move($_, DIRECTORY_TO_STORE.'/'.$directory_date_unix_style.'/'.basename($_)) or die "Ne peux pas déplacer le fichier '$_' dans '".DIRECTORY_TO_STORE."/$directory_date_unix_style' ($!)";
		} else {
			move($_, DIRECTORY_REJECTED.'/'.basename($_)) or die "Ne peux pas déplacer le fichier '$_' dans '".DIRECTORY_REJECTED."' ($!)";
		}
	
		# on a une place de libre. Ne pas oublier de libérer le sémaphore même en cas d'erreur
		$$sema_ref->up();
		
		return;
} # fin scanne image


###################################################
sub print_time {
	print strftime "\e[$fcolor{yellow}m[%Y-%m-%d %H:%M:%S]\e[0m ", localtime;
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