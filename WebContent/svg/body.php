<?php
// Fonctions liées à l'affichage
function computeSize($area){
	// on calcule la taille de chaque sous zone
	$x = $area->x + AREA_GAP;
	$y = $area->y + AREA_GAP;
	$area->width = 0;
	$area->height = 0;
	// Positionner les sous zones
	if (count($area->subareas) != 0){
		foreach ($area->subareas as $subarea){
			if (!$subarea->needed) {
				continue;
			}
			$subarea->x = $x;
			$subarea->y = $y + 10;
			computeSize($subarea);
			if ($area->display == "horizontal"){
				$x = $x + $subarea->width+AREA_GAP;
				$area->height  = max($area->height,$subarea->height);
				$area->width += $subarea->width + AREA_GAP;
			} else if ($area->display == "vertical"){
				$y = $y + $subarea->height+AREA_GAP;
				$area->width  = max($area->width,$subarea->width);
				$area->height += $subarea->height + AREA_GAP;
			} else {
			}
		}
		// Egaliser la taille des sous zones en largeur ou hauteur selon l'alignement horizontal ou vertical
		foreach ($area->subareas as $subarea){
			if ($area->display == "horizontal"){
				$subarea->height = $area->height;
			} else if ($area->display == "vertical"){
				$subarea->width = $area->width;
			}
		}
		// Déterminer exactement la largeur et la hauteur de la zone
		if ($area->display == "horizontal"){
			$area->width += AREA_GAP;
			$area->height += 2 * AREA_GAP + 10;
		} else if ($area->display == "vertical"){
			$area->width += 2*AREA_GAP;
			$area->height += AREA_GAP + 10;
		}
		// Les élements contenus ne seront pas affichés, on l'indique dans les logs
		foreach ($area->elements as $element){
			error_log($element->name." will not be displayed because this area contains sub areas");
			$element->x = 0;
			$element->y = 0;
			$element->width = 0;
			$element->height = 0;
		}
    // si la zone contient des éléments
	} else if (count($area->elements) != 0){ 
		// Positionner la taille de chaque element (par défaut ELEMENT_WIDTH et ELEMENT_HEIGHT)
		foreach ($area->elements as $element){
			$class = $element->class;
			if (!isset($element->width)){
				if (substr($class, 0, 5) === "rect_"){ // rect_width_height ex : rect_100_100
					$sizes = explode("_",$class);
					$element->width = intval($sizes[1]);
					$element->height = intval($sizes[2]);
				} else {
					$element->width = ELEMENT_WIDTH;
					$element->height = ELEMENT_HEIGHT;
				}
			}
		}
		// Déterminer s'il faut répartir les éléments par rapport aux liens qu'ils ont entre eux
		$atleastonelinkfound = false;
		foreach ($area->elements as $element){
			if (isset($element->links) && (count($element->links) > 0)){
				$atleastonelinkfound = true; // TODO que fait-on s'il n'y a pas que des liens depuis l'exterieur ?
				break;
			}
		}
		$first = null;
		// Au moins un lien a été trouvé, on va donc chercher le premier à disposer
		if ($atleastonelinkfound) {
			// Ordonner les élements pour prendre en compte les liens s'il y en a
			// Cherchons le premier (cad celui qui n'a aucun element interieur à la zone qui pointe vers lui)
			foreach ($area->elements as $element){
				$canbefirst = true;
				foreach ($area->elements as $otherelement){
					if ($otherelement == $element){
						continue;
					}
					if (!isset($otherelement->links)){
						continue;
					}
					foreach($otherelement->links as $link){
						if ($link->to == $element){
							$canbefirst = false;
							break;
						}
					}
					// Au moins un lien vers lui trouvé, on laisse tomber le parcours des autres éléments
					if (!$canbefirst) {
						break;
					}
				}
				// Aucun lien trouvé vers lui, c'est le bon candidat
				if ($canbefirst){
					$first = $element;
					break;
				}
			}
		}
		// On a trouvé un premier, on va ajouter les autres elements à partir de lui
		if ($first != null){
			$orderedElements[] = $first;
			$stack = array();
			$elementsInArea = array(); // lister tous les éléments qui sont dans la zone pour pouvoir identifier ceux qu'il ne faudra pas positionner s'il y a des liens externes à la zone
			// On indique que chaque element doit être calculé
			foreach ($area->elements as $element){
				$element->allreadyComputed = false;
				$elementsInArea[$element->id] = $element;
			}
			$maxy = 0;
			$maxx = 0;
			// J'empile l'étape start dans la pile
			$waitstack = array();
			$stack = array();
			$set = new stdClass();
			$set->element = $first;
			$set->y = $y;
			$stack[] = $set;
			$antiInfiniteLoop = 30;
			while (count($stack) > 0){
				$antiInfiniteLoop--;
				if ($antiInfiniteLoop == 0){	// Indispensable pour le debug
					displayErrorAndDie("Boucle infinie détectée");
				}
				// voir s'il ne faut pas basculer des éléments de la pile dans la pile d'attente
				$newStack = array();
				foreach ($stack as $set){
					$waited = false;
					foreach($area->elements as $element){
						if ($element->allreadyComputed) {
							continue;
						}
						if (isset($element->links)){
							foreach($element->links as $link){
								if ($link->to == $set->element){
									$waitstack[] = $set;
									$waited = true;
									break 2; // sortir de la boucle sur les éléments
								}
							}
						}
					}
					if ($waited == false){
						$newStack[] = $set;
					}
				}
				$stack = $newStack;
				// si écarter les éléments en attente abouttit à tout vider, tant pis, on rebascule un des éléments
				if (count($stack) == 0){
					$set = $waitstack[0];
					unset($waitstack[0]);
					// indiquer à tous ceux qui pointent dessus et qui ne sont pas calculés qu'il s'agit d'un lien arrière
					foreach($area->elements as $element){
						if ($element->allreadyComputed) {
							continue;
						}
						/*TODO : voir comment faire pour traiter ça dans body.php
						foreach($step->links as $link){
							if ($link->to == $set->step){
								$link->backlink = true;
							}
						}*/
					}
					$stack[] = $set;
				}
				$newStack = array();
				// Pour chaque element dans la pile
				foreach ($stack as $set){
					$element = $set->element;
					if ($element->allreadyComputed) {
						continue;
					}
					$y = max($y,$set->y);
					$element->x = $x;
					$element->y = $y;
					$element->allreadyComputed = true;
					foreach ($element->links as $link){
						$to_element = $link->to;
						if ((!$to_element->allreadyComputed) && (isset($elementsInArea[$to_element->id]))){
							$newSet = new stdClass();
							$newSet->element = $to_element;
							$newSet->y = $y;
							$newStack[] = $newSet;
						}
					}
					$y += AREA_GAP + $element->height + 50;
					$maxy = max($maxy,$y);
				}
				$stack = $newStack;
				$x += AREA_GAP + ELEMENT_WIDTH + 100;
				$y = AREA_GAP + 10;
				// On rebascule les éléments en attente dans la pile
				foreach($waitstack as $set){
					$stack[] = $set;
				}
				$waitstack = array();
			}
			$area->width = $x;
			$area->height = $maxy;
		} else {
			// disposer les composants via une grille
			if ($area->display == "grid"){
				$elementsCount = count($area->elements);
				$nbRow = 1;
				$nbCol = $elementsCount;
				while ($nbCol > ($nbRow + 1)) {
					$nbRow++;
					$nbCol = ceil($elementsCount / $nbRow);
				}
				$row = 0;
				$col = 0;
				foreach ($area->elements as $element){
					$element->x = $area->x + AREA_GAP + $col * ($element->width + ELEMENT_GAP);
					$element->y = $area->y + AREA_GAP + 10 + $row * ($element->height + ELEMENT_GAP);
					if ($row == 0){
						$area->width  += $element->width + ELEMENT_GAP;
						$area->height += $element->height + ELEMENT_GAP;
					}
					$col++;
					if ($col >= $nbCol){
						$col = 0;
						$row++;
					}
				}
				$area->width  += AREA_GAP * 2;
				$area->height += AREA_GAP * 2;
			// Disposer les éléments horizontalement
			} else if ($area->display == "horizontal"){
				$x = $area->x + AREA_GAP;
				$y = $area->y + AREA_GAP + 10;
				$maxheight = 0;
				foreach ($area->elements as $element){
					$element->x = $x;
					$element->y = $y;
					$x = $x + $element->width + ELEMENT_GAP;
					$area->width += $element->width + ELEMENT_GAP;
					$maxheight = max($maxheight,$element->height);
				}
				$area->width += (AREA_GAP*2);
				$area->height = $maxheight + (AREA_GAP*2) + 10;
			// Disposer les elements verticalement
			} else if ($area->display == "vertical"){
				$x = $area->x + AREA_GAP;
				$y = $area->y + AREA_GAP + 10;
				$maxwidth = 0;
				foreach ($area->elements as $element){
					$element->x = $x;
					$element->y = $y;
					$y = $y + $element->height + ELEMENT_GAP;
					$area->height += $element->height + ELEMENT_GAP;
					$maxwidth = max($maxwidth,$element->width);
				}
				$area->height += AREA_GAP + 20;
				$area->width = $maxwidth + (AREA_GAP*2);
			}
		}
	} else { // Ni elements ni sous zones
		$area->height = (AREA_GAP*2) + 80;
		$area->width  = (AREA_GAP*2) + 80;
	}
}
// Afficher une zone
function displayArea($level,$area){
	// ** Afficher la zone en elle meme **
	$class = 'area'.$level;
	if (count($area->elements) > 0){
		$class = "areaWithElements";
	} else if (count($area->subareas) == 0){
		$class = "areaEmpty";
	}
	echo '<rect class="'.$class.'" x="'.($area->x).'" y="'.($area->y).'" width="'.$area->width.'" height="'.$area->height.'" rx="10" ry="10"/>';
	$label = $area->code;
	if (($label == "") || ($label == null)){
		$label = $area->name;
	}
	// ** Afficher le label de la zone **
	$label = _truncateText($label,$area->width - 15,AREA_CHAR_WIDTH);
	echo '<text x="'.($area->x+15).'" y="'.($area->y+25).'" class="'.$class.'_title">'.$label.'</text>';
	// ** Affichage des sous zones récursivement **
	$subx = $area->x;
	$suby = $area->y;
	foreach ($area->subareas as $subarea){
		if (!$subarea->needed) {
			continue;
		}
		displayArea($level+1,$subarea);
	}
	// ** Affichage des éléments **
	foreach ($area->elements as $element){
		$class = $element->class;
		if (substr($class, 0, 5) === "rect_"){
			_drawElementAsRect($element);
		} else {
			_drawElement($element);
		}
	}
}
/** Afficher tous les liens récursivement */
function displayLinks($areas){
	foreach($areas as $area){
		// ** Affichage des liens **
		foreach ($area->elements as $from){
			if (isset($from->links)){
				foreach ($from->links as $link){
				//if (isset($link->backlink)){
					// TODO outch comment faire ??	
				//} else {
					_drawLink($from,$link->to,$link->label);
				//}
				}
			}
		}
		displayLinks($area->subareas);
	}
}
/**
 * Afficher un élément
 */
