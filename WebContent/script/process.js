function displayProcess(processId){
	hideToolBox();
	$("#process_toolbox").show();
	if (processId == null){
		clearFrame();
		$("#process_create_step_button").button("disable");
	} else {
		changeImage("views/view_process.php?id="+processId);
		$("#process_create_step_button").button("enable");
	}
}
function deleteProcess(processId){
	if (!confirm("Etes-vous sûr de vouloir supprimer le processus ?")){
		return;
	}
	$.ajax({
		type 	: "DELETE",
		url 	: "api/process.php?id="+processId,
		dataType: "text",
		success	: function( data ) {
   			alert("Processus supprimé");
			displayBusiness(null);
		}
	}).fail(function(jxqr,textStatus,error){
		alert(textStatus+" : "+error);
	});
}
function createStep(){
	alert("Not yet implemented");
}