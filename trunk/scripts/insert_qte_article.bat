insert_qte_article.exe >> insert_qte_article.log
rem Insertion des résultats dans la base MYSQL local
c:
c:\easyphp\mysql\bin\mysql --user=mcs --password=mcs -D MCS < qte_article.sql
