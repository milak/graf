<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="">
<meta name="author" content="milak">
<link rel="icon" href="images/favicon.ico">
<title>GRAF - Graphic Rendering Architecture Framework</title>
<link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.css" rel="stylesheet">
<link rel="stylesheet" href="vendor/jquery-panel/css/jquery-panel.css" />
<link rel="stylesheet" href="vendor/jquery/ui/1.12.1/themes/base/jquery-ui.css">
<link rel="stylesheet" href="vendor/datatable/jquery.dataTables.min.css">
<link rel="stylesheet" href="vendor/font-awsome/css/font-awesome.min.css" />
<style type="text/css">
html {
	height: 100%;
	overflow: hidden;
}
</style>
</head>
<body oncontextmenu="event.preventDefault()">
	<header>
		<!-- Fixed navbar -->
		<nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
			<a class="navbar-brand" href="#" onClick="applyItemId(null,null)"
				title="Graphic Rendering Architecture Framework">GRAF</a>
			<button class="navbar-toggler" type="button" data-toggle="collapse"
				data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false"
				aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarCollapse">
				<ul class="navbar-nav mr-auto">
					<li class="nav-item active"><a class="nav-link" href="#" onClick="back()">Back <span
							class="sr-only">(current)</span></a></li>
					<li class="nav-item"><a class="nav-link" href="#" onClick="addView('searchItem')">Search
							an item</a></li>
					<li class="nav-item dropdown"><a class="nav-link dropdown-toggle"
						href="http://example.com" id="menuCurrentItem" data-toggle="dropdown"
						aria-haspopup="true" aria-expanded="false">Choose an item</a>
						<div class="dropdown-menu" aria-labelledby="menuCurrentItem">
							<a class="dropdown-item disabled" href="#" id="menuDeleteItem" onClick="deleteItem()" disabled="true">Delete</a>
						</div></li>
					<li class="nav-item dropdown"><a class="nav-link dropdown-toggle"
						href="http://example.com" id="dropdown01" data-toggle="dropdown"
						aria-haspopup="true" aria-expanded="false">Views</a>
						<div class="dropdown-menu" aria-labelledby="dropdown01">
							<a class="dropdown-item" href="#" onClick="addView('strategic')">Strategic</a> <a
								class="dropdown-item" href="#" onClick="addView('business')">Business</a> <a
								class="dropdown-item" href="#" onClick="addView('logical')">Logical</a> <a
								class="dropdown-item" href="#" onClick="addView('technical')">Technical</a> <a
								class="dropdown-item" href="#" onClick="addView('service')">Service</a> <a
								class="dropdown-item" href="#" onClick="addView('process')">Process</a>
						</div></li>

				</ul>
				<form class="form-inline mt-2 mt-md-0">
					<input class="form-control mr-sm-2" type="text" placeholder="Search"
						aria-label="Search">
					<button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
				</form>
			</div>
		</nav>
	</header>
	<!-- Begin page content -->
	<main id="main" style="position:absolute;top:60px;width:100%; bottom:0px;"> </main>
	<svg id="strategic" style="width: 100%; height: 100%; display: none"></svg>
	<svg id="business" style="width: 100%; height: 100%; display: none"></svg>
	<svg id="logical" style="width: 100%; height: 100%; display: none"></svg>
	<svg id="service" style="width: 100%; height: 100%; display: none"></svg>
	<svg id="process" style="width: 100%; height: 100%; display: none"></svg>
	<svg id="technical" style="width: 100%; height: 100%; display: none"></svg>
	<div id="searchItem" style="width: 100%; height: 100%; display: none"></div>
	<div id="popup" style="display: none"></div>
	<!-- ========================================================== -->
	<!-- Placed at the end of the document so the pages load faster -->
	<script type="text/javascript" src="vendor/jquery/jquery-3.2.1.min.js"></script>
	<script type="text/javascript" src="vendor/jquery/ui/1.12.1/jquery-ui.js"></script>
	<script type="text/javascript" src="vendor/svgtool/svg-pan-zoom.js"></script>
	<script type="text/javascript" src="vendor/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="vendor/jquery-panel/js/jquery-panel.js"></script>
	<script type="text/javascript" src="vendor/graf/util.js"></script>
	<script type="text/javascript">
    	var viewDescription = new Array();
    	viewDescription['strategic'] 	= { url : 'views/view_strategique.php', class : 'panel-purple', static : true, 	title : "Vue stratégique"};
    	viewDescription['business'] 	= { url : 'views/view_business.php', 	class : 'panel-purple', static : false, title : "Vue métier"};
    	viewDescription['logical'] 		= { url : 'views/view_logique.php', 	class : 'panel-purple', static : false, title : "Vue logique"};
    	viewDescription['service'] 		= { url : 'views/view_process.php', 	class : 'panel-purple', static : false, title : "Vue service"};
    	viewDescription['process'] 		= { url : 'views/view_process.php', 	class : 'panel-purple', static : false, title : "Vue processus"};
    	viewDescription['technical']	= { url : 'views/view_technique.php', 	class : 'panel-purple', static : false, title : "Vue technique"};
    	viewDescription['searchItem'] 	= { url : 'forms/searchItem.html', 		class : 'panel-green', 	static : true,	title : "Chercher un élément"};
    	var views = new Array();
    	function addView(viewName){
        	var viewPanel;
        	var description = viewDescription[viewName];
           	if (description != null){
				var panel = {
					title : description.title,
            		class 	: description.class,
        			done	: function(){
        				try{
            				this.svgPanZoom.destroy();
            			}catch(exception){}
            			try{
        					this.svgPanZoom.destroy();
            			}catch(exception){}
            			try{
	        				this.svgPanZoom = svgPanZoom("#"+viewName, {
	        				    zoomEnabled				: true,
	        				    dblClickZoomEnabled		: false,
	        				    controlIconsEnabled		: true,
	        				    fit						: true,
	        				    center					: false,
	        				    minZoom					: 0.1,
	        				    zoomScaleSensitivity 	: 0.3
	        				});
            			}catch(exception){}
        			},
        			update	: function(){
            			try{
            				this.svgPanZoom.destroy();
            			}catch(exception){}
            			try{
        					this.svgPanZoom.destroy();
            			}catch(exception){}
            			try{
        				this.svgPanZoom = svgPanZoom("#"+viewName, {
        				    zoomEnabled				: true,
        				    dblClickZoomEnabled		: false,
        				    controlIconsEnabled		: true,
        				    fit						: true,
        				    center					: false,
        				    minZoom					: 0.1,
        				    zoomScaleSensitivity 	: 0.3
        				});
            			}catch(exception){
                			//console.log(exception);
                		}
        			},
        			close  : function(){
            			// retirer la vue
            			var index = -1;
            			for(var i = 0; i < views.length; i++){
                        	var view = views[i];
                        	if (view.panelId == this.panelId){
                            	index = i;
                            	break;
                        	}
            			}
            			if (index != -1){
            				views.splice(index,1);
            			}
        			}
        		}
               	var url = description.url;
               	if (!description.static) {
               		if (currentItem != null){
                	   	panel.url = url + "?id="+currentItem.id;
               		} else {
               			panel.url = null;
               		}
               	} else {
               		panel.url = url;
               	}
           		viewPanel = $('#'+viewName).panel(panel);
        		viewPanel.viewDescription = description;
           		views.push(viewPanel);
        	} else {
            	alert("Vue non supportée " + view);
        	}
    	}
    	var itemsList = new Array();
    	var currentItem = null;
    	function back(){
        	if (itemsList.length > 0){
        		currentItem = itemsList.pop();
        		applyItemId(currentItem);
        	} else {
        		currentItem = null;
        		applyItemId(null);
        	}
    	}
    	function deleteItem(){
        	if (currentItem != null){
        		alert("Ah ah tu y as cru ?");
        	}
    	}
    	function applyItemId(item){
        	if (item != null){
        		$.getJSON( "api/element.php?id="+item.id, function(result) {
        			var element = result.elements[0];
        			$("#menuCurrentItem").html(element.category.name+" - "+element.name);
        			//$("#menuDeleteItem").prop( "disabled", false);
        			$("#menuDeleteItem").attr('class', "dropdown-item");
        		});
        	} else {
        		$("#menuCurrentItem").html("Choose an item");
        		//$("#menuDeleteItem").prop( "disabled", true );
        		$("#menuDeleteItem").attr('class', "dropdown-item disabled");
        	}
    		for (var i = 0; i < views.length; i++){
            	var view = views[i];
            	if (!view.viewDescription.static){
                	if (item != null){
            			view.reload(view.viewDescription.url+"?id="+item.id);
                	} else {
                		view.reload(null);
                	}
            	}
        	}
    	}
    	function showContextMenu(what,id){
	    	$.getJSON( "api/element.php?id="+id, function(result) {
				var element = result.elements[0];
				var html = "<p>Instance</p>";
				html += "<b>Nom</b> : "+element.name+"<br/><br/>";
				html += "<hr/>";
				html+="<button onclick='hidePopup();svgElementClicked(\"\",\""+id+"\",0)'><img src='images/63.png'/> ouvrir</button>";
				html += " <button onclick='hidePopup();deleteItem(\""+id+"\")'><img src='images/14.png'/> supprimer</button>";
				html += " <button onclick='hidePopup()'><img src='images/33.png'/> fermer</button>";
				showPopup("Détail",html);
			}).fail(function(jxqr,textStatus,error) {
				showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
			});
    	}
    	function svgElementClicked(what,id,button){
        	if (button == 0){ // Left button
            	if (currentItem != null){
        			itemsList.push(currentItem);
            	}
        		currentItem = {id : id, category : what};
        		applyItemId(currentItem);
        	} else { // Right button
        		showContextMenu(what,id);
        	}
    	}
    	$(function() {
        	$("#main").panelFrame();
    	});
    </script>
</body>
</html>