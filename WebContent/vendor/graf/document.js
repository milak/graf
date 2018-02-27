global.document = {
	_current : null,
	open : function(id){
		$.getJSON("api/document.php?id="+id,function(result){
			if (result.documents.length == 0){
				sendMessage("error",i18next.t("message.document_failure_get"));
			} else {
				var document = result.documents[0];
				global.document._current = {id:id,name:document.name};
				global.document.refresh();
			}
		}).fail(function(jxqr,textStatus,error){
			sendMessage("error",i18next.t("message.document_failure_get")+" : "+error);
		});
		
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