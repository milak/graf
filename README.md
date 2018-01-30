# graf

Graphic Rendering Architecture Framework

## What is it ?

Graf aims to provide an architecte's tool to render all TOGAF architecte's diagrams. The main important feature given by this tool is that it can be based on a standalone database or a CMDB (only ITop for the moment). The advantages :
  * all components used in the diagrams are real life components described in the CMDB (Server, Database, Software application, etc.)
  * all the components stored in the CMDB can be linked to Business Domains and Strategic designs
  * Graf uses standards for the description of the diagrams : BPMN, TOSCA, SVG, etc.
  * Graf provides permalink Diagrams to be inserted in Architect documents.

## What is the status of this program ?

Graf is under active development. It cannot be used for production at now.

## Features list

Here is the list of allready available features :

  * Use of plugable DAO (Data Access Object) to define a link to Itop or Standalone Database. New DAO could be developped for other CMDB.
  * First DAO implementation : ITop (standalone Database must be rebuilt)
  * Items :
    * **Business Domains** : can contain any item
    * **Actors** : represents user roles
    * **Services** : main services offered by the Information System
    * **Business Processes** : based on BPMN (not yet stable) can reuse any item
    * **Software Solution** : based on TOSCA Yaml format to describe logic and technical structure of a solution and all environments (developement, production, test, etc.)
    * **Data** : (under construction) based on a standard format (to find) will describe all the main Business Data Models and the use maid in the Software Solution
    * **Server, Softwares, PC, etc.** : all real life components can be used int the diagrams to describe the solutions
  * Views :
    * all the views are based on templates described in JSON and can be customized
    * **Strategic View** : describes all the domains
    * **Business View** : describes all the Actors, Process, Service and Data linked to a Business Domain or an Actor
    * **Process View** : the view of BPMN process
    * **Logic View** : the view of any Software Solution based on TOSCA format
    * **Technical View** (not yet implemented) : the technical view of Software Solution : Sites, Machines, etc.
  * Generated documents (not yet started) :
    * Will generate architect documents containing all necessary diagrams and all the description

## How to install ?

You can install GRAF as a web application in your webserver or as docker image. But, in each case, you have to install mysql database.

### Install Mysql database

 - create a mysql database
 
 - run model/create_database.sql script

For more information about the data model see mpd.html file

### Install in your web server

 - clone the repository and link WebContent in your Apache or Nginx web server.

 - write the configuration file /home/graf/configuration.json :

For standalone usage
```json
{
	"dao" : "db",
	"db" : {
		"host"     : "mydb.com",
		"login"    : "john",
		"password" : "mypassord",
		"instance" : "graf"
	}
}
```
For ITOP based usage
```json
{
	"dao" : "itop",
	"itop" : {
		"url"     		: "http://myitop.com/webservices/rest.php",
		"login"    		: "john",
		"password"     	: "mypassord",
		"organisation" 	: "myenterprise",
		"version" 		: "1.3"
	}
}
```

### Or use as docker image :
For standalone usage
```shell
docker pull milak/graf
docker run -e DAO=db -e DB_LOGIN=_LOGIN_ -e DB_PASSWORD=_password_ -e DB_INSTANCE=_instancename_ -e DB_HOST=_db host_ milak/graf
```
For ITOP based usage
```shell
docker pull milak/graf
sudo docker run -e "DAO=itop" -e "ITOP_LOGIN=admin" -e "ITOP_PASSWORD=admin" -e "ITOP_VERSION=1.3" -e "ITOP_ORGANISATION=Demo" -e "ITOP_URL=http://localhost/itop/webservices/rest.php" milak/graf
```

## References :

  * TOSCA : <http://docs.oasis-open.org/tosca/TOSCA-Simple-Profile-YAML/v1.0/TOSCA-Simple-Profile-YAML-v1.0.html>
  * TOGAF : <http://www.opengroup.org/subjectareas/enterprise/togaf>
  * SVG : <https://www.w3.org/TR/SVG2>
  * BPMN : <http://www.omg.org/spec/BPMN>
  * Itop : <https://www.combodo.com/itop>
  * CMDB : <https://en.wikipedia.org/wiki/Configuration_management_database>