function createActor(){
	$("#create_actor_form").dialog({"modal":true,"title":"Création d'un acteur"});
}
function doCreateActor(){
	var name 	= $("#create_actor_form_name").val();
	$.ajax({
		type 	: "POST",
		url 	: "api/actor.php",
		data	: {
			"name"		: name,
			"domain_id"	: currentDomainId},
		dataType: "text",
		success	: function( data ) {
			displayBusiness(currentDomainId);
			$("#create_actor_form").dialog("close");
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function deleteActor(id){
	if (!confirm("Etes-vous sûr de vouloir supprimer l'acteur ?")){
		return;
	}
	$.ajax({
		type 	: "DELETE",
		url 	: "api/actor.php?id="+id,
		dataType: "text",
		success	: function(data) {
			displayBusiness(currentDomainId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}