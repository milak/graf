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
			$.getJSON('api/item.php', { name: query }, function (result) {
				if (result.code != 0){
					sendMessage("error",i18next.t("message.item_failure_search")+" : "+result.message);
				} else {
		        	var elements = result.objects;
		        	var searchResult = new Array();
		        	for (var i = 0; i < elements.length; i++){
		        		elements[i].categoryName = i18next.t("category."+elements[i].category.name);
		        		elements[i].is = "item";
		        		searchResult.push(elements[i]);
		        	}
		        	$.getJSON('api/document.php', { name: query }, function (result) {
		        		var documents = result.documents;
			        	for (var i = 0; i < documents.length; i++){
			        		documents[i].categoryName = documents[i].type;
			        		documents[i].is = "document";
			        		searchResult.push(documents[i]);
			        	}
		        		asyncResults(searchResult);
		        	}).fail(function(jxqr,textStatus,error){
						sendMessage("error",i18next.t("message.document_failure_search")+" : "+error);
					});
				}
	        }).fail(function(jxqr,textStatus,error){
				sendMessage("error",i18next.t("message.item_failure_search")+" : "+error);
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
		if (suggestion.is == "item"){
			global.item.open(suggestion);
		} else {
			global.document.open(suggestion.id);
		}
	});
}
function svgElementClicked(what,id,button){
	if (button == 0){ // Left button
		$.getJSON( "api/item.php?id="+id, function(result) {
			if (result.code != 0){
				sendMessage("error",i18next.t("message.item_no_information")+" : "+result.message);
			} else if (result.objects.length == 0){
				sendMessage("warning",i18next.t("message.item_no_information") + " ("+i18next.t("form.item.id")+" = "+id+")");
			} else {
				var element = result.objects[0];
				global.item.open(element);
			}
		}).fail(function(jxqr,textStatus,error){
			sendMessage("error",i18next.t("message.item_no_information")+" : "+error);
		});
	} else { // Right button
		showContextMenu(what,id);
	}
}
function showContextMenu(what,id){
	$.getJSON( "api/item.php?id="+id, function(result) {
		if (result.code != 0){
			sendMessage("error",i18next.t("message.item_no_information")+" : "+result.message);
		} else if (result.objects.length == 0){
			sendMessage("warning",i18next.t("message.item_no_information") + " ("+i18next.t("form.item.id")+" = "+id+")");
		} else {
			var element = result.objects[0];
			var html = "<p>"+i18next.t("form.item.title")+"</p>";
			html += "<div class='bd-callout-info bd-callout'>";
			html += "<b>"+i18next.t("form.item.name")+"</b> : "+element.name+"<br/><br/>";
			html += "</div>";
			html+="<button class='btn btn-primary btn-sm' onclick='hidePopup();svgElementClicked(\"\",\""+id+"\",0)'><img src='images/63.png'/> "+i18next.t("form.button.open")+"</button>";
			html += " <button class='btn btn-danger btn-sm' onclick='hidePopup();global.item.delete(\""+id+"\")'><img src='images/14.png'/> "+i18next.t("form.button.delete")+"</button>";
			html += " <button class='btn btn-danger btn-sm' onclick='hidePopup()'><img src='images/33.png'/> "+i18next.t("form.button.close")+"</button>";
			showPopup(i18next.t("view.item_detail"),html);
		}
	}).fail(function(jxqr,textStatus,error) {
		sendMessage("error",i18next.t("message.item_no_information")+" : "+error);
	});
}
function sendMessage(level,message){
	var wait = 1000;
	if (level == "warning"){
		$("#alert").attr('class', "alert alert-warning");
		$("#alertBadge").attr('class', "badge badge-warning");
		$("#alertLevel").text(i18next.t("message.Warning"));
		$("#alertIcon").attr("src","images/58.png");
		wait = 2800;
	} else if (level == "info"){
		$("#alert").attr('class', "alert alert-info");
		$("#alertBadge").attr('class', "badge badge-info");
		$("#alertLevel").text(i18next.t("message.Info"));
		$("#alertIcon").attr("src","images/4.png");
	} else if (level == "success"){
		$("#alert").attr('class', "alert alert-success");
		$("#alertBadge").attr('class', "badge badge-success");
		$("#alertLevel").text(i18next.t("message.Success"));
		$("#alertIcon").attr("src","images/3.png");
	} else if ((level == "error") || (level == "danger")){
		$("#alert").attr('class', "alert alert-danger");
		$("#alertBadge").attr('class', "badge badge-danger");
		$("#alertIcon").attr("src","images/89.png");
		$("#alertLevel").text(i18next.t("message.Error"));
		wait = 3000;
	} else if (level == "primary"){
		$("#alert").attr('class', "alert alert-primary");
		$("#alertBadge").attr('class', "badge badge-primary");
		$("#alertLevel").text(i18next.t("message.Info"));
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
function initI18N(){
	var lang = navigator.language || navigator.userLanguage; 
	_loadLang(lang);
}
function _loadLang(lang){
	$.getJSON("i18n/"+lang+".json", function(result) {
		var resources = {
			"lng" : lang,
			"resources" : result
		};
		i18next.init(resources, function(err, t) {
			jqueryI18next.init(i18next, $);
			$('*[data-i18n]').localize();
			global.view.init();
		});
	}).fail(function (jxqr,textStatus,error){
		if (lang != "en") {
			_loadLang("en");
		} else {
			sendMessage("error","Can't find any lang resources"); // Nb : ne pas essayer d'internationaliser
		}
	});
}
$(function() {
	initI18N();
	initAutoComplete();
	$("#main").panelFrame();
	$.getJSON( "api/element_class.php", function(result) {
		global.itemCategories = result.categories;
	}).fail(function(jxqr,textStatus,error) {
		sendMessage("error",i18next.t("unable_to_load_item_classes")+" : "+error);
	});
	$.getJSON( "api/view.php?areas=list", function(result) {
		global.views = result.views;
	}).fail(function(jxqr,textStatus,error) {
		sendMessage("error",i18next.t("unable_to_load_views")+" : "+error);
	});
});