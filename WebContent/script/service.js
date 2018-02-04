var currentComponentId = null;
function refreshServiceList(){
	$.getJSON("api/service.php", function(result){
		var services = result.services;
		var options = "<option value='null' selected>--choisir un service--</option>";
		for (var i = 0; i < services.length; i++){
			var service = services[i];
			options += '<option value="'+service.id+'">'+service.name+'</option>';
		}
		$('#search_service_form_list').html(options);
		$('#edit_component_form_service_list').html(options);
		$('#process_step_create_form_service_list').html(options);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Impossible de charger les services</h1>"+textStatus+ " : " + error);
	});
}
function createService(){
	$("#create_service_form_domain_id").val(currentItem.id);
	$("#create_service_form").dialog({"modal":true,"title":"Création d'un service"});
}
function doCreateService(){
	var name 		= $("#create_service_form_name").val();
	var code 		= $("#create_service_form_code").val();
	var domain_id 	= $("#create_service_form_domain_id").val();
	$.ajax({
		type 	: "POST",
		url 	: "api/service.php",
		data	: {
			"code"		: code,
			"name"		: name,
			"domain_id"	: domain_id},
		dataType: "text",
		success	: function( data ) {
			$("#create_service_form").dialog("close");
			displayBusiness(currentItem.id);
			refreshServiceList();
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function displayService(serviceId){
	hideToolBox();
	$("#service_toolbox").show();
	if (serviceId == null){
		currentItem = null;
		clearFrame();
		$("#service_import_item_button").button("disable");
	} else {
		currentItem = {
			'id' 	: serviceId,
			refresh : function(){
				changeImage("views/view_service.php?id="+this.id);
				$("#service_import_item_button").button("enable");
				return this;
			},
			addItem : function(itemId){
				$.ajax({
					type 	: "POST",
					url 	: "api/element.php",
					data	: {
						"id"		: this.id,
						"child_id"	: itemId
					},
					dataType: "text",
					success	: function(data) {
						currentItem.refresh();
					}
				}).fail(function(jxqr,textStatus,error){
					alert(textStatus+" : "+error);
				});
			}
		}.refresh();
	}
}
function deleteService(id){
	if (!confirm("Etes-vous sûr de vouloir supprimer le service ?")){
		return;
	}
	$.ajax({
		type 	: "DELETE",
		url 	: "api/service.php?id="+id,
		dataType: "text",
		success	: function(data) {
			displayBusiness(currentItem.id);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function addItemToService(){
	openSearchItemForm(null);
}


function searchService(){
	var selectedService = $("#search_service_form_list").val();
	if (selectedService == "NULL"){
		return;
	}
	displayService(selectedService);
}