function displayViews() {
	hideToolBox();
	$("#views_toolbox").show();
	selectView();
}
function views_checkFill() {
	selectView();
}
function selectView() {
	var view = $("#viewSelected").val();
	if (view == "null") {
		$("#views_update_button").button("disable");
		clearFrame();
	} else {
		$("#views_update_button").button("enable");
		if ($('input[name=views_fill]').is(':checked')) {
			changeImage("views/view_views.php?view=" + view);
		} else {
			changeImage("views/view_views.php?view=" + view + "&fill=no");
		}
	}
}
function updateView() {
	var view = $("#viewSelected").val();
	if (view == "null") {
		return;
	}
	$.ajax({
		url : "api/view.php?view=" + view,
		dataType : "text",
		success : function(result) {
			$("#update_view_form_name").val(view);
			$("#update_view_form_value").val(result);
			$("#update_view_form").dialog({
				"modal" : true,
				"title" : "Mise à jour de la vue",
				"width" : 400,
				"height" : 400
			});
		}
	}).fail(function(jxqr, textStatus, error) {
		alert(textStatus + " : " + error);
	});
}