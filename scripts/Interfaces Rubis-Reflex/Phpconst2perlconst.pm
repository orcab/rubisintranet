#!/usr/bin/perl

package Phpconst2perlconst;
$Phpconst2perlconst::VERSION = (qw($Revision: 0.1 $))[1];
use strict;
use Carp;
use Data::Dumper;

sub new {
	my $class = shift; #la fonction re�oit comme premier param�tre le nom de la classe
	my %param = @_;

	my $self = {}; #r�f�rence anonyme vers une table de hachage vide

	if (exists($param{'-file'})) { # si le param fichier existe

		open (F, '<'.$param{'-file'}) or carp "Impossible d'ouvrir ".$param{'-file'}." ($!)"; # on l'ouvre
		while(<F>) { # on lit chaque ligne du fichier
			if (/^\s*(?:#|\/\/|\/\*)/) { next; } # si c'est un commentaire on saute la ligne
				
			# on test le pattern des constante PHP -->    define('SOCIETE','toto');
			if (/
				^\s*				# d�but de ligne
				define				# mot cl� PHP define
				\s*\(\s*			# premiere parenthese ouvrante
				(["'])				# une simple ou double quote
				\s*?(.+?)\s*?		# on capture la constante tant que l'on ne trouve pas un espace
				\1					# une simple ou double quote captur� plus haut
				\s*,\s*				# une virgule
				(["'])				# une simple ou double quote
				(.*?)				# on capture la valeur tant que l'on ne trouve pas une simple ou double quote
				\3					# une simple ou double quote captur� plus haut
				\s*\)\s*			# fin de la parenthese
				;					# on finit avec une virgule
				/x) {

					$self->{$2} = $4 ;
			}
		}
		close(F);

	} else { # le param -file manque
		carp "Option -file manquante";
	}


	bless ($self,$class); #lie la r�f�rence � la classe
	return $self; #on retourne la r�f�rence consacr�e
}
