function createActor(){
	$.getJSON("api/element_class.php?category_name=actor",function (result){
		var classes = result.classes;
		var options = "";
		for (var i = 0; i < classes.length; i++){
			var aclass = classes[i];
			options += '<option value="'+aclass.id+'">'+aclass.name+'</option>';
		}
		$('#create_actor_form_class').html(options);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Impossible de charger les categories d'acteur</h1>"+textStatus+ " : " + error);
	});
	$("#create_actor_form").dialog({"modal":true,"title":"Création d'un acteur","minWidth":500});
}
function doCreateActor(){
	var name 	= $("#create_actor_form_name").val();
	var class_id = $("#create_actor_form_class").val();
	$.ajax({
		type 	: "POST",
		url 	: "api/element.php",
		data	: {
			"name"		: name,
			"class_id"	: class_id,
			"domain_id"	: currentItem.id},
		dataType: "text",
		success	: function( data ) {
			refreshActorLists();
			displayBusiness(currentItem.id);
			$("#create_actor_form").dialog("close");
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function refreshActorLists(){
	$.getJSON("api/element.php?category_name=actor", function(result){
		var elements = result.elements;
		var options = "<option value='null' selected>--choisir un acteur--</option>";
		for (var i = 0; i < elements.length; i++){
			var element = elements[i];
			options += '<option value="'+element.id+'">'+element.name+'</option>';
		}
		$('#process_step_create_form_actor_list').html(options);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Impossible de charger les acteurs</h1>"+textStatus+ " : " + error);
	});
}