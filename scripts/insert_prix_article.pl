#!/usr/bin/perl

use Data::Dumper;
use Win32::ODBC;
use strict ;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
use Config::IniFiles;
require 'Interfaces Rubis-Reflex/useful.pl';

print get_time()." START\n";

my $ini = new Config::IniFiles( -file => 'insert_cde_rubis_internet.ini' );
my $cfg = new Phpconst2perlconst(-file => '../inc/config.php');
my $prefix_base_rubis = $cfg->{LOGINOR_PREFIX_BASE};
my $loginor = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";

open(SQL,'+>prix_article.sql') or die "ne peux pas creer le fichier SQL ($!)" ;

print get_time()." Select des prix...";
my $sql = <<EOT;
select	A.NOART as CODE_ARTICLE,
		ROUND(PV.PVEN1,4) as PRIX_VENTE_ADH,
		ROUND(PV.PVEN1 * 1.5,4) as PRIX_AVEC_COEF,
		ROUND(PV.PVEN6,4) as PRIX_VENTE_PUBLIC,
		PARVT as PRIX_ACHAT_BRUT,
		A.ACTIV as CODE_ACTIVITE,
		TANU0 as ECOTAXE									-- L'ecotaxe dans la table ATABLEP1
from	${prefix_base_rubis}GESTCOM.AARTICP1 A
			left join ${prefix_base_rubis}GESTCOM.ATARPVP1 PV
				on A.NOART=PV.NOART
			left join ${prefix_base_rubis}GESTCOM.ATARPAP1 PR
				on A.NOART=PR.NOART
			left join ${prefix_base_rubis}GESTCOM.ATABLEP1 TAXE
				on A.TPFAR=TAXE.CODPR and TAXE.TYPPR='TPF'
where
		PV.PVT09='E'			-- tarif de vente en cours
	and PR.PRV03='E'			-- tarif de revient en cours
	and A.ARDIV<>'OUI'			-- pas d'article divers
	and A.NOART not like '15%'	-- pas d'article anciennement en expo
	and A.ETARE=''				-- pas d'article suspendu
	--and A.NOART='04001953'		-- pour les tests
EOT

$loginor->Sql($sql);
print "OK\n";

while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	map { $row{$_} = trim(quotify($row{$_})) ; } keys %row ;

	#calcul du prix public
	$row{'TARIF_PUBLIC'} = $row{'PRIX_VENTE_PUBLIC'}; # on prend le champ réservé en premier lieu

	if ($row{'TARIF_PUBLIC'} < $row{'PRIX_ACHAT_BRUT'}) { # si le tarif public est inférieur au prix d'achat sans remise, on prend le plus haut
		$row{'TARIF_PUBLIC'} = $row{'PRIX_ACHAT_BRUT'};
	}

=begin
	if ($row{'TARIF_PUBLIC'} < $row{'PRIX_VENTE_ADH'}) {
		$row{'TARIF_PUBLIC'} = 0 ; # si le prix public est inférieur au prix adh --> prix négocier donc prix public NC
	}
=cut
	if ($row{'TARIF_PUBLIC'} < $row{'PRIX_VENTE_ADH'}) {
		$row{'TARIF_PUBLIC'} = 0 ; # si le prix public est inférieur au prix adh --> prix négocier donc prix public NC
	}

	if		($row{'TARIF_PUBLIC'} <= 0) {						# prix public vide, on prend le prix adh * coef
		$row{'TARIF_PUBLIC'} = $row{'PRIX_AVEC_COEF'};
	} elsif ($row{'PRIX_AVEC_COEF'} < $row{'TARIF_PUBLIC'} && $row{'CODE_ACTIVITE'} ne '00D') {
		$row{'TARIF_PUBLIC'} = $row{'PRIX_AVEC_COEF'};
	}
	
	print SQL "UPDATE article SET prix_brut='$row{PRIX_VENTE_ADH}',prix_net='$row{PRIX_VENTE_ADH}',prix_public='$row{TARIF_PUBLIC}',ecotaxe='$row{ECOTAXE}' WHERE code_article='$row{CODE_ARTICLE}';\n";
}

$loginor->Close();
close SQL;

# on compress la base pour l'envoyé sur le serveur FTP
print get_time()." Compression du fichier SQL ... ";
system("bzip2 -zkf8 prix_article.sql");
print "OK\n";

print get_time()." Transfert ... ";
my $cmd = join(' ',	'pscp',
					'-scp',
					'-pw',
					$ini->val(qw/SSH pass/),
					'prix_article.sql.bz2',
					$ini->val(qw/SSH user/).'@'.$ini->val(qw/SSH host/).':prix_article.sql.bz2'
			);
`$cmd`;
print "OK\n";

print get_time()." Decompression ... ";
my $cmd = join(' ',	'plink',
					'-pw',
					$ini->val(qw/SSH pass/),
					$ini->val(qw/SSH user/).'@'.$ini->val(qw/SSH host/),
					'./insert-prix-article.sh'
			);
`$cmd`;
print "OK\n";

END: ;
print get_time()." END\n\n";