function _drawElement($element){
	$style = "";
	echo '<use id="element_'.$element->id.'" href="#'.$element->class.'" x="'.($element->x).'" y="'.($element->y).'" onclick=" window.parent.svgElementClicked(\''.$element->type.'\',\''.$element->id.'\')" style="'.$style.'"><title>'.$element->name.'</title></use>';
	$textwidth = _textWidth($element->name,ELEMENT_CHAR_WIDTH);
	$x = $element->x;
	$maxsize = $element->width + 30;
	if ($textwidth > $maxsize){
		$nbcharmax = ceil($maxsize / ELEMENT_CHAR_WIDTH);
		$text = substr($element->name,0,$nbcharmax);
		$x = $element->x + ceil(($element->width - $maxsize) / 2);
	} else {
		$x = $element->x + ceil(($element->width - $textwidth) / 2);
		$text = $element->name;
	}
	echo '<text x="'.$x.'" y="'.($element->y+$element->height+20).'" class="element_label"><title>'.$element->name.'</title>'.$text.'</text>';
}
/**
 * Afficher un élément sous forme d'un rectangle
 */
function _drawElementAsRect($element){
	echo '<rect x="'.$element->x.'" y="'.$element->y.'" width="'.$element->width.'" height="'.$element->height.'" rx="5" ry="5" class="rect_www_hhh" filter="url(#shadow)" onclick="window.parent.svgElementClicked(\''.$element->type.'\',\''.$element->id.'\')"/>';
	$lines = _splitTextInLines($element->name,ELEMENT_CHAR_WIDTH,CHAR_HEIGHT,$element->width,$element->height);
	$verticalgap = round(($element->height - (count($lines) * CHAR_HEIGHT)) / (count($lines) + 1));
	$y = $element->y + CHAR_HEIGHT + $verticalgap;
	$x = $element->x+2;
	foreach ($lines as $line){
		$textwidth = strlen($line) * ELEMENT_CHAR_WIDTH;
		$x = $element->x + round(($element->width - $textwidth) / 2);
		echo '<text x="'.$x.'" y="'.($y).'" class="element_label">'.$line.'</text>';
		$y+=$verticalgap+ CHAR_HEIGHT;
	}
}
/**
 * Afficher un lien
 */
