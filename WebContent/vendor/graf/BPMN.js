/**
 * Script permettant la manipulation d'une fichier BPMN
 */
var eventTypes = new Array();
eventTypes["startEvent"] = true;
eventTypes["userTask"] = true;
eventTypes["task"] = true;
eventTypes["endEvent"] = true;
eventTypes["sendTask"] = true;
eventTypes["parallelGateway"] = true;
eventTypes["exclusiveGateway"] = true;
eventTypes["intermediateCatchEvent"] = true;
eventTypes["boundaryEvent"] = true;
class Event {
	constructor(domObject,process,namespace) {
		this._domObject = domObject;
		this._process = process;
		this._namespace = namespace;
	}
	getId(){
		return this._domObject.attr("id");
	}
	getName(){
		return this._domObject.attr("name");
	}
	getImplementation(){
		return this._domObject.attr("implementation");
	}
	getType(){
		return this._domObject.prop("tagName").substring(this._namespace.length+1);
	}
	getLinks(direction){
		return this._process._getLinks(this.getId(),direction);
	}
}
class Link {
	constructor(name,from,to) {
		this._name = name;
		this._from = from;
		this._to = to;
	}
	getName(){
		return this._name;
	}
	getFrom(){
		return this._from;
	}
	getTo(){
		return this._to;
	}
}
class Process {
	constructor(documentElement,domObject,namespace) {
		this._documentElement = documentElement;
	    this._domObject = domObject;
	    this._namespace = namespace;
	}
	getId(){
		return this._domObject.attr("id");
	}
	getName(){
		return this._domObject.attr("name");
	}
	getType(){
		return "Process";
	}
	getLinks(direction){
		return this._getLinks(this.getId(),direction);
	}
	_getLinks(eventId,direction){
		var result = new Array();
		var ns = this._namespace;
		this._domObject.children().each(function(i,element){
			var element = $(element);
			var tagName = element.prop("tagName").substring(ns.length+1);
			if (tagName == "sequenceFlow") {
				var sourceRef = element.attr("sourceRef");
				var targetRef = element.attr("targetRef");
				var name = element.attr("name");
				if ((direction == "forward") || (direction == "both")){
					if (sourceRef == eventId) {
						result.push(new Link(name,sourceRef,targetRef));
					}
				}
				if ((direction == "backward") || (direction == "both")){
					if (targetRef == eventId) {
						result.push(new Link(name,sourceRef,targetRef));
					}
				}
			}
		});
		return result;
	}
	eachEvents(callback){
		var ns = this._namespace;
		var process = this;
		this._domObject.children().each(function(i,element){
			var element = $(element);
			var tagName = element.prop("tagName").substring(ns.length+1);
			if (tagName == "subProcess") {
				callback(new Process(element,ns));
			} else if (eventTypes.hasOwnProperty(tagName)){
				callback(new Event(element,process,ns));
			} else {
				
			}
		});
	}
	addEvent(eventType,name,implementation){
		var event = this._documentElement.createElementNS(BMPNNameSpace,eventType);
		var attribute = this._documentElement.createAttribute('name');
		attribute.value = name;
		event.setAttributeNode(attribute);
		attribute = this._documentElement.createAttribute('id');
		attribute.value = "_"+Math.random();
		event.setAttributeNode(attribute);
		attribute = this._documentElement.createAttribute('implementation');
		attribute.value = implementation;
		event.setAttributeNode(attribute);
		this._domObject.append(event);
	}
	getEvent(id){
		return new Event(this._domObject.find("*[id='"+id+"']"),this,this._namespace);
	};
}
var BMPNNameSpace = "http://www.omg.org/spec/BPMN/20100524/MODEL";
function parseBPMN(content){
	var xmlDOM;
	if (content instanceof XMLDocument){
		xmlDOM = content;
	} else {
		xmlDOM = $.parseXML(content)
	}
	var xml = $(xmlDOM);
	var ns = "bpmn\\:";
	xml.children().each(function (i,element){
		if (i == 0){
			var e = $(element);
			e.each(function() {
	        	$.each(this.attributes,function(i,a){
	            	if (a.value == BMPNNameSpace){
	            		ns = a.name.substring("xmlns:".length);
	            	}
	            })
		    })
		}
	});
	var definitions = null;
	xml.find(ns+"\\:definitions").each(function (i,element){
		if (i == 0){
			definitions = $(element);
		}
	});
	if (definitions == null){
		throw "Definitions not found";
	}
	definitions.find(ns+"\\:process").each(function (i,element){
		xml._process = $(element);
	});
	xml._xmlDOM = xmlDOM;
	xml._definitions = definitions;
	xml.serialize = function(){
		var serializer = new XMLSerializer();
		var result = serializer.serializeToString(this._xmlDOM);
		result = result.replace(/[\r\n][ \t]*[\r\n]/g,'\n');
		result = result.replace(/\/></g,'/>\n<');
		result = result.replace(/\n[ ]*(<[^>]*\/>)/g,'\n\t$1');
		return result;
	};
	xml.removeEvent = function(id){
		this._process.find("*[id='"+id+"']").remove();
	};
	xml.getProcess = function(){
		if (this._process == null){
			return null;
		} else {
			return new Process(this._xmlDOM,this._process,ns);
		}
	};
	return xml;
}
function testBPMN(){
	$.ajax({"url" : "views/toto.xml", "dsssataType" : "text/xml", "success" : function(result){
		var BPMN = parseBPMN(result);
		var process = BPMN.getProcess();
		//console.log("Process " + process.getId());
		// For each events
		process.eachEvents(function (event) {
			//console.log("Id = " + event.getId() + " name = " + event.getName() + " type = " + event.getType());
			if (event.getType())
			var links = event.getLinks("backward");
			links.forEach(function(l){
				var evt = process.getEvent(l.getFrom());
				//console.log("<- " + l.getFrom() + " ("+evt.getName()+","+evt.getType()+")");
			});
			links = event.getLinks("forward");
			links.forEach(function(l){
				var evt = process.getEvent(l.getTo());
				console.log("-> " + l.getTo() + " ("+evt.getName()+","+evt.getType()+")");
			});
			
		});
	}}).fail(function (a,x,error){
		sendMessage("error",error);
	});
}
function testBPMNOLD(){
	
	var BPMN = parseBPMN('<bpmn:definitions id="ID_1" xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"><bpmn:process id="4564789"><bpmn:startEvent name="ABCD" id="2"/><bpmn:endEvent name="EFGH" id="3"/></bpmn:process></bpmn:definitions>');
	
	var startEvent = BPMN.getEvent("2");
	//console.log("Id = " + startEvent.getId() + " name = " + startEvent.getName() + " type = " + startEvent.getType());
	// For each events
	BPMN.eachEvents(function (event) {
		console.log("Id = " + event.getId() + " name = " + event.getName() + " type = " + event.getType());
	});
	// Add event
	BPMN.addEvent('<bpmn:userTask name="IJKL" id="45"/>');
	console.log("--- Adding ---");
	// For each events
	BPMN.eachEvents(function (event) {
		console.log("Id = " + event.getId() + " name = " + event.getName() + " type = " + event.getType());
	});
	console.log("--- Removing ---");
	BPMN.removeEvent(2);
	// For each events
	BPMN.eachEvents(function (event) {
		console.log("Id = " + event.getId() + " name = " + event.getName() + " type = " + event.getType());
	});
	console.log(BPMN.serialize());
}
$.serializeXML = function(xmlDocument){
	var serializer = new XMLSerializer();
	return serializer.serializeToString(xmlDocument);
}