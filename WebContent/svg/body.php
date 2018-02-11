<?php
// Fonctions liées à l'affichage
function computeSize($area){
	// on calcule la taille de chaque sous zone
	$area->width = 0;
	$area->height = 0;
	// Positionner les sous zones
	$hasSubAreasToDraw = false;
	// Il s'agit de vérifier qu'il y aura bien des sous zones à afficher
	if (count($area->subareas) != 0){
	    foreach ($area->subareas as $subarea){
	        if ($subarea->needed) {
	            $hasSubAreasToDraw = true;
	            break;
	        }
	    }
	}
	if ($hasSubAreasToDraw){
		_computeSubAreasCoords($area);
    // si la zone contient des éléments
	} else if (count($area->elements) != 0){
	    _computeElementsCoords($area);
	} else { // Ni elements ni sous zones
		$area->height = (AREA_GAP*2) + 80;
		$area->width  = (AREA_GAP*2) + 80;
	}
}
function _computeSubAreasCoords($area){
    $x = $area->x + AREA_GAP;
    $y = $area->y + AREA_GAP;
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
        if (!$subarea->needed) {
            continue;
        }
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
}
function _computeComposedElementSize($element){
    $x = 20;
    $y = AREA_GAP + CHAR_HEIGHT; // Passer le titre
    $maxWidth = $element->width;
    foreach($element->subElements as $subElement){
        if (!isset($subElement->width)){
            $subElement->width = ELEMENT_WIDTH;
            $subElement->height = ELEMENT_HEIGHT;
        }
        $subElement->x = $x;
        $subElement->y = $y;
        if (isset($subElement->subElements) && (count($subElement->subElements) > 0)){
            _computeComposedElementSize($subElement);
        }
        $newWidth = $subElement->width;
        if ($newWidth > $maxWidth){
            $maxWidth = $newWidth;
        }
        $y += $subElement->height + 10;
    }
    $element->height = $y+20;
    $element->width = $maxWidth+40;
}
function _computeElementsCoords($area){
    $x = $area->x + AREA_GAP;
    $y = $area->y + AREA_GAP + 10;
    // Positionner la taille de chaque element (par défaut ELEMENT_WIDTH et ELEMENT_HEIGHT)
    foreach ($area->elements as $element){
        if (!isset($element->display->class)){
            $class = "rect_100_100";
        } else {
            $class = $element->display->class;
        }
        // Si aucune taille n'est précisée
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
        if (isset($element->subElements) && (count($element->subElements) > 0)){
            _computeComposedElementSize($element);
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
    $stack = array();
    // Au moins un lien a été trouvé, on va donc chercher les premiers à disposer
    if ($atleastonelinkfound) {
        // Ordonner les élements pour prendre en compte les liens s'il y en a
        // Cherchons les premiers (cad ceux qui n'ont aucun element interieur à la zone qui pointe vers eux)
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
            // Aucun lien trouvé vers lui, c'est un bon candidat
            if ($canbefirst){
                $set = new stdClass();
                $set->element = $element;
                $set->y = $y; // TODO voir s'il ne faudrait pas incrémenter $y
                $stack[] = $set;
            }
        }
    }
    // On a trouvé les premiers, on va calculer les autres elements à partir de ceux-là
    if (count($stack) != 0){
        $elementsInArea = array(); // lister tous les éléments qui sont dans la zone pour pouvoir identifier ceux qu'il ne faudra pas positionner s'il y a des liens externes à la zone
        // On indique que chaque element doit être calculé
        foreach ($area->elements as $element){
            $element->allreadyComputed = false;
            $elementsInArea[$element->id] = $element;
        }
        $maxy = 0;
        $maxx = 0;
        $waitstack = array();
        $antiInfiniteLoop = 30;
        while (count($stack) > 0){
            $antiInfiniteLoop--;
            if ($antiInfiniteLoop == 0){	// Indispensable pour le debug
                displayErrorAndDie("Boucle infinie détectée");
            }
            // voir s'il ne faut pas basculer des éléments de la pile dans la pile d'attente pour les afficher plus tard
            $newStack = array();
            foreach ($stack as $set){
                $waited = false;
                foreach($area->elements as $element){
                    if ($element->allreadyComputed) {
                        continue;
                    }
                    if (isset($element->links)){
                        // Est-ce qu'un autre élément pointe sur cet élément ? Si oui, on le met en attente.
                        foreach($element->links as $link){
                            if ($link->to == $set->element){
                                $waitstack[] = $set;
                                $waited = true;
                                break 2; // sortir de la boucle sur les éléments
                            }
                        }
                    }
                }
                // Pas de mise en attente ? On le prend.
                if ($waited == false){
                    $newStack[] = $set;
                }
            }
            $stack = $newStack;
            // si écarter les éléments en attente abouttit à tout vider, tant pis, on rebascule un des éléments
            if (count($stack) == 0){
                $set = $waitstack[0];
                unset($waitstack[0]);
                // TODO indiquer à tous ceux qui pointent dessus et qui ne sont pas calculés qu'il s'agit d'un lien arrière
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
                // On injecte dans la pile tous les elements pointés par l'élément courrant à condition qu'ils soient dans la zone courrante et qu'ils n'aient pas déjà été positionnés
                foreach ($element->links as $link){
                    $to_element = $link->to;
                    if ((!$to_element->allreadyComputed) && (isset($elementsInArea[$to_element->id]))){
                        $newSet = new stdClass();
                        $newSet->element = $to_element;
                        $newSet->y = $y;
                        $newStack[] = $newSet;
                    }
                }
                $y += AREA_GAP + $element->height + 10;// 10 = la hauteur de la légende TODO mettre une constante
                $maxy = max($maxy,$y);
            }
            $stack = $newStack;
            $x += AREA_GAP + ELEMENT_WIDTH + 100; // 100 = la taille du lien TODO mettre une constante
            $y = $area->y + AREA_GAP + 10;
            // On rebascule les éléments en attente dans la pile
            foreach($waitstack as $set){
                $stack[] = $set;
            }
            $waitstack = array();
        }
        $area->width = $x - $area->x;
        $area->height = $maxy - $area->y;
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
	echo "<rect class='".$class."' x='".($area->x)."' y='".($area->y)."' width='".$area->width."' height='".$area->height."' rx='10' ry='10'/>\n";
	$label = $area->name;
	// ** Afficher le label de la zone **
	$label = _truncateText($label,$area->width - 15,AREA_CHAR_WIDTH);
	echo "<text x='".($area->x+15)."' y='".($area->y+25)."' class='".$class."_title'>".$label."</text>\n";
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
	    $class = $element->display->class;
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
			_drawSubLinks($from);
		}
		displayLinks($area->subareas);
	}
}
/** Afficher récursivement les liens des sous éléments */
function _drawSubLinks($element){
    if (!isset($element->subElements)){
        return;
    }
    foreach ($element->subElements as $from){
        if (isset($from->links)){
            foreach ($from->links as $link){
                _drawLink($from,$link->to,$link->label);
            }
        }
        _drawSubLinks($from);
    }
}
/**
 * Afficher un élément
 */
