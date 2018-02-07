function displayStrategic(){
	currentItem = {
			refresh : function() {
				hideToolBox();
				$("#strategic_toolbox").show();
				strategic_checkSeeProcess();
				return this;
			}
	}.refresh();
}
function strategic_checkSeeProcess(){
	if( $('input[name=strategic_viewprocess]').is(':checked') ){
		changeImage("views/view_strategique.php?showProcess=true");
	} else {
		changeImage("views/view_strategique.php");
	}
}