# graf
Graphic Rendering Architecture Framework

## How to install

### Install in your web server

 - clone the repository and link WebContent in your Apache or Nginx web server.

 - create a mysql database

 - write the configuration file /home/graf/configuration.json :

  {
  	"db" : {
  		"host"     : "host",
  		"user"     : "user",
  		"password" : "password",
  		"instance" : "instance"
  	}
  }


### Use as docker image :
docker pull milak/graf
docker run -e GRAF_DB_USER=user -e GRAF_DB_PASSWORD=password -e GRAF_DB_INSTANCE=instancename -e GRAF_DB_HOST=db host milak/graf