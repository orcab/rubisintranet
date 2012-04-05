-- Suspension des codes expo tous les soirs
update AFAGESTCOM.AARTICP1 set ETARE='S' where NOART like '15%' and ETARE='' -- suspension des code expo (fiche article)
update AFAGESTCOM.ASTOFIP1 set STSTS='S' where NOART like '15%' and STSTS='' -- suspension des code expo (fiche stock)
update AFAGESTCOM.AARFOUP1 set ETAFE='S' where NOART like '15%' and ETAFE='' -- suspension des code expo (fiche ref fournisseur)

-- supprime la préco sur les autre dépot que les principaux
update AFAGESTCOM.ASTOFIP1 set STO10='N' where DEPOT='AFZ' or DEPOT='9FA' or DEPOT='EXP' -- fiche de stock

-- passe les servi sur stock à "non" sur les dépot autre que principaux
update AFAGESTCOM.ASTOFIP1 set STSER='NON' where STSER='OUI' and (DEPOT='AFZ' or DEPOT='9FA' or DEPOT='EXP') -- fiche de stock

-- Fournisseur unique pour le dépot de Lorient : CESAFA
update AFAGESTCOM.ASTOFIP1 set STFOU='CESAFA' where DEPOT='AFL' -- fiche de stock

-- Passe les kit en préco à "non"
update AFAGESTCOM.AARTICP1 set ART06='NON' where CDKIT='OUI' -- fiche article
update AFAGESTCOM.ASTOFIP1 set STO10='N' where NOART IN (select NOART from AFAGESTCOM.AARTICP1 where CDKIT='OUI') -- fiche de stock

-- conditionnement d'achat divisible
update AFAGESTCOM.AARFOUP1 set ARF03='OUI' where NOART like '02%' -- fiche article-fournisseur

-- passe les achat interdit de blanc a "non" sur la fiche de stock
update AFAGESTCOM.ASTOFIP1 set STO11='N' where STO11='' --fiche de stock

-- passe les produits de Caudan à la formule de réappro "CES"
--update AFAGESTCOM.ASTOFIP1 set STO21='CES' where DEPOT='AFL' --fiche de stock

-- pour les articles créer depuis plus d'un an, on passe la formule de réappro en DFT (sur AFA et AFL)
update AFAGESTCOM.ASTOFIP1 set STO21='DFT' where (DEPOT='AFA' or DEPOT='AFL') and DAYS(DATE(NOW())) - DAYS(DATE(CONCAT(STCSS,CONCAT(STCAA,CONCAT('-',CONCAT(STCMM,CONCAT('-',STCJJ))))))) > 365

-- pour les articles créer depuis moins d'un an, on affecte la formule NVX (sur AFA et AFL)
update AFAGESTCOM.ASTOFIP1 set STO21='NVX' where (DEPOT='AFA' or DEPOT='AFL') and DAYS(DATE(NOW())) - DAYS(DATE(CONCAT(STCSS,CONCAT(STCAA,CONCAT('-',CONCAT(STCMM,CONCAT('-',STCJJ))))))) <= 365

-- affecte des gestionnaires de stock par défaut pour les articles sans gestionnaire en fonction de l'activité article AFA
update AFAGESTCOM.ASTOFIP1 set STGES='RLF' where STGES='' and DEPOT='AFA' and NOART in (select NOART from AFAGESTCOM.AARTICP1 where ACTIV='00A' or ACTIV='00M')
update AFAGESTCOM.ASTOFIP1 set STGES='JM' where STGES='' and DEPOT='AFA' and NOART in (select NOART from AFAGESTCOM.AARTICP1 where ACTIV='00B' or ACTIV='00P')
update AFAGESTCOM.ASTOFIP1 set STGES='BT' where STGES='' and DEPOT='AFA' and NOART in (select NOART from AFAGESTCOM.AARTICP1 where ACTIV='00D' or ACTIV='00E' or ACTIV='00F' or ACTIV='00H' or ACTIV='00X')
update AFAGESTCOM.ASTOFIP1 set STGES='CK' where STGES='' and DEPOT='AFA' and NOART in (select NOART from AFAGESTCOM.AARTICP1 where ACTIV='00C' or ACTIV='00G' or ACTIV='00J' or ACTIV='00K' or ACTIV='00L' or ACTIV='00N' or ACTIV='00T' or ACTIV='00W')
update AFAGESTCOM.ASTOFIP1 set STGES='PK' where DEPOT='EXP' and STGES<>'PK'

-- affecte des gestionnaires de stock par défaut pour les articles sans gestionnaire en fonction de l'activité article AFL
update AFAGESTCOM.ASTOFIP1 set STGES='VLP'  where STGES='' and DEPOT='AFL' and NOART in (select NOART from AFAGESTCOM.AARTICP1 where ACTIV='00D' or ACTIV='00E' or ACTIV='00H' or ACTIV='00J' or ACTIV='00K' or ACTIV='00W' or ACTIV='00X')
update AFAGESTCOM.ASTOFIP1 set STGES='JMLB' where STGES='' and DEPOT='AFL' and NOART in (select NOART from AFAGESTCOM.AARTICP1 where ACTIV='00A' or ACTIV='00C' or ACTIV='00F' or ACTIV='00L' or ACTIV='00M')
update AFAGESTCOM.ASTOFIP1 set STGES='GL'   where STGES='' and DEPOT='AFL' and NOART in (select NOART from AFAGESTCOM.AARTICP1 where ACTIV='00B' or ACTIV='00G' or ACTIV='00N' or ACTIV='00P')