/**
 * Usage :
 *
 * Create a panel
 * $.panel({
 *	"class" : 'panel-primary',
 *	"beforeClose" : function (){
 *		if (confirm("Are your sure ?")){
 * 			return true;
 * 		} else {
 *			return false;
 *		}
 * 	}
 * }).html('Panel's content').title('Panel's title');
 * 
 * Create a panel
 * $.panel({
 *	"title" : 'Panel's title',
 * 	"class" : 'panel-success',
 *	"url"	: 'stat.html',
 *	"close" : function (){
 *		alert("Panel closed");
 *	}
 * });
 * 
 * Use an existing div
 * $("#myPanel").panel({
 *		"title" : 'Panel's title',
 *		"class" : 'panel-success'
 * }).close(function(){alert('Goodbye')});
 * 
 */
$(function () {
	$.panel = function(options){
		if ($._panelFrame === undefined){
			$("#body").panelFrame();
		}
		return $._panelFrame.createPanel($('<div></div>'),options);
	};
	$.fn.panel = function(options){
		if ($._panelFrame === undefined){
			$("#body").panelFrame();
		}
		var result = $._panelFrame.createPanel(this,options);
		result._restore = this;
		return result;
	};
	$.fn.panelFrame = function(){
		$._panelFrame 	= this;
		this._panelId 	= 0;
		this._panels 	= new Array();
		this.update 	= function (){
			var frame = this;
			frame.html("");
			var count = this._panels.length;
			if (count == 0){
				// rien
			} else if (count == 1){
				var panel = this._panels[0];
				panel._wrapper.width("98%");
				panel._wrapper.height("98%");
				frame.append(panel._wrapper);
				if (typeof panel._onUpdate !== "undefined") {
					panel._onUpdate();
				}
			} else {
				var nbRow = 1;
		        var nbCol = count;
		        while (nbCol > (nbRow + 1)) {
		            nbRow++;
		            nbCol = Math.ceil(count / nbRow);
		        }
		        //alert(nbCol+ " " + nbRow);
				var row = 1;
				var col = 1;
				var rowHeight = (Math.round(100/nbRow)-3)
				var divRow = $('<div style="display:inline-flex;width:100%;height:'+rowHeight+'%"></div>');
				frame.append(divRow);
				var index = 0;
				this._panels.forEach(function (panel){
					// dernier
					if (index == (count-1)){
						// Remplissage nécessaire
						if (col < nbCol){
							panel._wrapper.width(""+(Math.round(100/nbCol)+Math.round(100/nbCol)-4)+"%");
						} else {
							panel._wrapper.width(""+(Math.round(100/nbCol)-3)+"%");
						}
					} else {
						panel._wrapper.width(""+(Math.round(100/nbCol)-3)+"%");
					}
					panel._wrapper.height("98%");
					divRow.append(panel._wrapper);
					if (typeof panel._onUpdate !== "undefined") {
						panel._onUpdate();
					}
					col++;
					if (col > nbCol){
						divRow = $('<div style="display:inline-flex;width:100%;height:'+rowHeight+'%"></div>');
						frame.append(divRow);
						row++;
						col = 1;
					}
					index++;
				});
			}
		};
		this.getPanel = function (panelId){
			var result = null;
			this._panels.forEach(function(panel){
				if (panel.panelId == panelId){
					result = panel;
				}
			});
			return result;
		};
		this.removePanel = function (panelId){
			var index = 0;
			var panelFound = null;
			var found = -1;
			this._panels.forEach(function(panel){
				if (panel.panelId == panelId){
					if (typeof panel._onBeforeClose !== "undefined") {
						if (!panel._onBeforeClose()){
							return;					
						}
					}
					if (typeof panel._onClose !== "undefined") {
						panel._onClose();
					}
					panelFound = panel;
					found = index;
				}
				index++;
			});
			if (found != -1){
				if (typeof panelFound._restore !== "undefined"){
					panelFound._restore.hide();
					panelFound._restore.removeAttr("panelId");
					$("body").append(panelFound._restore);
				}
				this._panels.splice(found,1);
				this.update();
			}
		};
		this.createPanel = function(target,aOption){
			// Allready initialised
			if (typeof target.attr("panelId") != 'undefined'){
				return this.getPanel(target.attr("panelId"));
			}
	        var option = $.extend({ // These are the defaults.
	            class			: 'panel-default'
	        }, aOption);
			this._panelId++;
			target._wrapper = $('<div></div>');
			target.panelId 	= this._panelId;
			target.attr("panelId",target.panelId);
			// If no title asked, try to get the title allready set in the element 
			if (typeof aOption.title == 'undefined') {
				if (typeof target.attr("title") != 'undefined'){
					option.title 	= target.attr("title");
				} else {
					option.title 	= "";
				}
			}
			target.title 	= option.title;
			target.class 	= option.class;
			target.show();
			if (typeof option.beforeClose !== undefined){
				target._onBeforeClose = option.beforeClose;
			}
			if (typeof option.done !== undefined){
				target._onDone = option.done;
			}
			if (typeof option.close !== undefined){
				target._onClose = option.close;
			}
			if (typeof option.update !== undefined){
				target._onUpdate = option.update;
			}
			target._wrapper.attr('class', "panel "+target.class);
			target.heading = $('<div class="panel-heading"></div>');
					var titlePane = $('<span>'+target.title+'</span>');
					target.heading.append(titlePane);
					var close = $('<a href="#" onClick="$._panelFrame.removePanel('+target.panelId+')" class="pull-right"></a>');
					close.html('<em class="fa fa-times"></em>');
					target.heading.append(close);
					var reload = $('<a href="#" onClick="$._panelFrame.getPanel('+target.panelId+').reload()" class="pull-right"></a>');
					reload.html('<em class="fa fa-reload"></em>');
					target.heading.append(reload);
			target._wrapper.append(target.heading);
					var body = $('<div class="panel-body"></div>');
					body.append(target);
			target._wrapper.append(body);
		    this._panels.push(target);
		    /*target.html = function(html){
		    	this.html(html);
		    	return this;
		    };*/
		    target.title = function(text){
		    	titlePane.text(text);
		    	return this;
		    };
		    target.close = function(func){
		    	if (func === undefined){
		    		$._panelFrame.removePanel(this.panelId);
		    	} else {
		    		this._onClose = func;
		    	}
		    };
		    target.reload = function(func){
		    	target.html("");
		    	$.ajax({
		    		url: target.url
	    		}).done(function(data) {
	    			target.html(data);
	    			if (typeof target._onDone !== "undefined") {
	    				target._onDone();
					}
	    		});
		    };
		    if (option.url !== undefined){
		    	target.url = option.url;
		    	target.reload();
		    }
		    this.update();
		    return target;
		};
		return this;
	};
});