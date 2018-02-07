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