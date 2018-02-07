<?php
// Définitions de constantes d'affichage
define("ELEMENT_HEIGHT",50);
define("ELEMENT_WIDTH",100);
define("AREA_GAP",30);
define("ELEMENT_GAP",80);
define("ELEMENT_CHAR_WIDTH",7);
define("LINK_CHAR_WIDTH",8);
define("AREA_CHAR_WIDTH",10);
define("CHAR_HEIGHT",11);
//header('Content-Type: image/svg+xml'); //ne fonctionne pas car le type mime n'est pas reconnu
//header('Content-Type: image/svg+xml'); //ne fonctionne pas car le type mime n'est pas reconnu
// ************
// Début de l'image
// ************
function displayErrorAndDie($error){
	die('<text x="1" y="50" class="error_text">'.$error.'</text></svg>');
}
?><?xml version="1.0" encoding="UTF-8" standalone="no"?>
<?php if (isset($_REQUEST["ashtml"])){?>
<html>
<body style="margin:0px 0px 0px 0px">
<?php }?>
<svg viewBox="0 0 10000 10000" preserveAspectRatio="xMaxYMid">
<style><?php
require("style.css");
?></style>
<defs>
	<filter id="texture" x="0%" y="0%" width="100%" height="100%">
	    <!-- COLORS -->
        <feFlood flood-color="#582D1B" result="COLOR-red" />
        <!-- COLORS END -->
        <!-- FRACTAL TEXTURE -->
        <feTurbulence baseFrequency=".05,.004" top="-50%" type="fractalNoise" numOctaves="4" seed="0" result="FRACTAL-TEXTURE_10" />
        <feColorMatrix type="matrix" values="0 0 0 0 0,
          0 0 0 0 0,
          0 0 0 0 0,
          0 0 0 -1.2 1.1" in="FRACTAL-TEXTURE_10" result="FRACTAL-TEXTURE_20" />
        <!-- FRACTAL TEXTURE END -->
        <!-- STROKE -->
        <feMorphology operator="dilate" radius="4" in="SourceAlpha" result="STROKE_10" />
        <!-- STROKE END -->
        <!-- EXTRUDED BEVEL -->
         <feConvolveMatrix order="8,8" divisor="1"
          kernelMatrix="1 0 0 0 0 0 0 0 0 1 0 0 0 0 0 0 0 0 1 0 0 0 0 0 0 0 0 1 0 0 0 0 0 0 0 0 1 0 0 0 0 0 0 0 0 1 0 0 0 0 0 0 0 0 1 0 0 0 0 0 0 0 0 1" in="STROKE_10" result="BEVEL_20" />
          <feOffset dx="4" dy="4" in="BEVEL_20" result="BEVEL_25"/>
          <feComposite operator="out" in="BEVEL_25" in2="STROKE_10" result="BEVEL_30"/>
          <feComposite in="COLOR-red" in2="BEVEL_30" operator="in" result="BEVEL_40" />
          <feMerge result="BEVEL_50">
            <feMergeNode in="BEVEL_40" />
            <feMergeNode in="SourceGraphic" />
          </feMerge>
         <!-- EXTRUDED BEVEL END -->
        <feComposite in2 =  "FRACTAL-TEXTURE_20" in="BEVEL_50" operator="in"/>
    </filter>
    <filter id="shadow" x="0" y="0" width="200%" height="200%">
      <feOffset result="offOut" in="SourceAlpha" dx="15" dy="15" />
      <feGaussianBlur result="blurOut" in="offOut" stdDeviation="5" />
      <feBlend in="SourceGraphic" in2="blurOut" mode="normal" />
    </filter>
    <filter id="shadowOrig" x="0" y="0" width="200%" height="200%">
      <feOffset result="offOut" in="SourceAlpha" dx="20" dy="20" />
      <feGaussianBlur result="blurOut" in="offOut" stdDeviation="10" />
      <feBlend in="SourceGraphic" in2="blurOut" mode="normal" />
    </filter>
<?php
require("objects.svg");
?>
</defs>
