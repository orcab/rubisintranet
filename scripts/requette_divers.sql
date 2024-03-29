-- passage des bon du jour comptoir en EMP
update AFAGESTCOM.AENTBOP1 set TYVTE='EMP' where TYVTE<>'EMP' and (CDCAM='DIS' or CDCAM='CPT') and (DAYS(DATE(NOW())) - DAYS(DATE(DSEMS || DSEMA || '-' || DSEMM || '-' || DSEMJ))) = 0

-- passage des bon du jour livr�s en LIV
update AFAGESTCOM.AENTBOP1 set TYVTE='LIV' where TYVTE<>'LIV' and not (CDCAM='DIS' or CDCAM='CPT') and (DAYS(DATE(NOW())) - DAYS(DATE(DSEMS || DSEMA || '-' || DSEMM || '-' || DSEMJ))) = 0

-- Suspension des codes expo tous les soirs
update AFAGESTCOM.AARTICP1 set ETARE='S' where NOART like '15%' and ETARE='' -- suspension des code expo (fiche article)
update AFAGESTCOM.ASTOFIP1 set STSTS='S' where NOART like '15%' and STSTS='' -- suspension des code expo (fiche stock)
update AFAGESTCOM.AARFOUP1 set ETAFE='S' where NOART like '15%' and ETAFE='' -- suspension des code expo (fiche ref fournisseur)

-- supprime la pr�co sur les autre d�pot que les principaux
update AFAGESTCOM.ASTOFIP1 set STO10='N' where STO10<>'N' and (DEPOT='AFZ' or DEPOT='9FA' or DEPOT='EXP') -- fiche de stock

-- passe les servi sur stock � "non" sur les d�pot autre que principaux
update AFAGESTCOM.ASTOFIP1 set STSER='NON' where STSER='OUI' and (DEPOT='AFZ' or DEPOT='9FA' or DEPOT='EXP') -- fiche de stock

-- passe les produits CAB56 et BERNER � non servis sur stock
update AFAGESTCOM.ASTOFIP1 set STSER='NON' where NOART in (select NOART from AFAGESTCOM.AARTICP1 where ACTIV='00R' or ACTIV='00T') and STSER='OUI'
update AFAGESTCOM.AARTICP1 set SERST='NON' where (ACTIV='00R' or ACTIV='00T') and SERST='OUI'

-- Fournisseur unique pour le d�pot de Lorient : CESAFA
--update AFAGESTCOM.ASTOFIP1 set STFOU='CESAFA' where DEPOT='AFL' -- fiche de stock

-- Passe les kit en pr�co � "non"
update AFAGESTCOM.AARTICP1 set ART06='NON' where CDKIT='OUI' and ART06='OUI'  -- fiche article
update AFAGESTCOM.ASTOFIP1 set STO10='N' where NOART IN (select NOART from AFAGESTCOM.AARTICP1 where CDKIT='OUI') and STO10<>'N' -- fiche de stock

-- conditionnement d'achat divisible
--update AFAGESTCOM.AARFOUP1 set ARF03='OUI' where NOART like '02%' -- fiche article-fournisseur

-- passe les achat interdit de blanc a "non" sur la fiche de stock
update AFAGESTCOM.ASTOFIP1 set STO11='N' where STO11='' --fiche de stock

-- passe les produits de Caudan � la formule de r�appro "CES"
--update AFAGESTCOM.ASTOFIP1 set STO21='CES' where DEPOT='AFL' --fiche de stock

-- Pour certain fournisseur l'agence de Cuadan commande en direct
--update AFAGESTCOM.ASTOFIP1 set STFOU='NICOLL' where NOART in (select NOART from AFAGESTCOM.ASTOFIP1 where DEPOT='AFA' and STFOU='NICOLL') and DEPOT='AFL' and STFOU<>'NICOLL'
--update AFAGESTCOM.ASTOFIP1 set STFOU='SCHNEI' where NOART in (select NOART from AFAGESTCOM.ASTOFIP1 where DEPOT='AFA' and STFOU='SCHNEI') and DEPOT='AFL' and STFOU<>'SCHNEI'


-- pour les articles cr�er depuis plus d'un an, on passe la formule de r�appro en DFT (sur AFA et AFL)
update AFAGESTCOM.ASTOFIP1 set STO21='DFT' where (DEPOT='AFA' or DEPOT='AFL') and DAYS(DATE(NOW())) - DAYS(DATE(CONCAT(STCSS,CONCAT(STCAA,CONCAT('-',CONCAT(STCMM,CONCAT('-',STCJJ))))))) > 365

-- pour les articles cr�er depuis moins d'un an, on affecte la formule NVX (sur AFA et AFL)
update AFAGESTCOM.ASTOFIP1 set STO21='NVX' where (DEPOT='AFA' or DEPOT='AFL') and DAYS(DATE(NOW())) - DAYS(DATE(CONCAT(STCSS,CONCAT(STCAA,CONCAT('-',CONCAT(STCMM,CONCAT('-',STCJJ))))))) <= 365

