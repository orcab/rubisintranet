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
use Getopt::Long;

my ($date,$noemail,$help);
GetOptions('date:s'=>\$date , 'noemail!'=>\$noemail, 'help|usage!'=>\$help) ;
die <<EOT if ($help);
Liste des arguments :
--date=yyyy-mm-dd
	Date des manquant à la pr&eacute;paration (aujourd'hui par d&eacute;faut)

--noemail
	N'envoi pas l'email

--usage ou --help
	Affiche ce message
EOT

my ($siecle,$annee,$mois,$jour) = (	substr(strftime('%Y',localtime),0,2),
									substr(strftime('%Y',localtime),2,2),
									strftime('%m',localtime),
									strftime('%d',localtime)
								);

################# DATE DE TRAVAIL ######################################################
if (length($date)>0 && $date !~ m/^\d{4}-\d{2}-\d{2}$/)  {
	die "Le format de date '$date' n'est pas du style yyyy-mm-dd";
}

if (length($date)>0) { # aucune date de spécifié --> on prend le dernier jour ouvré
	($siecle,$annee,$mois,$jour) 	= ($date =~ m/^(\d{2})(\d{2})-(\d{2})-(\d{2})$/);
}
#########################################################################################

# check si les commandes web ce sont bien intégrées
print get_time()."START\n";

my $cfg = new Phpconst2perlconst(-file => '../inc/config.php');

my $prefix_base_rubis = $cfg->{LOGINOR_PREFIX_BASE};
my $loginor = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";

# va chercher les eventuelles erreur dans la base rubis
print get_time()."Check les erreurs d'integration de la journee ...";
my $sql = <<EOT ;
select			SEOCLI as CODE_CLIENT,NOMCL as NOM_CLIENT,SEOLIG as NUM_LIGNE,SENART as CODE_ARTICLE,SENQTE as QTE,DESI1 as DESIGNATION1,DESI2 as DESIGNATION2
from			${prefix_base_rubis}GESTCOM.APARDEP1 STRACC
	left join	${prefix_base_rubis}GESTCOM.ACLIENP1 CLIENT
				on STRACC.SEOCLI=CLIENT.NOCLI
	left join	${prefix_base_rubis}GESTCOM.AARTICP1 ARTICLE
				on STRACC.SENART=ARTICLE.NOART
where			SENTET='ANO' and SENPRO='CDC'		-- les erreurs d'intégration sur la procédure CDC (les commandes web)
			and SENDCS='$siecle' and SENDCA='$annee' and SENDCM='$mois' and SENDCJ='$jour'	-- sur la journée choisit
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
Adh&eacute;rent : $row{NOM_CLIENT} ($row{CODE_CLIENT})
Article  : $row{DESIGNATION1} ($row{CODE_ARTICLE})
           $row{DESIGNATION2}
Ligne    : $row{NUM_LIGNE}
Quantit&eacute; : $row{QTE}

EOT
}
$loginor->Close();

$message .= <<EOT ;

Pour corriger ces erreurs, vous pouvez vous rendre dans Rubis dans l'interface :
Satellites --> Structure d'accueil
Date       : ${jour}${mois}${annee}
Provenance : CDC
Donn&eacute;es en anomalie

Un message d'erreur plus explicite sur la nature du probl&egrave;me &agrave; corriger est donn&eacute; en rouge.
EOT

# envoi le mail avec le rapport d'erreur
if ($nb_erreur > 0) {
	if ($noemail) {
		print $message;
	} else {
		send_mail({
			'smtp_serveur'	=> $cfg->{'SMTP_SERVEUR'},
			'smtp_user'		=> $cfg->{'SMTP_USER'},
			'smtp_password'	=> $cfg->{'SMTP_PASS'},
			'smtp_port'		=> $cfg->{'SMTP_PORT'},
			'from_email' 	=> 'commande@coopmcs.com',
			'from_name' 	=> 'Erreur commande web',
			'subject'		=> "Erreur d'integration de commande web du $jour/$mois/$siecle$annee",
			'message'		=> "<pre>Voici les erreurs d'int&eacute;gration de commande web dans Rubis pour la journ&eacute;e du <b>$jour/$mois/$siecle$annee</b>\n\n$message</pre>",
			'html'			=> 1,
			'to'			=> {	'aymeric.merigot@coopmcs.com'	=>	'Aymeric Merigot',
									'benjamin.poulain@coopmcs.com' 	=> 	'Benjamin Poulain'
								},
			'debug'			=> 0
		}) or die "Impossible d'envoyer le mail";
	}
}

print get_time()."END\n\n";