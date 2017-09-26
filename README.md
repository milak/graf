# graf
Graphic Rendering Architecture Framework

## How to install

### Install in your web server

 - clone the repository and link WebContent in your Apache or Nginx web server.

 - create a mysql database

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

### Use as docker image :
```shell
docker pull milak/graf
docker run -e GRAF_DB_USER=_user_ -e GRAF_DB_PASSWORD=_password_ -e GRAF_DB_INSTANCE=_instancename_ -e GRAF_DB_HOST=_db host_ milak/graf
```