<?php
require_once('../inc/fpdf/fpdf.php');
require_once('../inc/qrcode/qrcode.class.php');
include 'connexion.php';

class PDF extends FPDF
{
	//En-tête
	function Header()
	{	//Logo MCS de gauche
		 $this->Image('images/PDF/mcs_pdf.png',0,0,45);
		//Logo Artipole de droite
		 $this->Image('images/PDF/artipole_pdf.png',250,0,45);
	}


	//Pied de page
	function Footer()
	{
		//Positionnement à 1,5 cm du bas
		$this->SetY(-10);
		//Police Arial italique 8
		$this->SetFont('Arial','I',8);
		//Texte du bas de la page
		$this->Cell(0,0,'9, rue Ampère - 56890 PLESCOP - Tél. 02 97 69 00 69 - Fax 02 97 69 03 23',0,2,'C');
		$this->Ln(4);//Saut de ligne
		$this->Cell(0,0,'SA coopérative artisanale à Capital variable - Siret : 442 986 360 00026 - APE 515J - RCS VANNES B 442 986 360 - N° TVA : FR 19 442 986 360',0,1,'C');
	}
	
}

//Nouveau document PDF
$pdf=new PDF();

//Connexion à la BDD
connexion();

//Défini la police de base
$pdf->SetFont('Arial','',10);

//Ajoute une page
$pdf->AddPage('L');

//Requete SQL
$sql = <<<EOT
SELECT 	artisan.numero,artisan.nom,artisan.email,artisan.tel1,artisan.tel3,artisan.adr1,artisan.cp,artisan.ville,artisan.activite,text_desc,photo,website
FROM 	artisan LEFT JOIN infoexpo
			ON artisan.numero = infoexpo.numero
WHERE	artisan.numero= '$_GET[numero_artisan]'
EOT;
$res = mysql_query($sql) or die("Erreur dans la requette SQL (".mysql_error().")") ;

$data = mysql_fetch_array($res);
$pdf->SetFont('Arial','B',26);
$pdf->Cell(0,0,$data['nom'],0,1,'C');

if ($data['activite'] == 1)  {
	$pdf->Ln(11);
	$pdf->Image("images/PDF/plombier-mini.png",110,17);
	$pdf->SetFont('Arial','B',14);
	$pdf->Cell(0,0,'Plombier & Chauffagiste',0,1,'C');
}

if ($data['activite'] == 2) {
	$pdf->Ln(11);
	$pdf->Image("images/PDF/electricien-mini.png",125,17);
	$pdf->SetFont('Arial','B',14);
	$pdf->Cell(0,0,'Electricien',0,1,'C');
}

if ($data['activite'] == 3) {
	$pdf->Ln(11);
	$pdf->Image("images/PDF/both-mini.png",110,17);
	$pdf->SetFont('Arial','B',14);
	$pdf->Cell(0,0,'Plombier & Electricien',0,1,'C');
}
				
$pdf->Ln(10);
$pdf->SetFont('Arial','',12);

//Test si les champs existent et sont remplis pour les afficher si tel est le cas

if  (isset($data['email']) && $data['email']!= " " && $data['email']!= "")  {
	$pdf->Cell(0,0,'e-m@il : '.$data['email'],0,1,'C');
	$pdf->Ln(7);
}

if  (isset($data['tel1']) && $data['tel1']!= " " && $data['tel1']!= "")  {
	$pdf->Cell(0,0,'tel : '.$data['tel1'],0,1,'C');
	$pdf->Ln(7);
}

if  (isset($data['tel3']) && $data['tel3']!= " " && $data['tel3']!= "")  {
	$pdf->Cell(0,0,'fax : '.$data['tel3'],0,1,'C');
	$pdf->Ln(7);
}

if  (isset($data['cp']) && $data['cp']!= " " && $data['cp']!= "" && isset($data['ville']) && $data['ville']!= " " && $data['ville']!= "")  {
	$pdf->Cell(0,0,$data['adr1'],0,1,'C');
	$pdf->Ln(7);
	$pdf->Cell(0,0,$data['cp'].' '.$data['ville'],0,1,'C');
	$pdf->Ln(7);
}

