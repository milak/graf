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
	"delete" : function(aDocumentId){
		var documentId;
		if (typeof aDocumentId === "undefined") {
			if (this._current == null){
				sendMessage("warning",i18next.t("message.no_document_selected"));
				return;
			}
			documentId = this._current.id;
		} else {
			documentId = aDocumentId;
		}
		if (confirm(i18next.t("message.document_delete_confirm"))){
			$.ajax({
				"url" : "api/document.php?id="+documentId,
				"method" : "DELETE",
				"success" : function(){
					sendMessage("success",i18next.t("message.document_success_delete"));
					if (global.document.getCurrent() != null){
						if (global.document.getCurrent().id == documentId){
							global.document.close();
						}
					}
				}
			}).fail(function (jxqr,textStatus,error){
				sendMessage("error",i18next.t("message.document_failure_delete"));
			});
		}
	}
};