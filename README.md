# graf
Graphic Rendering Architecture Framework

## How to install

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
docker run -e DAO=itop -e ITOP_LOGIN=_login_ -e ITOP_PASSWORD=_password_ -e ITOP_URL=_url_ -e ITOP_VERSION=_version_ -e ITOP_ORGANISATION=_organisation_ milak/graf
```