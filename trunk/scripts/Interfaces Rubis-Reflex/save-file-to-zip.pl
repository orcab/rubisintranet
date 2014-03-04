######################################## CREATION DU FICHIER DE SAUVEGARDE ###########################################################
printf "%s Create save file\n",get_time();
mkpath(dirname(OUTPUT_FILENAME).'/sauvegarde') if !-d dirname(OUTPUT_FILENAME).'/sauvegarde' ;

use Archive::Zip qw( :ERROR_CODES :CONSTANTS );
my 	$zip = Archive::Zip->new();
my 	$file_member = $zip->addFile( OUTPUT_FILENAME );
  	$file_member->desiredCompressionLevel( COMPRESSION_LEVEL_BEST_COMPRESSION );

warn "Impossible de creer la sauvegarde compressee"
	unless ( $zip->writeToFileNamed( dirname(OUTPUT_FILENAME).'/sauvegarde/'.strftime("%Y-%m-%d %Hh%Mm%Ss ", localtime).basename(OUTPUT_FILENAME).'.zip') == AZ_OK );

unlink(OUTPUT_FILENAME) or warn "Impossible de supprimer le fichier original ($!)";

1;