function _drawLink($from,$to,$label){
	$x1 = 0;
	$y1 = 0;
	$x2 = 0;
	$y2 = 0;
	$sens = "";
	// est-ce que to est à droite de from ?
	if ($from->x < $to->x){
		$x1 = $from->x + $from->width + 2;
		$x2 = $to->x - 2;
		$sens = "droite";
	// est-ce qu'ils sont au même niveau ?
	} else if ($from->x == $to->x){
		$x1 = $from->x + ceil($from->width / 2);
		$x2 = $to->x + ceil($to->width / 2);
	// to est à gauche de from
	} else {
		$x1 = $from->x - 2;
		$x2 = $to->x + $to->width + 2;
		$sens = "gauche";
	}
	// est-ce que to est en dessous de from ?
	if ($from->y < $to->y){
		$y1 = $from->y + $from->height + 2;
		$y2 = $to->y - 2;
		$sens = "bas".$sens;
	// est-ce que to est au même niveau que from ?
	} else if ($from->y == $to->y) {
		$y1 = $from->y + ceil ($from->height / 2);
		$y2 = $to->y + ceil ($to->height / 2);
	// est-ce que to est au dessus de from ?
	} else {
		$y1 = $from->y - 2;
		$y2 = $to->y + $to->height + 2;
		$sens = "haut".$sens;
	}
	echo '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" style="stroke:black"/>';
	// calcul du centre de la ligne
	$x = ceil(($x1+$x2) / 2);
	$y = ceil(($y1+$y2) / 2) - 3;
	// calcul de la taille du texte
	$textWidth = _textWidth($label,LINK_CHAR_WIDTH);
	// Centrer le texte
	$x = $x-ceil($textWidth/2);
	if ($label != ""){
		echo '<text x="'.$x.'" y="'.$y.'" style="fill:gray" class="link_label">'.$label.'</text>';
	}
	// ** mettre une flèche **
	if ($sens == "bas"){
		echo '<line x1="'.$x2.'" y1="'.$y2.'" x2="'.($x2 - 4).'" y2="'.($y2 - 8).'" style="stroke:black"/>';
		echo '<line x1="'.$x2.'" y1="'.$y2.'" x2="'.($x2 + 4).'" y2="'.($y2 - 8).'" style="stroke:black"/>';
	} else if ($sens == "haut"){
		echo '<line x1="'.$x2.'" y1="'.$y2.'" x2="'.($x2 - 4).'" y2="'.($y2 + 8).'" style="stroke:black"/>';
		echo '<line x1="'.$x2.'" y1="'.$y2.'" x2="'.($x2 + 4).'" y2="'.($y2 + 8).'" style="stroke:black"/>';
	} else if ($sens == "droite"){
		echo '<line x1="'.$x2.'" y1="'.$y2.'" x2="'.($x2 - 8).'" y2="'.($y2 - 4).'" style="stroke:black"/>';
		echo '<line x1="'.$x2.'" y1="'.$y2.'" x2="'.($x2 - 8).'" y2="'.($y2 + 4).'" style="stroke:black"/>';
	} else if ($sens == "gauche"){
		echo '<line x1="'.$x2.'" y1="'.$y2.'" x2="'.($x2 + 8).'" y2="'.($y2 - 4).'" style="stroke:black"/>';
		echo '<line x1="'.$x2.'" y1="'.$y2.'" x2="'.($x2 + 8).'" y2="'.($y2 + 4).'" style="stroke:black"/>';
	} else if ($sens == "hautgauche"){
		echo '<line x1="'.$x2.'" y1="'.$y2.'" x2="'.($x2).'" y2="'.($y2 + 8).'" style="stroke:black"/>';
		echo '<line x1="'.$x2.'" y1="'.$y2.'" x2="'.($x2 + 8).'" y2="'.($y2).'" style="stroke:black"/>';
	} else if ($sens == "hautdroite"){
		echo '<line x1="'.$x2.'" y1="'.$y2.'" x2="'.($x2).'" y2="'.($y2 + 8).'" style="stroke:black"/>';
		echo '<line x1="'.$x2.'" y1="'.$y2.'" x2="'.($x2 - 8).'" y2="'.($y2).'" style="stroke:black"/>';
	} else if ($sens == "basgauche"){
		echo '<line x1="'.$x2.'" y1="'.$y2.'" x2="'.($x2).'" y2="'.($y2 - 8).'" style="stroke:black"/>';
		echo '<line x1="'.$x2.'" y1="'.$y2.'" x2="'.($x2 + 8).'" y2="'.($y2).'" style="stroke:black"/>';
	} else if ($sens == "basdroite"){
		echo '<line x1="'.$x2.'" y1="'.$y2.'" x2="'.($x2).'" y2="'.($y2 - 8).'" style="stroke:black"/>';
		echo '<line x1="'.$x2.'" y1="'.$y2.'" x2="'.($x2 - 8).'" y2="'.($y2).'" style="stroke:black"/>';
	} else {
		echo '<circle cx="'.$x2.'" cy="'.$y2.'" r="5" style="fill:gray"/>';
	}
}
/**
 * Truncate a text according the maxWidth
 */
