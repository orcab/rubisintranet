<?php
require('../inc/fpdf/fpdf.php');
include 'connexion.php';

class PDF extends FPDF
{

	//En-tête
	function Header()
	{
		//Titre du document
		 $this->Image('images/PDF/informations.png',70,10,70);
		//Logo MCS de gauche
		 $this->Image('images/PDF/mcs_pdf.png',0,0,40);
		//Logo Artipole de droite
		 $this->Image('images/PDF/artipole_pdf.png',170,0,40);
	}
	
	//Pied de page
	function Footer()
	{
		//Positionnement à 1,5 cm du bas
		$this->SetY(-15);
		//Police Arial italique 8
		$this->SetFont('Arial','I',8);
		//Texte du bas de la page
		$this->Cell(0,0,'9, rue Ampère - 56890 PLESCOP - Tél. 02 97 69 00 69 - Fax 02 97 69 03 23',0,2,'C');
		$this->Ln(4);//Saut de ligne
		$this->Cell(0,0,'SA coopérative artisanale à Capital variable - Siret : 442 986 360 00026 - APE 515J - RCS VANNES B 442 986 360 - N° TVA : FR 19 442 986 360',0,1,'C');
	}  

    var $widths;
    var $aligns;

    function SetWidths($w)
    {    //Tableau des largeurs de colonnes
        $this->widths=$w;
    }

    function SetAligns($a)
    {    //Tableau des alignements de colonnes
        $this->aligns=$a;
    }

    function Row($param)
    {
        $data = array();
        for($i=0 ; $i<sizeof($param) ; $i++)
            $data[] = isset($param[$i]['text']) ? $param[$i]['text'] : '';

        //Calcule la hauteur de la ligne
        $nb=0;
        for($i=0;$i<count($data);$i++)
            $nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
        $h=5*$nb;

        //Effectue un saut de page si nécessaire
        $this->CheckPageBreak($h);
        //Dessine les cellules
        for($i=0;$i<count($data);$i++) {
            $a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L'; // gere l'align
            $this->SetFont(isset($param[$i]['font-family'])?$param[$i]['font-family']:'',isset($param[$i]['font-style'])?$param[$i]['font-style']:'',isset($param[$i]['font-size'])?$param[$i]['font-size']:'');
            if (isset($param[$i]['font-color']))    $this->SetTextColor($param[$i]['font-color'][0],$param[$i]['font-color'][1],$param[$i]['font-color'][2]);
            if (isset($param[$i]['text-align']))    $a = $param[$i]['text-align'];
           
            $w=$this->widths[$i];
           
            //Sauve la position courante
            $x=$this->GetX();
            $y=$this->GetY();
            //Dessine le cadre
            $this->Rect($x,$y,$w,$h);
            //Imprime le texte
            $this->MultiCell($w,5,$data[$i],0,$a,0);
            //Repositionne à droite
            $this->SetXY($x+$w,$y);
        }
        //Va à la ligne
        $this->Ln($h);
    }

