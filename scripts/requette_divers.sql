-- Suspension des codes expo tous les soirs
update AFAGESTCOM.AARTICP1 set ETARE='S' where NOART like '15%'  -- suspension des code expo (fiche article)
update AFAGESTCOM.ASTOFIP1 set STSTS='S' where NOART like '15%'  -- suspension des code expo (fiche stock)
update AFAGESTCOM.AARFOUP1 set ETAFE='S' where NOART like '15%'  -- suspension des code expo (fiche ref fournisseur)

-- supprime la pr�co sur les autre d�pot que les principaux
update AFAGESTCOM.ASTOFIP1 set STO10='N' where DEPOT='AFZ' or DEPOT='9FA' -- fiche de stock

-- Fournisseur unique pour le d�pot de Lorient : CESAFA
update AFAGESTCOM.ASTOFIP1 set STFOU='CESAFA' where DEPOT='AFL' -- fiche de stock

-- Passe les kit en pr�co � "non"
update AFAGESTCOM.AARTICP1 set ART06='NON' where CDKIT='OUI' -- fiche article
update AFAGESTCOM.ASTOFIP1 set STO10='N' where NOART IN (select NOART from AFAGESTCOM.AARTICP1 where CDKIT='OUI') -- fiche de stock

-- conditionnement d'achat divisible
update AFAGESTCOM.AARFOUP1 set ARF03='OUI' -- fiche article-fournisseur

-- passe les achat interdit de blanc a "non" sur la fiche de stock
update AFAGESTCOM.ASTOFIP1 set STO11='N' where STO11='' --fiche de stock

-- passe les produits de Caudan � la formule de r�appro "CES"
update AFAGESTCOM.ASTOFIP1 set STO21='CES' where DEPOT='AFL' --fiche de stock

-- pour les articles cr�er depuis plus d'un an, on passe la formule de r�appro en DFT (sur AFA)
update AFAGESTCOM.ASTOFIP1 set STO21='DFT' where DEPOT='AFA' and DAYS(DATE(NOW())) - DAYS(DATE(CONCAT(STCSS,CONCAT(STCAA,CONCAT('-',CONCAT(STCMM,CONCAT('-',STCJJ))))))) > 365

-- pour les articles cr�er depuis moins d'un an, on affecte la formule NVX (sur AFA)
update AFAGESTCOM.ASTOFIP1 set STO21='NVX' where DEPOT='AFA' and DAYS(DATE(NOW())) - DAYS(DATE(CONCAT(STCSS,CONCAT(STCAA,CONCAT('-',CONCAT(STCMM,CONCAT('-',STCJJ))))))) <= 365