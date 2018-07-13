global.project = {
	_currentProject : null,
	getCurrent : function(){
		return this._currentProject;
	},
	setCurrent : function(aProject){
		this._currentProject = aProject;
		/** Apply currentItem change */
		if (this._currentProject == null){
			$("*[data-provider^='currentProject']").each(function(index,listener){
				listener = $(listener);
				var attribute = listener.attr("data-provider");
				var changed = false;
				listener.text(this._currentProject.name);
				changed = true;
				listener.trigger("change");
			});
		} else {
			$("*[data-provider^='currentProject']").each(function(index,listener){
            	listener = $(listener);
            	var attribute = listener.attr("data-provider");
            	if (attribute == "currentProject.name"){
            		listener.text(this._currentProject.name);
            	}
				listener.trigger("change");
        	});
		}
		this.refresh();
	},
	open : function(aProjectId){
		$.getJSON( "api/project.php?id="+aProjectId, function(result) {
			global.project.setCurrent(result.objects[0]);
		});
	},
	refresh : function(){
		
	}
};