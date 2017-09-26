<?php
// Définitions de constantes d'affichage
define("ELEMENT_HEIGHT",50);
define("ELEMENT_WIDTH",100);
define("AREA_GAP",30);
define("CHAR_WIDTH",9);
define("CHAR_HEIGHT",11);
// ************
// Début de l'image
// ************
function displayErrorAndDie($error){
	die('<text x="1" y="50" class="error_text">'.$error.'</text>');
}
?><?xml version="1.0" encoding="UTF-8" standalone="no"?>
<svg width="100%" height="100%">
<style><?php
require("style.css");
?></style>
<defs>
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
