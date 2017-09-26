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

```json
{
	"db" : {
		"host"     : "host",
		"user"     : "user",
		"password" : "password",
		"instance" : "instance"
	}
}
```

### Or use as docker image :
```shell
docker pull milak/graf
docker run -e GRAF_DB_USER=_user_ -e GRAF_DB_PASSWORD=_password_ -e GRAF_DB_INSTANCE=_instancename_ -e GRAF_DB_HOST=_db host_ milak/graf
```
![Image of Yaktocat](https://raw.githubusercontent.com/milak/graf/master/model/mpd.html)
