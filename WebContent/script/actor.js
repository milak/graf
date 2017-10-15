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
			refreshActorLists();
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
			refreshActorLists();
			displayBusiness(currentDomainId);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function refreshActorLists(){
	$.getJSON("api/actor.php", function(result){
		var actors = result.actors;
		var options = "<option value='null' selected>--choisir un acteur--</option>";
		for (var i = 0; i < actors.length; i++){
			var actor = actors[i];
			options += '<option value="'+actor.id+'">'+actor.name+'</option>';
		}
		$('#process_step_create_form_actor_list').html(options);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Impossible de charger les acteurs</h1>"+textStatus+ " : " + error);
	});
}