perl insert_prix_article.pl >> insert_prix_article.log
rem Insertion des résultats dans la base MYSQL local
c:
c:\easyphp\mysql\bin\mysql --user=mcs --password=mcs -D MCS < prix_article.sql
