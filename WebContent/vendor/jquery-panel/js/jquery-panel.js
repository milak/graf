/**
 * Author : Milak
 * Usage :
 *
 * Create a panel
 * $.panel({
 *	"class" : 'panel-green',
 *	"close" : function (){
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
 *	"title"   : 'Panel's title',
 * 	"class"   : 'panel-blue',
 *	"url"	  : 'stat.html',
 *	"buttons" : ['reload','maximize','normal','close'],
 *	"close"   : function (){
 *		alert("Panel closed");
 *	}
 * });
 * 
 * Use an existing div
 * $("#myPanel").panel({
 *		"title" : 'Panel's title'
 * }).close(function(){alert('Goodbye')});
 * 
 */
$(function () {
	/**
	 * Add simple function to $ environment
	 * Arguments : see $.fn.panel
	 * Exemple : $.panel(options);
	 */
	$.panel = function(options){
		if ($._panelFrame === undefined){
			$("body").panelFrame();
		}
		return $._panelFrame.createPanel($('<div></div>'),options);
	};
	/**
	 * Add panel function to selector
	 * Arguments : 
	 * {
	 * 		title   : '', // the title of the panel
	 * 		close   : function(){}, // function called when close has been asked, if the function returns false, the panel will not be closed, if it returns true or nothing, the function will be closed
	 * 		url	    : '', // the url of the content that will be loaded, when loaded, done function will be called
	 * 		done    : function(){}, // function called when url is loaded
	 * 		update  : function(){}, // function called when the panel has been redrawn by the panelFrame
	 * 		buttons : ['reload','maximize','normal','close'], // buttons that will be added in the panel's title
	 * 		footer	: the footer to display in the bottom of the panel
	 * }
	 * Returns : a panel with functions :
	 * 		title("title")     			: change the title of the panel
	 * 		html("content")    			: change the body of the panel
	 * 		footer("content")  			: change the footer of the panel
	 * 		reload(["newurl"]) 			: reload the url of the panel. if new url is given, the url is changed - done will be loaded
	 * 		state(["normal|maximized"])	: change the state of the panel. If no new state is given, simply returns its state
	 * 		close([function(){}])		: close the panel or set the function to call when the panel is closed
	 * 		update(function(){})		: set the function to call when the panel is updated
	 * Example : $("#mydiv").panel(options); 
	 */
	$.fn.panel = function(options){
		if ($._panelFrame === undefined){
			$("body").panelFrame();
		}
		var result = $._panelFrame.createPanel(this,options);
		result._restore = this;
		return result;
	};
	/**
	 * Add panelFrame function to selector
	 * Arguments : N/A
	 * Example : $("#mydiv").panelFrame(); 
	 */
	$.fn.panelFrame = function(){
		/*for (var i = 0; i < 5; i++){
			var div = $('<div style="display:inline-flex;width:100%;height:0px"></div>');
			div.hide();
			this.rows.push(div);
			this.append(div);
		}*/
		$._panelFrame 	= this;
		this.sortable();
		this.attr('class', "panelFrame");
		this._panelId 	= 0;
		this._maximized = null;
		this._panels 	= new Array();
		this.addPanel 	= function (panel){
			try{
				this._panels.push(panel);
				this.append(panel._wrapper);
				var count = this._panels.length;
				var nbRow = 1;
		        var nbCol = count;
		        while (nbCol > (nbRow + 1)) {
		            nbRow++;
		            nbCol = Math.ceil(count / nbRow);
		        }
				this.css("grid-template-columns","repeat("+nbRow+", 1fr)");
				for (var p = 0; p < this._panels.length; p++){
		        	try{
		        		this._panels[p]._onUpdate();
					}catch(exception){}
		        }
				/*
				var count = this._panels.length;
				var nbRow = 1;
		        var nbCol = count;
		        while (nbCol > (nbRow + 1)) {
		            nbRow++;
		            nbCol = Math.ceil(count / nbRow);
		        }
		        var row;
		        var panelFrame = this[0];
		        var children = this.children(".panelRow");
		        console.log(children);
		        if (children.length < nbRow){
		        	row = $('<div class="panelRow"></div>');
					this.append(row);
					children = this.children(".panelRow");
		        } else {
		        	row = children[nbRow-1];
		        }
		        var found = false;
		        for (var i = 0; i < children.length; i++){
		        	var r = children[i];
		        	if (r.childElementCount < nbCol){
		        		$(r).append(panel._wrapper);
		        		found = true;
		        		break;
		        	}
		        }
		        for (var p = 0; p < this._panels.length; p++){
		        	this._panels[p].css("height","100%");
		        }
		        if (!found){
		        	console.log("WARNING - no row found");
		        }*/
			}catch(exception){
				console.log("WARNING - panelFrame.addPanel() : " + exception);
			}
		},
		this.update 	= function (){
		},
		this.updateOLD 	= function (){
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
		/**
		 * Private function used for title's buttons
		 * TODO see how to set them private
		 */
		this.getPanel = function (panelId){
			var result = null;
			this._panels.forEach(function(panel){
				if (panel.panelId == panelId){
					result = panel;
				}
			});
			return result;
		};
		/**
		 * Private function used for remove of panel
		 * TODO see how to set them private
		 */
		this.removePanel = function (panelId){
			var index = 0;
			var panelFound = null;
			var found = -1;
			this._panels.forEach(function(panel){
				if (panel.panelId == panelId){
    				try{
    					var result = panel._onClose();
    					if ((result !== "undefined") && (result === false)){
    						return;
    					}
    				} catch(exception){
    					console.log("jquery-panel - exception");
    					console.log(exception);
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
				//var parent = panelFound._wrapper[0].parentElement;
				panelFound._wrapper.remove();
				/*console.log("After remove : " + parent.childElementCount);
				console.log(parent);
				if (parent.childElementCount == 0){
					parent.remove();
				}*/
				this._panels.splice(found,1);
				if (this._maximized == panelFound){
					this._maximized = null;
				}
				this.update();
			}
		};
		/**
		 * Private function used for creation of panel
		 * TODO see how to set them private
		 */
		this.createPanel = function(target,aOption){
			// Allready initialised ?
			if (typeof target.attr("panelId") != 'undefined'){
				var panel = this.getPanel(target.attr("panelId"));
				//panel._wrapper.show();
				return panel;
			}
			var option = {
	            class		: 'panel-default',
	            buttons		: ['reload','maximize','normal','close'] 
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
					for (var i = 0; i < option.buttons.length; i++){
						var buttonName = option.buttons[i];
						if (buttonName == "reload") {
							if (typeof option.url !== "undefined"){
								buttons.append($('<a href="#" style="margin-right:5px;color:black" 	title="Reload content" onClick="$._panelFrame.getPanel('+target.panelId+').reload()"><img src="images/42a.png"></img></a>'));
							}
						} else if (buttonName == "maximize") {
							target._maximize = $('<a href="#" style="margin-right:5px;color:black" 	title="Maximize panel" onClick="$._panelFrame.getPanel('+target.panelId+').state(\'maximized\')"><img src="images/64.png"></img></a>');
							buttons.append(target._maximize);
						} else if (buttonName == "normal") {
							target._normalize = $('<a href="#" style="margin-right:5px;color:black"	title="Set normal state" onClick="$._panelFrame.getPanel('+target.panelId+').state(\'normal\')"><img src="images/14.png"></img></a>');
							target._normalize.hide();
							buttons.append(target._normalize);
						} else if (buttonName == "close") {
							buttons.append($('<a href="#" style="margin-right:5px;color:black"	title="Close panel" onClick="$._panelFrame.getPanel('+target.panelId+').close()"><img src="images/33.png"></img></a>'));
						} else {
							buttons.append($('<a href="#" style="margin-right:5px;color:black"	onClick="">Unsupported button name : '+buttonName+'</a>'));
						}
					}
					heading.append(buttons);
			target._wrapper.append(heading);
				var body = $('<div class="panel-body"></div>');
					body.append(target);
			target._wrapper.append(body);
			// If a footer is set
			if (typeof option.footer != 'undefined') {
				var footer = $('<div class="panel-footer"></div>');
				body.append(footer);
			}
		    // Ajout des gestion d'evènements
			if (typeof option.done !== "undefined"){
				target._onDone = option.done;
			} else {
				target._onDone = function(){};
			}
			if (typeof option.fail !== "undefined"){
				target._onFail = option.fail;
			} else {
				target._onFail = function(){};
			}
			if (typeof option.close !== "undefined"){
				target._onClose = option.close;
			} else {
				target._onClose = function(){return true;};
			}
			if (typeof option.update !== "undefined"){
				target._onUpdate = option.update;
			} else {
				target._onUpdate = function(){};
			}
		    // Ajout de fonctions standard
		    target.title = function(text){
		    	titlePane.text(text);
		    	return this;
		    };
		    target.update = function(func){
		    	if (typeof func !== "undefined"){
		    		this._onUpdate = func;
		    	} else {
		    		throw new Exception("Missing argument function");
		    	}
		    	return this;
		    };
		    target.close = function(func){
		    	if (typeof func === "undefined"){
		    		$._panelFrame.removePanel(this.panelId);
		    	} else {
		    		this._onClose = func;
		    	}
		    	return this;
		    };
		    target.fail = function(func){
		    	if (typeof func !== "undefined"){
		    		this._onFail = func;
		    	}
		    	return this;
		    };
		    target._state = "normal";
		    target.state = function(state){
		    	if (typeof state !== "undefined"){
		    		if (state == "maximized"){
			    		if (this._state == "normal"){
				    		$._panelFrame._maximized = this;
				    		$._panelFrame.update();
				    		target._state = "maximized";
				    		if (typeof target._normalize !== "undefined"){
				    			target._normalize.show();
				    		}
				    		if (typeof target._maximize !== "undefined"){
				    			target._maximize.hide();
				    		}
				    	}
			    	} else if (this._state == "maximized"){
			    		$._panelFrame._maximized = null;
			    		target._state = "normal";
			    		$._panelFrame.update();
			    		if (typeof target._normalize !== "undefined"){
			    			target._normalize.hide();
			    		}
			    		if (typeof target._maximize !== "undefined"){
			    			target._maximize.show();
			    		}
			    	} else {
			    		throw new Exception("Unsupported state " + state);
			    	}
		    	}
		    	return this._state;
		    };
		    target.reload = function(url){
		    	if (typeof url !== "undefined"){
		    		this.url = url;
		    	}
		    	if (typeof this.url === "undefined"){
		    		return;
		    	}
		    	if (this.url == null){
		    		this.html("");
		    		return;
		    	}
		    	$.ajax({
		    		url : this.url
	    		}).done(function(data) {
	    			target.html(data);
    				try{
    					target._onDone();
    				} catch(exception){
    					console.log("jquery-panel - exception");
    					console.log(exception);
    				}
	    		}).fail(function(jxqr,textStatus,error){
	    			target._onFail(jxqr,textStatus,error);
	    		});
		    };
		    if (typeof option.url !== "undefined"){
		    	target.url = option.url;
		    	target.reload();
		    }
		    this.addPanel(target);
		    return target;
		};
		return this;
	};
});