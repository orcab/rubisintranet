-- phpMyAdmin SQL Dump
-- version 2.11.8.1
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- Généré le : Ven 21 Novembre 2008 à 14:18
-- Version du serveur: 5.0.27
-- Version de PHP: 5.2.0

SET FOREIGN_KEY_CHECKS=0;

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `mcs`
--

-- --------------------------------------------------------

--
-- Structure de la table `anomalie`
--

CREATE TABLE IF NOT EXISTS `anomalie` (
  `id` int(11) NOT NULL auto_increment,
  `date_creation` datetime NOT NULL,
  `date_cloture` datetime default NULL,
  `createur` varchar(255) NOT NULL,
  `artisan` varchar(255) default NULL,
  `fournisseur` varchar(255) default NULL,
  `pole` tinyint(4) NOT NULL,
  `evolution` tinyint(4) NOT NULL,
  `resp_coop` tinyint(4) NOT NULL,
  `resp_adh` tinyint(4) NOT NULL,
  `resp_four` tinyint(4) NOT NULL,
  `probleme` text,
  `supprime` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `anomalie_commentaire`
--

CREATE TABLE IF NOT EXISTS `anomalie_commentaire` (
  `id` int(11) NOT NULL auto_increment,
  `id_anomalie` int(11) NOT NULL,
  `date_creation` datetime NOT NULL,
  `createur` varchar(255) NOT NULL,
  `type` enum('telephone','fax','visite','courrier','email','autre') NOT NULL,
  `humeur` tinyint(4) default NULL,
  `commentaire` text,
  `supprime` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `article`
--

CREATE TABLE IF NOT EXISTS `article` (
  `id` int(11) NOT NULL auto_increment,
  `code_article` varchar(15) NOT NULL,
  `designation` varchar(122) default NULL COMMENT 'trois fois 40 car + 2CR',
  `gencod` varchar(13) default NULL COMMENT 'code barre',
  `servi_sur_stock` tinyint(1) NOT NULL,
  `conditionnement` int(11) default NULL,
  `surconditionnement` int(11) default NULL,
  `unite` enum('BTE','CEN','COL','HEU','KG','L','MIL','ML','M2','M3','PCE','PLA','SAC','TON','UN') NOT NULL,
  `activite` varchar(3) default NULL,
  `famille` varchar(3) default NULL,
  `sousfamille` varchar(3) default NULL,
  `chapitre` varchar(3) default NULL,
  `souschapitre` varchar(3) default NULL,
  `chemin` varchar(19) NOT NULL,
  `fournisseur` varchar(35) default NULL,
  `ref_fournisseur` varchar(255) default NULL,
  `ref_fournisseur_condensee` varchar(255) default NULL,
  `prix_brut` decimal(10,2) default NULL,
  `prix_net` decimal(10,2) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code_article` (`code_article`),
  KEY `fourn` (`fournisseur`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `artisan`
--

CREATE TABLE IF NOT EXISTS `artisan` (
  `id` int(11) NOT NULL auto_increment,
  `numero` varchar(6) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `suspendu` tinyint(1) NOT NULL default '0',
  `email` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `nom` (`nom`),
  UNIQUE KEY `numero` (`numero`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `commande_adherent_relance`
--

CREATE TABLE IF NOT EXISTS `commande_adherent_relance` (
  `id` int(11) NOT NULL auto_increment,
  `NOBON` varchar(6) NOT NULL,
  `date` datetime NOT NULL,
  `representant` varchar(255) NOT NULL,
  `type` enum('telephone','fax','visite','courrier','email') NOT NULL,
  `humeur` tinyint(4) default NULL,
  `commentaire` text,
  PRIMARY KEY  (`id`),
  KEY `NOBON` (`NOBON`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `commande_rubis_relance`
--

CREATE TABLE IF NOT EXISTS `commande_rubis_relance` (
  `id` int(11) NOT NULL auto_increment,
  `CFBON` varchar(6) NOT NULL,
  `date` datetime NOT NULL,
  `representant` varchar(255) NOT NULL,
  `type` enum('telephone','fax','visite','courrier','email') NOT NULL,
  `humeur` tinyint(4) default NULL,
  `commentaire` text,
  PRIMARY KEY  (`id`),
  KEY `NOBON` (`CFBON`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `devis`
--

CREATE TABLE IF NOT EXISTS `devis` (
  `id` int(11) NOT NULL auto_increment,
  `date` datetime NOT NULL,
  `date_maj` datetime default NULL,
  `representant` varchar(255) NOT NULL,
  `artisan` varchar(255) default NULL,
  `theme` varchar(255) NOT NULL,
  `nom_client` varchar(255) default NULL,
  `adresse_client` text,
  `adresse_client2` text,
  `codepostal_client` varchar(10) default NULL,
  `ville_client` varchar(255) default NULL,
  `tel_client` varchar(255) default NULL,
  `tel_client2` text,
  `email_client` varchar(255) default NULL,
  `num_cmd_rubis` text,
  `mtht_cmd_rubis` float(15,2) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `devis_article`
--

CREATE TABLE IF NOT EXISTS `devis_article` (
  `id` int(11) NOT NULL auto_increment,
  `code_article` varchar(15) default NULL,
  `ref_fournisseur` varchar(255) NOT NULL,
  `fournisseur` varchar(35) default NULL,
  `designation` text,
  `prix_public_ht` decimal(10,2) default NULL,
  `remise` decimal(4,2) default '0.00',
  `date_creation` datetime NOT NULL,
  `date_maj` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `ref_fournisseur` (`ref_fournisseur`,`fournisseur`),
  KEY `ref_fournisseur_2` (`ref_fournisseur`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `devis_ligne`
--

CREATE TABLE IF NOT EXISTS `devis_ligne` (
  `id` int(11) NOT NULL auto_increment,
  `id_devis` int(11) NOT NULL,
  `code_article` varchar(15) default NULL,
  `ref_fournisseur` varchar(255) default NULL,
  `fournisseur` varchar(35) default NULL,
  `designation` text,
  `qte` int(11) default NULL,
  `puht` decimal(10,2) default NULL,
  `pu_adh_ht` decimal(10,2) default '0.00',
  `stock` tinyint(1) NOT NULL default '0',
  `expo` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `id_devis` (`id_devis`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `devis_relance`
--

CREATE TABLE IF NOT EXISTS `devis_relance` (
  `id` int(11) NOT NULL auto_increment,
  `id_devis` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `representant` varchar(255) NOT NULL,
  `type` enum('telephone','fax','visite','courrier','email') NOT NULL,
  `humeur` tinyint(4) default NULL,
  `commentaire` text,
  PRIMARY KEY  (`id`),
  KEY `id_devis` (`id_devis`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `devis_rubis_relance`
--

CREATE TABLE IF NOT EXISTS `devis_rubis_relance` (
  `id` int(11) NOT NULL auto_increment,
  `NOBON` varchar(6) NOT NULL,
  `date` datetime NOT NULL,
  `representant` varchar(255) NOT NULL,
  `type` enum('telephone','fax','visite','courrier','email') NOT NULL,
  `humeur` tinyint(4) default NULL,
  `commentaire` text,
  PRIMARY KEY  (`id`),
  KEY `NOBON` (`NOBON`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `employe`
--

CREATE TABLE IF NOT EXISTS `employe` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `prenom` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `email` varchar(255) default NULL,
  `loginor` varchar(6) default NULL,
  `code_vendeur` varchar(3) default NULL,
  `tel` varchar(255) default NULL,
  `ip` varchar(255) default NULL,
  `machine` varchar(255) default NULL,
  `printer` tinyint(1) NOT NULL default '0',
  `droit` tinyint(4) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `historique_article`
--

CREATE TABLE IF NOT EXISTS `historique_article` (
  `id` int(11) NOT NULL auto_increment,
  `code_article` varchar(15) default NULL,
  `titre` varchar(40) NOT NULL,
  `description` text,
  `de_la_part` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `date_demande` datetime NOT NULL,
  `date_creation` datetime default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code_article` (`code_article`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `pdvente`
--

CREATE TABLE IF NOT EXISTS `pdvente` (
  `id` int(11) NOT NULL auto_increment,
  `code` varchar(3) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `activite_pere` varchar(3) default NULL,
  `famille_pere` varchar(3) default NULL,
  `sousfamille_pere` varchar(3) default NULL,
  `chapitre_pere` varchar(3) default NULL,
  `chemin` varchar(19) default NULL,
  `niveau` int(3) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `chemin` (`chemin`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `send_document`
--

CREATE TABLE IF NOT EXISTS `send_document` (
  `id` int(11) NOT NULL auto_increment,
  `numero_artisan` varchar(255) NOT NULL,
  `AR` varchar(255) NOT NULL,
  `BL` varchar(255) NOT NULL,
  `RELIQUAT` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `numero_artisan` (`numero_artisan`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `tarif_article`
--

CREATE TABLE IF NOT EXISTS `tarif_article` (
  `id` int(11) NOT NULL auto_increment,
  `code_article` varchar(15) NOT NULL,
  `designation` text,
  `id_categ` int(11) default NULL,
  `image` text,
  `id_style` int(11) default NULL,
  `electromenager` tinyint(1) NOT NULL default '0',
  `ref_fournisseur` varchar(255) default NULL,
  `px_coop_ht` float(11,2) default NULL,
  `px_adh_ht` float(11,2) default NULL,
  `px_pub_ttc` float(11,2) default NULL,
  `px_eco_ttc` float(11,2) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code_article` (`code_article`),
  KEY `id_categ` (`id_categ`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `tarif_categ`
--

CREATE TABLE IF NOT EXISTS `tarif_categ` (
  `id` int(11) NOT NULL auto_increment,
  `nom` varchar(255) NOT NULL,
  `chemin` varchar(255) NOT NULL,
  `image` varchar(100) default NULL,
  `saut_de_page` tinyint(1) NOT NULL default '0',
  `id_style` int(11) default NULL,
  `page_de_garde` text,
  `electromenager` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `chemin` (`chemin`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `tarif_style`
--

CREATE TABLE IF NOT EXISTS `tarif_style` (
  `id` int(11) NOT NULL auto_increment,
  `nom` varchar(255) NOT NULL,
  `valeur` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `devis_ligne`
--
ALTER TABLE `devis_ligne`
  ADD CONSTRAINT `devis_ligne_fk` FOREIGN KEY (`id_devis`) REFERENCES `devis` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS=1;