-- pour les articles cr�er depuis plus d'un an en class E ou F, on passe en pr�co � NON
update AFAGESTCOM.ASTOFIP1 set STO10='N' where STO10<>'N' and (STCLA='E' or STCLA='F') and DAYS(DATE(NOW())) - DAYS(DATE(CONCAT(STCSS,CONCAT(STCAA,CONCAT('-',CONCAT(STCMM,CONCAT('-',STCJJ))))))) > 365




-- pour les articles en class entre A et D --> achat autoris�
--update AFAGESTCOM.ASTOFIP1 set STO11='N' where (STCLA='A' or STCLA='B' or STCLA='C' or STCLA='D') and (DEPOT='AFA' or DEPOT='AFL') and STO11<>'N'

-- pour les articles cr�er  depuis plus d'un anet en class inf�rieur a E --> achat interdit
--update AFAGESTCOM.ASTOFIP1 set STO11='O' where not (STCLA='A' or STCLA='B' or STCLA='C' or STCLA='D') and (DEPOT='AFA' or DEPOT='AFL') and DAYS(DATE(NOW())) - DAYS(DATE(CONCAT(STCSS,CONCAT(STCAA,CONCAT('-',CONCAT(STCMM,CONCAT('-',STCJJ))))))) > 365 and STO11<>'O'

-- affecte des gestionnaires de stock par d�faut pour les articles sans gestionnaire en fonction de l'activit� article AFA
--update AFAGESTCOM.ASTOFIP1 set STGES='RLF' where STGES='' and DEPOT='AFA' and NOART in (select NOART from AFAGESTCOM.AARTICP1 where ACTIV='00A' or ACTIV='00M')
--update AFAGESTCOM.ASTOFIP1 set STGES='JM' where STGES='' and DEPOT='AFA' and NOART in (select NOART from AFAGESTCOM.AARTICP1 where ACTIV='00B' or ACTIV='00P')
--update AFAGESTCOM.ASTOFIP1 set STGES='BT' where STGES='' and DEPOT='AFA' and NOART in (select NOART from AFAGESTCOM.AARTICP1 where ACTIV='00D' or ACTIV='00E' or ACTIV='00F' or ACTIV='00H' or ACTIV='00X')
--update AFAGESTCOM.ASTOFIP1 set STGES='PB' where STGES='' and DEPOT='AFA' and NOART in (select NOART from AFAGESTCOM.AARTICP1 where ACTIV='00C' or ACTIV='00G' or ACTIV='00J' or ACTIV='00K' or ACTIV='00L' or ACTIV='00N' or ACTIV='00T' or ACTIV='00W')
--update AFAGESTCOM.ASTOFIP1 set STGES='PK' where STGES='' and DEPOT='AFA' and NOART like '15%'
--update AFAGESTCOM.ASTOFIP1 set STGES='PK' where (DEPOT='EXP' and STGES<>'PK')

-- affecte des gestionnaires de stock par d�faut pour les articles sans gestionnaire en fonction de l'activit� article AFL
--update AFAGESTCOM.ASTOFIP1 set STGES='VLP'  where STGES<>'VLP'  and DEPOT='AFL' and NOART in (select NOART from AFAGESTCOM.AARTICP1 where ACTIV='00D' or ACTIV='00E' or ACTIV='00H' or ACTIV='00J' or ACTIV='00K' or ACTIV='00W' or ACTIV='00X')
--update AFAGESTCOM.ASTOFIP1 set STGES='JMLB' where STGES<>'JMLB' and DEPOT='AFL' and NOART in (select NOART from AFAGESTCOM.AARTICP1 where ACTIV='00A' or ACTIV='00C' or ACTIV='00F' or ACTIV='00L' or ACTIV='00M')
--update AFAGESTCOM.ASTOFIP1 set STGES='GL'   where STGES<>'GL'   and DEPOT='AFL' and NOART in (select NOART from AFAGESTCOM.AARTICP1 where ACTIV='00B' or ACTIV='00G' or ACTIV='00N' or ACTIV='00P')

-- passe � suspendu les articles en achat interdit qui n'ont plus de stock et pas de commande en cours
--update AFAGESTCOM.AARTICP1 set ETARE='S' where NOART in (select S.NOART from AFAGESTCOM.ASTOFIP1 FS left join AFAGESTCOM.AARTICP1 A on A.NOART=FS.NOART left join AFAGESTCOM.ASTOCKP1 S on S.NOART=FS.NOART and S.DEPOT=FS.DEPOT where FS.STO11='O' and FS.DEPOT='AFA' and A.ETARE='' and S.QTATC='0' and S.QTAFO='0' and S.QTINV='0')