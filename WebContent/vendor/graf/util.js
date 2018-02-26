function showPopup(title,body){
	$("#popup").html(body);
	$("#popup").dialog({"modal":true,"title":title,"minWidth":400});
}
function hidePopup(){
	$("#popup").dialog("close");
}

function sortByName(a, b){
	return a.name.localeCompare(b.name);
}
function initAutoComplete(){
	//Auto completion lors de la recherche
	$('input.typeahead').typeahead({
			minLength: 3,
		  	highlight: true
	},{
		source : function (query, syncResults, asyncResults){
			return $.get('api/element.php', { name: query }, function (data) {
	        	var elements = data.elements;
	        	var result = new Array();
	        	for(var i = 0; i < elements.length; i++){
	        		elements[i].categoryName = elements[i].category.name;
	        		result.push(elements[i]);
	        	}
	        	asyncResults(result);
	        }).fail(function(jxqr,textStatus,error){
				sendMessage("error","Unable to get elements : "+error);
			});
		},
		display : Handlebars.compile(''),
		templates: {
			//header : "Items found",
		    /*empty: [
		      '<div class="empty-message">',
		        'unable to find any item that match the current query',
		      '</div>'
		    ].join('\n'),*/
		    suggestion: Handlebars.compile('<div><strong>{{categoryName}}</strong> – {{name}}</div>')
		}
	}).bind('typeahead:select', function(ev, suggestion) {
		  openItem(suggestion);
	});
}
function svgElementClicked(what,id,button){
	if (button == 0){ // Left button
		$.getJSON( "api/element.php?id="+id, function(result) {
			if (result.elements.length == 0){
				sendMessage("warning","Item not found (id = "+id+")");
			} else {
				var element = result.elements[0];
				openItem(element);
			}
		});
	} else { // Right button
		showContextMenu(what,id);
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
		sendMessage("error","Unable to get item information : "+error);
	});
}
function sendMessage(level,message){
	var wait = 1000;
	if (level == "warning"){
		$("#alert").attr('class', "alert alert-warning");
		$("#alertBadge").attr('class', "badge badge-warning");
		$("#alertLevel").text("Warning");
		$("#alertIcon").attr("src","images/58.png");
		wait = 2800;
	} else if (level == "info"){
		$("#alert").attr('class', "alert alert-info");
		$("#alertBadge").attr('class', "badge badge-info");
		$("#alertLevel").text("Info");
		$("#alertIcon").attr("src","images/4.png");
	} else if (level == "success"){
		$("#alert").attr('class', "alert alert-success");
		$("#alertBadge").attr('class', "badge badge-success");
		$("#alertLevel").text("Success");
		$("#alertIcon").attr("src","images/3.png");
	} else if ((level == "error") || (level == "danger")){
		$("#alert").attr('class', "alert alert-danger");
		$("#alertBadge").attr('class', "badge badge-danger");
		$("#alertIcon").attr("src","images/89.png");
		$("#alertLevel").text("Error");
		wait = 3000;
	} else if (level == "primary"){
		$("#alert").attr('class', "alert alert-primary");
		$("#alertBadge").attr('class', "badge badge-primary");
		$("#alertLevel").text("Info");
		$("#alertIcon").attr("src","images/49.png");
	} else {
		$("#alert").attr('class', "alert alert-primary");
		$("#alertBadge").attr('class', "badge badge-primary");
		$("#alertLevel").text("");
		$("#alertIcon").attr("src","images/49.png");
	}
	$("#alertMessage").text(message);
	$("#alert").show("slide").delay(wait).hide("fade", {}, 1200);
}
var global = {
	itemCategories 	: null,
	views 			: null,
	currentItem 	: null
};
$(function() {
	initAutoComplete();
	var menuViewDropDown = $("#menuViewDropDown");
	Object.keys(viewDescription).forEach(function (index) {
		menuViewDropDown.append($("<a class='dropdown-item' href='#' onClick='addView(\""+index+"\")'><img src='images/"+viewDescription[index].icon+"'/> "+viewDescription[index].title+"</a>"));
	});
	$("#main").panelFrame();
	$.getJSON( "api/element_class.php", function(result) {
		global.itemCategories = result.categories;
	}).fail(function(jxqr,textStatus,error) {
		sendMessage("error","Unable to load item classes : "+error);
	});
	$.getJSON( "api/view.php?areas=list", function(result) {
		global.views = result.views;
	}).fail(function(jxqr,textStatus,error) {
		sendMessage("error","Unable to load views : "+error);
	});
});