#!/usr/bin/perl


# ce sript permet de fetcher tous les mails d'une boite mail et de créer un fichier de commande au format RUBIS (structure d'accueil de commande)
# il depose ensuite ce fichier sur le disque partagé pour une intégration dans Rubis

use Data::Dumper;
use POSIX qw(strftime);
use Net::POP3;
use Config::IniFiles;
use Email::Simple;
use File::Copy;
use File::Basename;
use Mysql ;
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
$|=1;
print print_time()."START\n";

# charge la config en mémoire
my $cfg  = new Config::IniFiles( -file => 'pop2sql.ini' , -nocase=>1 ) or die "Impossible de charger le fichier de config 'pop2sql.ini'";
my $cfg2 = new Phpconst2perlconst(-file => '../inc/config.php');

my $mysql = Mysql->connect($cfg2->{MYSQL_HOST},$cfg2->{MYSQL_BASE},$cfg2->{MYSQL_USER},$cfg2->{MYSQL_PASS}) or die "Peux pas se connecter a mysql";
   $mysql->selectdb($cfg2->{MYSQL_BASE}) or die "Peux pas selectionner la base mysql";

print print_time()."Traitement des requetes SQL\n";

# début du script
POP3_FETCH:
print print_time()."POP3 connection ... ";
my $pop3 = Net::POP3->new($cfg->val('pop3','host'), ResvPort=>$cfg->val('pop3','port') , Timeout => 30) or die "Impossible de se connecter au serveur POP3";
print "ok\n";

print print_time()."POP3 authentification ... ";
my $authentification = $pop3->login($cfg->val('pop3','user'), $cfg->val('pop3','pass'));
if (defined($authentification) && $authentification > 0) {
	print "ok\n";
	foreach my $msgnum (keys %{$pop3->list}) {
		my $email= Email::Simple->new(join('',@{$pop3->get($msgnum)}));
		my ($messageId) = ($email->header('Message-Id') =~ m/^<(.+?)\@/i);
		$messageId = substr($messageId, 0 , 15);
		my $subject   = $cfg->val('pop3','valid_subject');

		# procedure de la validation que l'email est bien une commande
		if ($email->header('Subject') =~ m/^$subject/i) { # ok le sujet est valide, on l'examine
			print print_time()."SQL Command found\n";
			my $sql = $email->body;
			chomp($sql);
			#print "SQL $msgnum='$sql'\n";
			$mysql->query($sql);

			# on supprime le message
			print print_time()."Deleting command $msgnum ... ";
			$pop3->delete($msgnum);
			print "ok\n";

		} else {
			print print_time()."Malformed email found ($msgnum). Deleting ... ";
			$pop3->delete($msgnum);
			print "ok\n";
		}
	}
} elsif ($authentification == '0E0') {
	print "ok, but no message\n";
	goto END;
} else {
	die "Impossible de s'identifier sur le serveur POP3";
}

END:
$pop3->quit;
print print_time()."END\n\n";



sub print_time {
	print strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
	return '';
}