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
		this.rows = new Array();
		for (var i = 0; i < 5; i++){
			var div = $('<div style="display:inline-flex;width:100%;height:0px"></div>');
			div.hide();
			this.rows.push(div);
			this.append(div);
		}
		$._panelFrame 	= this;
		this._panelId 	= 0;
		this._maximized = null;
		this._panels 	= new Array();
		this.addPanel 	= function (panel){
			
		},
		this.update 	= function (){
			for (var i = 0; i < this.rows.length; i++){
				var div = this.rows[i];
				div.hide();
			}
			var count = this._panels.length;
			if (count == 0){
				// rien
			} else {
				var nbRow = 1;
		        var nbCol = count;
		        while (nbCol > (nbRow + 1)) {
		            nbRow++;
		            nbCol = Math.ceil(count / nbRow);
		        }
				var row = 0;
				var col = 1;
				var rowHeight = Math.ceil(100/nbRow);
				var colWidth = Math.ceil(100/nbCol);
				var divRow = this.rows[row];
				divRow.height(rowHeight+"%");
				divRow.show();
				for (var p = 0; p < this._panels.length; p++){
					var panel = this._panels[p];
					if (col > nbCol){
						row++;
						divRow = this.rows[row];
						divRow.height(rowHeight+"%");
						divRow.show();
						col = 1;
					}
					// dernier
					if (p == (count-1)){
						// Remplissage nécessaire ?
						if (col < nbCol){
							var colCount = nbCol-col +1;
							panel._wrapper.width(""+(colCount * colWidth) +"%");
						} else {
							panel._wrapper.width(""+colWidth+"%");
						}
					} else {
						panel._wrapper.width(""+colWidth+"%");
					}
					panel._wrapper.height("100%");
					divRow.append(panel._wrapper);
					if (typeof panel._onUpdate !== "undefined") {
						try{
							panel._onUpdate();
						}catch(exception){}
					}
					col++;
				}
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
					panelFound.hide();
					panelFound._wrapper.remove();
					this.append(panelFound);
					panelFound._restore.removeAttr("panelId");
					//$("body").append(panelFound._restore);
				}
				this._panels.splice(found,1);
				this.update();
			}
		};
		this.createPanel = function(target,aOption){
			// Allready initialised ?
			if (typeof target.attr("panelId") != 'undefined'){
				var panel = this.getPanel(target.attr("panelId"));
				//panel._wrapper.show();
				return panel;
			}
			var option = {
	            class			: 'panel-default'
	        };
			if (typeof aOption !== 'undefined'){
				option = $.extend(option, aOption);
			}
			this._panelId++;
			target._wrapper = $('<div></div>');
			target.panelId 	= this._panelId;
			target.attr("panelId",target.panelId);
			// If no title asked, try to get the title allready set in the element 
			if (typeof option.title == 'undefined') {
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
					var buttons = $('<span class="pull-right"></span>');
						var close = $('<a href="#" onClick="$._panelFrame.removePanel('+target.panelId+')" class="pull-right"></a>');
						close.html('<em class="fa fa-times"></em>');
						buttons.append(close);
						var reload = $('<a href="#" onClick="$._panelFrame.getPanel('+target.panelId+').reload()" class="pull-right"></a>');
						reload.html('<em class="fa fa-reload"></em>');
						buttons.append(reload);
					target.heading.append(buttons);
			target._wrapper.append(target.heading);
					var body = $('<div class="panel-body"></div>');
					body.append(target);
					//target.height("80%");
					//target.width("100%");
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
		    if (typeof option.url !== "undefined"){
		    	target.url = option.url;
		    	target.reload();
		    }
		    this.update();
		    return target;
		};
		return this;
	};
});