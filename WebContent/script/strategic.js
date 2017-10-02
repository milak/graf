function displayStrategic(){
	hideToolBox();
	$("#strategic_toolbox").show();
	strategic_checkSeeProcess();
}
function strategic_checkSeeProcess(){
	if( $('input[name=strategic_viewprocess]').is(':checked') ){
		changeImage("views/view_strategique.php?showProcess=true");
	} else {
		changeImage("views/view_strategique.php");
	}
}
		