    function CheckPageBreak($h)
    {
        //Si la hauteur h provoque un débordement, saut de page manuel
        if($this->GetY() + $h + 8 > $this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    function NbLines($w,$txt)
    {
        //Calcule le nombre de lignes qu'occupe un MultiCell de largeur w
        $cw=&$this->CurrentFont['cw'];
        if($w==0) $w=$this->w-$this->rMargin-$this->x;
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n") $nb--;
        $sep=-1; $i=0; $j=0; $l=0; $nl=1;
        while($i<$nb)
        {
            $c=$s[$i];
            if($c=="\n") {
                $i++; $sep=-1; $j=$i; $l=0; $nl++;
                continue;
            }
            if($c==' ') $sep=$i;
            $l+=$cw[$c];
            if($l>$wmax) {
                if($sep==-1) {
                    if($i==$j) $i++;
                } else $i=$sep+1;
                $sep=-1; $j=$i; $l=0; $nl++;
            }
            else $i++;
        }
        return $nl;
    }
}	

//Decode le tableau de parametre en un tableau de valeur
$tab = json_decode(stripslashes($_POST['myJson']), true);

//Tableau pour la condition sql
$condition =  array();

//echo "test : ".print_r($tab);

//Boucle créant un tableau de valeur pour la condition sql
foreach ($tab as $num_adh) {
	array_push($condition," artisan.numero='$num_adh' ");
}

//explose le tableau en une chaine string séparé par des "or"
$artisan_sql = implode(" or ", $condition);

//Nouveau document PDF
$pdf=new PDF();

//Connexion à la BDD
connexion();

//Défini la police de base
$pdf->SetFont('Arial','',16);

//Marge pour descendre le tableau
$pdf->SetMargins(5,50,5);

//Ajoute une page
$pdf->AddPage();

//Taille des colonnes du tableau
$pdf->SetWidths(array(7, 50, 38, 45, 50));
$pdf->SetFillColor(255,0,0);
$pdf->SetDrawColor(0,0,0);
$pdf->SetLineWidth(.1);

//Requete SQL
$sql = <<<EOT
SELECT 	artisan.activite,artisan.nom,artisan.tel1,artisan.tel3,artisan.adr1,artisan.ville,artisan.cp,artisan.email,website
FROM 	artisan LEFT JOIN infoexpo
			ON artisan.numero = infoexpo.numero
WHERE	$artisan_sql
EOT;

$res = mysql_query($sql) or die("Erreur dans la requette SQL (".mysql_error().")") ;

//Boucle pour remplir le tableau d'adhérents
while ($row = mysql_fetch_array($res)) {
	$u=$pdf->GetX();
	$v=$pdf->GetY();
	// nettoyage de valeur, enlève les espaces avant et après la chaine de caractère
	$row['nom'] 	= 	trim($row['nom']); 
	$row['tel1'] 	=	trim($row['tel1']);
	$row['tel1'] 	= 	trim($row['tel1']);
	$row['tel3'] 	= 	trim($row['tel3']);
	$row['adr1'] 	= 	trim($row['adr1']);
	$row['ville'] 	= 	trim($row['ville']);
	$row['cp'] 		= 	trim($row['cp']);
	$row['email'] 	= 	trim($row['email']);
	$row['website'] = 	trim($row['website']);
	
	//Tableau des infos d'adhérent
	$pdf->Row(
			array( //   font-family , font-weight, font-size, font-color, text-align
				
				//Colonne pour afficher le marker de l'adhérent
				array(($row['activite']==1 ? $pdf->Image('images/PDF/plombier-mini.png',$u,$v) : "")|| //Test de l'activité pour choisir le marker correspondant à afficher
				    ($row['activite']==2 ? $pdf->Image('images/PDF/electricien-mini.png',$u,$v) : "")||
					($row['activite']==3 ? $pdf->Image('images/PDF/both-mini.png',$u,$v) : ""),
					'font-style' => '', 'text-align' => 'L', 'font-size' => 10 ),
				
				//Colonne pour afficher le nom de l'adhérent				
				array('text' => "$row[nom]"  ,
					'font-style' => 'B', 	'text-align' => 'C', 'font-size' => 10),
				
				//Colonne pour afficher le tel et le fax de l'adhérent				
				array('text' => ($row['tel1'] ? "N° tel : $row[tel1]" : "").	//Condition si le champ est rempli
					($row['tel3'] ? "\nFax :    $row[tel3]" : ""),				//Condition si le champ est rempli
					'font-style' => '',  'text-align' => 'L', 'font-size' => 10 ),
				
				//Colonne pour afficher l'adresse le cp et la ville			
				array('text' => "$row[adr1]\n$row[cp] $row[ville]" ,
					'font-style' => '',   'text-align' => 'L', 'font-size' => 10 ),
				
				//Colonne pour afficher webiste et le mail de l'adhérent
				array('text' => ($row['email'] ? "E-m@il : $row[email]" : ""). 		//Condition si le champ est rempli
					($row['website'] ? "\nSite internet : $row[website]" : "") ,	//Condition si le champ est rempli
					'font-style' => '',   'text-align' => 'L', 'font-size' => 10 )								
			)
	);
}//Fin de la boucle while
$output = 'output'.uniqid().'.pdf' ;
$pdf->Output($output, 'F');

if (file_exists(FOXIT_PATH)) {
	//$message = base64_encode('"'.FOXIT_PATH.'" -t '.$output.' '.PRINTER);
	system('"'.FOXIT_PATH.'" -t '.$output.' '.PRINTER);
} else {
	$message = base64_encode("Impossible de trouver Foxit Reader");
}
unlink($output);
$message = base64_encode("Impression terminée, vous pouvez la récupérer à l'accueil.");

echo <<<EOT
{"message":"$message"}
EOT;

?>