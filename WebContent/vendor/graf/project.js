global.project = {
	_currentProject : null,
	getCurrent : function(){
		return this._currentProject;
	},
	setCurrent : function(aProject){
		this._currentProject = aProject;
		this.refresh();
	},
	open : function(aProjectId){
		
	},
	refresh : function(){
		
	}
};