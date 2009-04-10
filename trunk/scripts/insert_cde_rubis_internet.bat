@echo off
echo Construction de la nouvelle base SQLITE cde_rubis
insert_cde_rubis_internet >> insert_cde_rubis_internet.log

rem echo Envoi de cette base sur le site web
rem ftp -s:insert_cde_rubis_internet.cmd ftp.coopmcs.com
