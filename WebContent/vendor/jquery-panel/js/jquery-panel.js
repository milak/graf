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
			if (this._maximized != null) {
				this._maximized._wrapper.css("width","100%");
				this._maximized._wrapper.css("height","100%");
				this.append(this._maximized._wrapper);
				try{
					this._maximized._onUpdate();
				}catch(exception){}
			} else if (count == 0){
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
							panel._wrapper.css("width",""+(colCount * colWidth)+"%");
						} else {
							panel._wrapper.css("width",""+colWidth+"%");
						}
					} else {
						panel._wrapper.css("width",""+colWidth+"%");
					}
					panel._wrapper.css("height","100%");
					divRow.append(panel._wrapper);
					try{
						panel._onUpdate();
					}catch(exception){}
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
					if (typeof panel._onClose !== "undefined") {
	    				try{
	    					var result = panel._onClose();
	    					if ((result !== "undefined") && (result === false)){
	    						return;
	    					}
	    				} catch(exception){
	    					console.log("jquery-panel - exception");
	    					console.log(exception);
	    				}
					}
					panelFound = panel;
					found = index;
				}
				index++;
			});
			if (found != -1){
				if (typeof panelFound._restore !== "undefined"){
					panelFound.hide();
					this.append(panelFound);
					panelFound._restore.removeAttr("panelId");
				}
				panelFound._wrapper.remove();
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
	            class		: 'panel-default',
	            buttons		: ['close','reload','maximize'] 
	        };
			if (typeof aOption !== 'undefined'){
				option = $.extend(option, aOption);
			}
			this._panelId++;
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
			target.show();
			// Création 
			target._wrapper = $('<div></div>');
			target._wrapper.attr('class', "panel "+option.class);
				var heading = $('<div class="panel-heading"></div>');
					var titlePane = $('<span>'+option.title+'</span>');
					heading.append(titlePane);
					var buttons = $('<span style="float:right"></span>');
						/*if (typeof target.url !== "undefined"){
							buttons.append($('<a href="#" style="margin-right:5px;color:black" 	onClick="$._panelFrame.getPanel('+target.panelId+').reload()">R</a>'));
				    	}*/
						target._maximize = $('<a href="#" style="margin-right:5px;color:black" 	onClick="$._panelFrame.getPanel('+target.panelId+').maximize()"><img src="images/64.png"></img></a>');
						buttons.append(target._maximize);
						target._normalize = $('<a href="#" style="margin-right:5px;color:black" 	onClick="$._panelFrame.getPanel('+target.panelId+').normal()"><img src="images/14.png"></img></a>');
						target._normalize.hide();
						buttons.append(target._normalize);
						buttons.append($('<a href="#" style="margin-right:5px;color:black"	onClick="$._panelFrame.getPanel('+target.panelId+').close()"><img src="images/33.png"></img></a>'));
					heading.append(buttons);
			target._wrapper.append(heading);
				var body = $('<div class="panel-body"></div>');
					body.append(target);
			target._wrapper.append(body);
		    this._panels.push(target);
		    // Ajout des gestion d'evènements
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
			} else {
				target._onUpdate = function(){};
			}
		    // Ajout de fonctions standard
		    target.title = function(text){
		    	titlePane.text(text);
		    	return this;
		    };
		    target.close = function(func){
		    	if (typeof func === "undefined"){
		    		$._panelFrame.removePanel(this.panelId);
		    	} else {
		    		this._onClose = func;
		    	}
		    };
		    target._state = "normal";
		    target.toggleMaxNormal = function(url){
		    	if (this._state == "normal"){
		    		this.maximize();
		    	} else {
		    		this.normal();
		    	}
		    };
		    target.maximize = function(url){
		    	if (this._state == "normal"){
		    		$._panelFrame._maximized = this;
		    		$._panelFrame.update();
		    		target._state = "maximized";
		    		target._normalize.show();
		    		target._maximize.hide();
		    	}
		    };
		    target.normal = function(url){
		    	if (this._state == "maximized"){
		    		$._panelFrame._maximized = null;
		    		target._state = "normal";
		    		$._panelFrame.update();
		    		target._normalize.hide();
		    		target._maximize.show();
		    	}
		    };
		    target.reload = function(url){
		    	if (typeof url !== "undefined"){
		    		target.url = url;
		    	}
		    	if (typeof target.url === "undefined"){
		    		return;
		    	}
		    	target.html("");
		    	$.ajax({
		    		url: target.url
	    		}).done(function(data) {
	    			target.html(data);
	    			if (typeof target._onDone !== "undefined") {
	    				try{
	    					target._onDone();
	    				} catch(exception){
	    					console.log("jquery-panel - exception");
	    					console.log(exception);
	    				}
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