var currentSolutionId = null;
function refreshSolutionLists(){
	$.getJSON("api/element.php?category_name=solution", function(result){
		var elements = result.elements;
		var options = "<option value='null' selected>--choisir une solution--</option>";
		for (var i = 0; i < elements.length; i++){
			var element = elements[i];
			options += '<option value="'+element.id+'">'+element.name+'</option>';
		}
		$('#search_solution_form_list').html(options);
	}).fail(function(jxqr,textStatus,error) {
		showPopup("Echec","<h1>Impossible de charger les solutions</h1>"+textStatus+ " : " + error);
	});
}
function displaySolution(solutionId){
	hideToolBox();
	$("#logic_toolbox").show();
	if (solutionId == null){
		clearFrame();
		$("#logic_create_software_button").button("disable");
		$("#logic_create_device_button").button("disable");
		$("#logic_create_service_button").button("disable");
		$("#logic_create_data_button").button("disable");
		$("#logic_create_instance_button").button("disable");
		currentSolutionId = null;
	} else {
		changeImage("views/view_logique.php?id="+solutionId);
		$("#logic_create_software_button").button("enable");
		$("#logic_create_device_button").button("enable");
		$("#logic_create_service_button").button("enable");
		$("#logic_create_data_button").button("enable");
		$("#logic_create_instance_button").button("enable");
		currentSolutionId = solutionId;
	}
}
function searchSolution(){
	var selectedSolution = $("#search_solution_form_list").val();
	if (selectedSolution == "null"){
		return;
	}
	displaySolution(selectedSolution);
}