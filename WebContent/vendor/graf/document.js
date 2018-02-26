global.document = {
	_current : null,
	open : function(id){
		this._current = {id:id};
		sendMessage("warning",i18next.t("message.not_yet_implemented"));
		this.refresh();
	},
	close : function(id){
		this._current = null;
		this.refresh();
	},
	setCurrent : function(id){
		this._current = id;
		this.refresh();
	},
	getCurrent : function(){
		return this._current;
	},
	refresh : function(){
		if (this._current != null){
			$("#menuDeleteDocument").attr("disabled", false);
			$("#menuDeleteDocument").attr('class', "dropdown-item");
		} else {
			$("#menuDeleteDocument").attr("disabled", true);
			$("#menuDeleteDocument").attr('class', "dropdown-item disabled");
		}
		/** Apply currentItem change */
    	$("*[data-provider^='currentDocument']").each(function(index,listener){
        	listener = $(listener);
        	var attribute = listener.attr("data-provider");
        	if (attribute == "currentDocument.name"){
        		listener.text(item.name);
        	} else if (attribute == "currentDocument.type"){
        		listener.text(item.type);
        	}
			listener.trigger("change");
    	});
		breadCrumb.refresh();
	},
	"delete" : function(){
		sendMessage("warning",i18next.t("message.not_yet_implemented"));
	}
};