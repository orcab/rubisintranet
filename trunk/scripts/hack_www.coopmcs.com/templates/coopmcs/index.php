<?php
defined('_JEXEC') or die;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction ?>">
<head>
<jdoc:include type="head" />

<script src="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/include/AC_RunActiveContent.js" type="text/javascript"></script>

<link type="text/css" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/include/jquery.jscrollpane/css/jquery.jscrollpane.css" rel="stylesheet" media="all" />
<script type="text/javascript" src="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/include/jquery.js"></script>
<script type="text/javascript" src="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/include/jquery.jscrollpane/js/jquery.mousewheel.js"></script>
<script type="text/javascript" src="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/include/jquery.jscrollpane/js/jquery.jscrollpane.min.js"></script>

<script type="text/javascript">
	$(function(){
		$('.scroll-pane').jScrollPane({showArrows: true});
	});
<!--
function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}
//-->
</script>

<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/system.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/general.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/css/template.css" type="text/css" />

</head>

<body>
	<div class="main" align="center">
		<div class="principale">
			<div class="conteneur">
				<div class="bandeau">
					<div id="texte">
						<div class="logos"><img src="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/images/logos.png"></div>
						<div class="outils">
							<jdoc:include type="modules" name="bandeau-outils" />
						</div>   
						<div class="menuprincipal">
							<jdoc:include type="modules" name="bandeau-menu" />
						</div>
					</div>
					<div id="flash"></div>
				</div>
				<div class="ariane"><jdoc:include type="modules" name="fil-ariane" /></div>
				<img src="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/images/contenu-haut.png">
				<div class="contenu">
					<?php
					
					if(JRequest::getCmd('view', '') == 'featured'){
					?>
					<div class="gauche">
						<div class="texte-acc">
							<jdoc:include type="modules" name="encart-presentation" />
						</div>
						<div class="video">
							<jdoc:include type="modules" name="encart-video" />
						</div>
					</div>
					<div class="bloc-actu-acc">
						<jdoc:include type="modules" name="produit" />
					</div>
					<?php
					}else{
					?>
					<div class="gauche">
						<jdoc:include type="message" />
						<jdoc:include type="component" />
						<div class="bas-component">
							<img src="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/images/fiche-bas.gif">
						</div>
					</div>
					<?php
					}					
					?>
					<div class="droite">
						<div class="espace">
							<div class="espace-tit">Espace adhérents</div>
							<div class="espace-bloc">
								<jdoc:include type="modules" name="compte"/>
							</div>
							<jdoc:include type="modules" name="menu-enregistre" />
						</div>
						<div class="zoom">Zoom sur</div>
						<jdoc:include type="message" />
						<jdoc:include type="modules" name="zoom-sur"/>
					</div>
				</div>
				<img src="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/images/contenu-bas.png">
			</div>
		</div>
	</div>
	<div class="main2" align="center">
		<div class="principale">
			<div class="bas">
				<div class="adresse">
					<jdoc:include type="modules" name="pied-adresse" />
				</div>
				<div style="float:right">
					<div class="option2">Réalisation :</div>
					<div class="juliana"><a href="http://www.juliana.fr" target="_blank"><img border="0" src="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/images/juliana.gif"></a></div>
				</div>
				<jdoc:include type="modules" name="pied-menu" />
			</div>
		</div>
	</div>
</body>
</html>
