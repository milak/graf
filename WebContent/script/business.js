function displayBusiness(domainId){
	hideToolBox();
	$("#business_toolbox").show();
	if (domainId == null) {
		var id = $("#domainSelected").val();
		if (id != "null"){
			domainId = id;
		}
	}
	if (domainId != $("#domainSelected").val()){
		$("#domainSelected").val(""+domainId);
		$("#business_toolbox").controlgroup("refresh");
	}
	if (domainId == null) {
		$("#business_create_process_button").button("disable");
		$("#business_create_service_button").button("disable");
		clearFrame();
	} else {
		$("#business_create_process_button").button("enable");
		$("#business_create_service_button").button("enable");
		changeImage("views/view_business.php?id="+domainId);
	}
}
function createDomain(){
	$.getJSON( "api/area.php?view=strategique", function(result) {
		var areas = result.areas;
		$('#domain_create_form_area').html("");
		var options = "<option value='null'>~~Choisir une zone~~</option>";
		for (var i = 0; i < areas.length; i++){
			var area = areas[i];
			options += '<option value="'+area.id+'">'+area.name+'</option>';
		}
		$('#domain_create_form_area').append(options);
		$("#domain_create_form").dialog({"modal":true,"title":"Création d'un domaine"});
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Error</h1>"+textStatus+ " : " + error);
	});
}
function doCreateDomain(){
	var name 	= $("#domain_create_form_name").val();
	var area_id 	= $("#domain_create_form_area").val();
	$.ajax({
		type 	: "POST",
		url 	: "api/domain.php",
		data	: {
			"name"		: name,
			"area_id"	: area_id},
		dataType: "text",
		success	: function( data ) {
				refreshDomainList();
			displayStrategic();
			$("#domain_create_form").dialog("close");
		}
	})
		.fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function deleteDomain(domainId){
	if (!confirm("Etes-vous sûr de vouloir supprimer le domaine ?")){
		return;
	}
	$.ajax({
		type 	: "DELETE",
		url 	: "api/domain.php?id="+domainId,
		dataType: "text",
		success	: function(data) {
			displayStrategic();
			refreshDomainList();
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}