function _drawElement($element){
    $class = $element->display->class;
    $style     = "";
    if (isset($element->display->dashed)){
        $style     = "style='stroke-dasharray:5,5'";
    }
    echo <<<SVG
    <use id     		= 'element_$element->id'
        href      		= '#$class'
        x        		= '$element->x' y='$element->y'
		onmousedown		= 'evt.stopImmediatePropagation();evt.preventDefault();evt.stopPropagation();window.parent.svgElementClicked("$element->type","$element->id",evt.button)'
        $style>
            <title>$element->name</title>
    </use>\n
SVG;
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
	echo "\t<text x='".$x."' y='".($element->y+$element->height+20)."' class='element_label'><title>".$element->name."</title>".$text."</text>\n";
	if (isset($element->subElements)){
	    error_log("SubElements not supported for elements with class != rect_*_*");
	/*    $delta = 0;
	    foreach ($element->subElements as $subElement){
	        echo "\t<text x='".$x."' y='".($element->y+$element->height+20+delta)."' class='element_label'><title>".$subElement->name."</title>".$subElement->name."</text>\n";
	        $delta++;
	    }*/
	}
}
/**
 * Afficher un élément sous forme d'un rectangle
 */
function _drawElementAsRect($element){
    $style = '';
    $filter = '';
    if (isset($element->display->dashed)){
        $style     = "style='stroke-dasharray:5,5'";
    } else if (isset($element->display->blured)){
        $style     = "style='stroke-dasharray:1,2'";
    }
	echo <<<SVG
	<rect  x='$element->x' y='$element->y' width='$element->width' height='$element->height' 
           rx='5' ry='5' class='rect_www_hhh' filter='url(#shadow)'
		   onmouseup 			= 'evt.preventDefault();window.parent.svgElementClicked("$element->type","$element->id",evt.button);'
		   $style/>\n
SVG;
	$lines = _splitTextInLines($element->name,ELEMENT_CHAR_WIDTH,CHAR_HEIGHT,$element->width,$element->height);
	$x = $element->x;
	if (isset($element->subElements) && count($element->subElements) > 0){
	    $verticalgap = round(($element->height - (count($element->subElements) * CHAR_HEIGHT)) / (count($lines) + 1));
	    $y = $element->y + (CHAR_HEIGHT * 2);
	    $line = $lines[0];
	    $textwidth = strlen($line) * ELEMENT_CHAR_WIDTH;
	    $x = $element->x + round(($element->width - $textwidth) / 2);
	    echo '<text x="'.$x.'" y="'.($y).'" class="element_label">'.$line.'</text>';
	    foreach ($element->subElements as $subElement){
	        $subElement->x = $subElement->x + $element->x;
	        $subElement->y = $subElement->y + $element->y;
	        _drawElementAsRect($subElement);
	    }
	} else {
    	$verticalgap = round(($element->height - (count($lines) * CHAR_HEIGHT)) / (count($lines) + 1));
    	$y = $element->y + CHAR_HEIGHT + $verticalgap;
    	foreach ($lines as $line){
    		$textwidth = strlen($line) * ELEMENT_CHAR_WIDTH;
    		$x = $element->x + round(($element->width - $textwidth) / 2);
    		echo '<text x="'.$x.'" y="'.($y).'" class="element_label">'.$line.'</text>';
    		$y+=$verticalgap+ CHAR_HEIGHT;
    	}
	}
}
/**
 * Afficher un lien
 */
function _drawLink($from,$to,$label){
	echo "\t";
	$x1 = 0;
	$y1 = 0;
	$x2 = 0;
	$y2 = 0;
	$sens = "";
	// est-ce que to est à droite de from ?
	if (($from->x + ($from->width / 2)) < $to->x){
		$x1 = $from->x + $from->width + 2;
		$x2 = $to->x - 2;
		$sens = "droite";
	// est-ce que to est à gauche de from ?
	} else if (($to->x + ($to->width / 2)) < $from->x){
	    $x1 = $from->x - 2;
	    $x2 = $to->x + $to->width + 2;
	    $sens = "gauche";
	// ils sont "à peu près" au même niveau
	} else {
	    $x1 = $from->x + ceil($from->width / 2);
	    $x2 = $to->x + ceil($to->width / 2);
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
	echo "\n";
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
	echo "<!-- areas -->\n";
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
	echo "<!-- links -->\n";
	displayLinks($areas);
}
?>