function _truncateText($text,$maxWidth,$charWidth){
	$textWidth = strlen($text) * $charWidth;
	if ($textWidth > $maxWidth){
		$nbcharmax = ceil($maxWidth / $charWidth);
		$text = substr($text,0,$nbcharmax);
	}
	return $text;
}
/**
 * Compute a text width
 */
function _textWidth($text,$charWidth){
	return strlen($text) * $charWidth;
}
/**
 * Split a text in lines according the maxWidth and maxHeight
 */
function _splitTextInLines($textToSplit,$charWidth,$charHeight,$maxWidth,$maxHeight){
	$textwidth = _textWidth($textToSplit,$charWidth);
	$lines = array();
	if ($textwidth > ($maxWidth - 5)){
 		$nbcharmax = intval(($maxWidth - 5) / $charWidth);
		$text = $textToSplit;
		while ((strlen($text) * $charWidth) > $maxWidth){
			$pos = $nbcharmax;
			if ($pos >= strlen($text)){
				$pos = strlen($text);
			}
			while (($pos > 0) && ($text[$pos] != " ")){
				$pos--;
			}
			if ($pos == 0){
				$pos = $nbcharmax;
			}
			$subtext = substr($text,0,$pos);
			$lines[] = $subtext;
			$text = substr($text,$pos);
			if ((count($lines) * $charHeight) > $maxHeight) {
				$text = "";
			}
		}
		if (strlen($text) > 0){
			$lines[] = $text;
		}	
	} else {
		$lines[] = $textToSplit;
	}
	return $lines;
}
/**
 * Afficher toutes les zones
 */
function display($areas){
	// Calculer la position et la taille des zones par rapport à leur contenu
	$x = 0;
	foreach ($areas as $area){
		$area->x = $x;
		$area->y = 0;
		computeSize($area);
		$x += $area->width + AREA_GAP;
	}
	if (count($areas) == 1){
		displayArea(0,$areas[0]);
	} else {
		foreach ($areas as $area){
			displayArea(1,$area);
		}
	}
	displayLinks($areas);
}
?>
