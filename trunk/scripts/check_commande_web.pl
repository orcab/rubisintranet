#!/usr/bin/perl
use strict;
use Data::Dumper;
use Config::IniFiles;
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
use POSIX qw(strftime);
require 'Interfaces Rubis-Reflex/useful.pl'; # send_mail
use Win32::ODBC;
use Net::SMTP;


# check si les commandes web ce sont bien intégrées
print print_time()."START\n";

my $cfg = new Phpconst2perlconst(-file => '../inc/config.php');

my $prefix_base_rubis = $cfg->{LOGINOR_PREFIX_BASE};
my $loginor = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";

# va chercher les eventuelles erreur dans la base rubis
my ($annee,$mois,$jour) = split(/ +/,strftime("%Y %m %d", localtime));
my $siecle = substr($annee,0,2);
my $annee_deux_chiffre = substr($annee,2,2);
print print_time()."Check les erreurs d'integration de la journee ...";
my $sql = <<EOT ;
select			SEOCLI as CODE_CLIENT,NOMCL as NOM_CLIENT,SEOLIG as NUM_LIGNE,SENART as CODE_ARTICLE,SENQTE as QTE,DESI1 as DESIGNATION1,DESI2 as DESIGNATION2
from			${prefix_base_rubis}GESTCOM.APARDEP1 STRACC
	left join	${prefix_base_rubis}GESTCOM.ACLIENP1 CLIENT
				on STRACC.SEOCLI=CLIENT.NOCLI
	left join	${prefix_base_rubis}GESTCOM.AARTICP1 ARTICLE
				on STRACC.SENART=ARTICLE.NOART
where			SENTET='ANO' and SENPRO='CDC'		-- les erreurs d'intégration sur la procédure CDC (les commandes web)
			and SENDCS='$siecle' and SENDCA='$annee_deux_chiffre' and SENDCM='$mois' and SENDCJ='$jour'	-- sur la journée
		--	and SENDCS='20' and SENDCA='12' and SENDCM='04' and SENDCJ='11'								-- pour les tests
EOT
$loginor->Sql($sql); # regarde les erreurs de la journée dans la proc CDC
print "OK\n";

#print $sql."\n";

# il y a des erreurs
my $message = '';
my $nb_erreur = 0;
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	$nb_erreur++;
	#print Dumper(\%row);
	$message .= <<EOT ;
Adhérent : $row{NOM_CLIENT} ($row{CODE_CLIENT})
Article  : $row{DESIGNATION1} ($row{CODE_ARTICLE})
           $row{DESIGNATION2}
Ligne    : $row{NUM_LIGNE}
Quantité : $row{QTE}

EOT
}
$loginor->Close();

$message .= <<EOT ;
Pour corriger ces erreurs, vous pouvez vous rendre dans Rubis dans l'interface :
Satellites --> Structure d'accueil
Date       : ${jour}${mois}${annee_deux_chiffre}
Provenance : CDC
Données en anomalie

Un message d'erreur plus explicite sur la nature du problème à corriger est donné en rouge.
EOT

#print $message;

# envoi le mail avec le rapport d'erreur
if ($nb_erreur > 0) {
	send_mail({
		'smtp_serveur'	=> $cfg->{'SMTP_SERVEUR'},
		'smtp_user'		=> $cfg->{'SMTP_USER'},
		'smtp_password'	=> $cfg->{'SMTP_PASS'},
		'smtp_port'		=> $cfg->{'SMTP_PORT'},
		'from_email' 	=> 'commande@coopmcs.com',
		'from_name' 	=> 'Erreur commande web',
		'subject'		=> "Erreur d'integration de commande web du $jour/$mois/$annee",
		'message'		=> "Voici les erreurs d'intégration de commande web dans Rubis pour la journée du $jour/$mois/$annee\n\n$message",
		'html'			=> 0,
		'to'			=> {	'aymeric.merigot@coopmcs.com'	=>	'Aymeric Merigot',
								'benjamin.poulain@coopmcs.com' 	=> 	'Benjamin Poulain'
							},
		'debug'			=> 0
	}) or die "Impossible d'envoyer le mail";
}

print print_time()."END\n\n";

################################################################################

sub print_time {
	print strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
	return '';
}