if  (isset($data['website']) && $data['website']!= " " && $data['website']!= "")  {
	$pdf->Cell(0,0,'Site internet : '.$data['website'],0,0,'C');
	$qrcode = new QRcode($data['website'], 'L'); // error level : L, M, Q, H
	$qrcode->displayFPDF($pdf, 297/2 - 5, $pdf->GetY()+2, 10);
}

if  (isset($data['text_desc']) && $data['text_desc']!= " " && $data['text_desc']!= "")  {
	$pdf->Ln(30);
	$pdf->MultiCell(100,5,preg_replace('/<\s*br\/?\s*>/','',$data['text_desc']));  //Multiligne remove <br>
}
$pdf->Ln(4);

//Transforme la chaine pde caractere du champ photo en tableau
$photo = explode(",", $data['photo']);


//Place chaque image sur le PDF		
if  (isset($photo[0]) && $photo[0])  {
	$size = getimagesize("images/photo_adh/$_GET[numero_artisan]/$photo[0]") ;
	if ($size[0] > $size[1]){
		$pdf->Image("images/photo_adh/$_GET[numero_artisan]/$photo[0]",10,30,80);
	} else {
		$pdf->Image("images/photo_adh/$_GET[numero_artisan]/$photo[0]",30,30,0,55);
	}
}

if  (isset($photo[1]) && $photo[1])  {
	$size = getimagesize("images/photo_adh/$_GET[numero_artisan]/$photo[1]") ;
	if ($size[0] > $size[1]){
		$pdf->Image("images/photo_adh/$_GET[numero_artisan]/$photo[1]",10,140,80);
	} else {
		$pdf->Image("images/photo_adh/$_GET[numero_artisan]/$photo[1]",30,140,0,55);
	}
}

if  (isset($photo[2]) && $photo[2])  {
	$size = getimagesize("images/photo_adh/$_GET[numero_artisan]/$photo[2]") ;
	if ($size[0] > $size[1]){
		$pdf->Image("images/photo_adh/$_GET[numero_artisan]/$photo[2]",115,80,80);
	} else {
		$pdf->Image("images/photo_adh/$_GET[numero_artisan]/$photo[2]",130,80,0,55);
	}
}

if  (isset($photo[3]) && $photo[3])  {	
	$size = getimagesize("images/photo_adh/$_GET[numero_artisan]/$photo[3]") ;
	if ($size[0] > $size[1]){
		$pdf->Image("images/photo_adh/$_GET[numero_artisan]/$photo[3]",120,150,70);
	} else {
		$pdf->Image("images/photo_adh/$_GET[numero_artisan]/$photo[3]",130,150,0,55);
	}
}

if  (isset($photo[4]) && $photo[4])  {	
	$size = getimagesize("images/photo_adh/$_GET[numero_artisan]/$photo[4]") ;
	if ($size[0] > $size[1]){
		$pdf->Image("images/photo_adh/$_GET[numero_artisan]/$photo[4]",210,135,80);
	} else {
		$pdf->Image("images/photo_adh/$_GET[numero_artisan]/$photo[4]",225,135,0,55);
	}
}

if  (isset($photo[5]) && $photo[5])  {
	$size = getimagesize("images/photo_adh/$_GET[numero_artisan]/$photo[5]") ;
	if ($size[0] > $size[1]){
		$pdf->Image("images/photo_adh/$_GET[numero_artisan]/$photo[5]",220,50,70);
	} else {
		$pdf->Image("images/photo_adh/$_GET[numero_artisan]/$photo[5]",220,50,0,55);
	}
}
	
$output = 'output_adh'.uniqid().'.pdf' ;
$pdf->Output($output, 'F');

if (file_exists(FOXIT_PATH)) {
	//$message = base64_encode('"'.FOXIT_PATH.'" -t '.$output.' '.PRINTER);
	if (PRINT_PDF)
		system('"'.FOXIT_PATH.'" -t '.$output.' '.PRINTER);
} else {
	$message = base64_encode("Impossible de trouver Foxit Reader");
}

if (PRINT_PDF)
	unlink($output);

$message = base64_encode("Vous pouvez récupérer le documents auprès de nos conseillères.");

echo <<<EOT
{ "message":"$message" }
EOT;

?>