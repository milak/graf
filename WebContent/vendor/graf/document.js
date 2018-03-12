global.document = {
	_current : null,
	open : function(id){
		$.getJSON("api/document.php?id="+id,function(result){
			if (result.documents.length == 0){
				sendMessage("error",i18next.t("message.document_failure_get"));
			} else {
				var document = result.documents[0];
				global.document.setCurrent({id:id,name:document.name,type:document.type});
			}
		}).fail(function(jxqr,textStatus,error){
			sendMessage("error",i18next.t("message.document_failure_get")+" : "+error);
		});
	},
	close : function(){
		this.setCurrent(null);
	},
	setCurrent : function(document){
		this._current = document;
		this.refresh();
	},
	getCurrent : function(){
		return this._current;
	},
	refresh : function(){
		var current = this.getCurrent();
		if (current != null){
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
        		listener.text(current.name);
        	} else if (attribute == "currentDocument.type"){
        		listener.text(current.type);
        	}
			listener.trigger("change");
    	});
		breadCrumb.refresh();
		global.view.applyDocument(document);
	},
	unlinkFromItem : function(aDocumentId, aItemId){
		$.ajax({
			"url" : "api/document.php?id="+aDocumentId+"&itemId="+aItemId,
			"method" : "DELETE",
			"dataType" : "json",
			"success" : function(result) {
				if (result.code != 0){
					sendMessage("error", i18next.t("message.document_failure_update") + " : " + result.message);
				} else {
					sendMessage("success", i18next.t("message.document_success_update"));
					global.document.refresh();
					global.item.refresh();
				}
			}
		}).fail(function(jxqr, textStatus, error) {
			sendMessage("error", i18next.t("message.document_failure_update") + " : " + error);
		});
	},
	"delete" : function(aDocumentId){
		var documentId;
		if (typeof aDocumentId === "undefined") {
			var current = this.getCurrent();
			if (current == null){
				sendMessage("warning",i18next.t("message.no_document_selected"));
				return;
			}
			documentId = current.id;
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
					// Maybe the document was linked to item
					if (global.item.getCurrent() != null){
						global.item.refresh();
					}
				}
			}).fail(function (jxqr,textStatus,error){
				sendMessage("error",i18next.t("message.document_failure_delete"));
			});
		}
	}
};