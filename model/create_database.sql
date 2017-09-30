create database carto;
use carto;
create table area (
    id        INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code      VARCHAR(20),
    name      VARCHAR(100),
    parent_id INT,
    position  INT,
    view_id   INT NOT NULL,
    display   VARCHAR(20)
);

ALTER TABLE `area` ADD CONSTRAINT `FK_area_area` FOREIGN KEY (`parent_id`) 	REFERENCES `area`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `area` ADD CONSTRAINT `FK_area_view` FOREIGN KEY (`view_id`) 	REFERENCES `view`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;


create table view (
    id        INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(100) NOT NULL
);

create table node_type (
    id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20),
    name        VARCHAR(100),
    class       VARCHAR(100);
);

insert into node_type (code,name) values ("DB","Base de données");
insert into node_type (code,name) values ("PROXY","Proxy");
insert into node_type (code,name) values ("STORE","Stockage");
insert into node_type (code,name) values ("OTHER","Autre");
insert into node_type (code,name) values ("WEB","Web");

create table node (
    id            INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100),
    description   VARCHAR(200),
    area_id       INT,
    node_type_id  INT
);


create table machine (
    id        INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fqdn      VARCHAR(100),
    ip        VARCHAR(100),
    alias     VARCHAR(100),
    node_id   INT
);

create table tag (
    id        INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    value     VARCHAR(100)
);

create table node_has_tag (
    node_id    INT,
    tag_id     INT
);

create table service (
    id        	INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code      	VARCHAR(20),
    name      	VARCHAR(100),
    domain_id	INT
);

ALTER TABLE `service` ADD CONSTRAINT `FK_service_domain` FOREIGN KEY (`domain_id`) REFERENCES `domain`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

create table service_has_tag (
    service_id INT,
    tag_id     INT
);

ALTER TABLE `service_has_tag` ADD UNIQUE `PK_service_has_tag` (`service_id`, `tag_id`);
ALTER TABLE `service_has_tag` ADD CONSTRAINT `FK_service_has_tag_service`	FOREIGN KEY (`service_id`) 	REFERENCES `service`(`id`) 	ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `service_has_tag` ADD CONSTRAINT `FK_service_has_tag_tag` 		FOREIGN KEY (`tag_id`) 		REFERENCES `tag`(`id`) 		ON DELETE RESTRICT ON UPDATE RESTRICT;

create table component (
	id				 INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name			 VARCHAR(100),
	software_id  	 INT,
	data_id			 INT,
	service_id		 INT,
	device_id		 INT
);

create table service_needs_component (
    service_id 		 INT NOT NULL,
    component_id     INT NOT NULL
);
ALTER TABLE `service_needs_component` ADD UNIQUE `PK_service_needs_component` (`service_id`, `component_id`);
ALTER TABLE `service_needs_component` ADD CONSTRAINT `FK_service_needs_component_service` 	FOREIGN KEY (`service_id`) 		REFERENCES `service`(`id`) 		ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `service_needs_component` ADD CONSTRAINT `FK_service_needs_component_component` FOREIGN KEY (`component_id`) 	REFERENCES `component`(`id`) 	ON DELETE RESTRICT ON UPDATE RESTRICT;

create table instance (
	id				INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name			VARCHAR(100),
	service_id		INT,
	environment_id	INT
);
ALTER TABLE `instance` ADD CONSTRAINT `FK_instance_service` 	FOREIGN KEY (`service_id`) 		REFERENCES `service`(`id`) 		ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `instance` ADD CONSTRAINT `FK_instance_environment` FOREIGN KEY (`environment_id`) 	REFERENCES `environment`(`id`) 	ON DELETE RESTRICT ON UPDATE RESTRICT;

create table environment (
    id     INT 			NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code   VARCHAR(10) 	NOT NULL,
    name   VARCHAR(50) 	NOT NULL
);

create table service_uses_node (
    service_id         INT,
    node_id            INT,
    environment_id     INT
);

create table domain (
    id           INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100),
    area_id      INT
);
ALTER TABLE `domain` ADD CONSTRAINT `FK_domain_area` FOREIGN KEY (`area_id`) REFERENCES `area`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

create table process (
    id           INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100),
    description  VARCHAR(1000),
    domain_id	 INT
);

create table step_type (
    id           INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL
);

insert into step_type (name) values ("START");
insert into step_type (name) values ("SERVICE");
insert into step_type (name) values ("ACTOR");
insert into step_type (name) values ("SUB-PROCESS");
insert into step_type (name) values ("CHOICE");
insert into step_type (name) values ("END");

create table process_step (
    id           		INT 			NOT NULL AUTO_INCREMENT PRIMARY KEY,
    process_id          INT 			NOT NULL,
    name                VARCHAR(100) 	NOT NULL,
    step_type_id  		INT 			NOT NULL,
    service_id          INT,
    actor_id            INT,
    sub_process_id      INT
);

ALTER TABLE `process_step` ADD CONSTRAINT `FK_process_step_process` 		FOREIGN KEY (`process_id`) 		REFERENCES `process`(`id`) 		ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `process_step` ADD CONSTRAINT `FK_process_step_step_type` 		FOREIGN KEY (`step_type_id`) 	REFERENCES `step_type`(`id`)	ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `process_step` ADD CONSTRAINT `FK_process_step_service` 		FOREIGN KEY (`service_id`) 		REFERENCES `service`(`id`) 		ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `process_step` ADD CONSTRAINT `FK_process_step_actor` 			FOREIGN KEY (`actor_id`) 		REFERENCES `actor`(`id`) 		ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `process_step` ADD CONSTRAINT `FK_process_step_sub_process` 	FOREIGN KEY (`sub_process_id`) 	REFERENCES `process`(`id`) 		ON DELETE RESTRICT ON UPDATE RESTRICT;

create table step_link (
    process_id       INT NOT NULL,
    from_step_id  	 INT NOT NULL,
    to_step_id		 INT NOT NULL,
    label		 	VARCHAR(50),
    data		 	VARCHAR(50)
);

ALTER TABLE `step_link` ADD CONSTRAINT `FK_step_link_process` 				FOREIGN KEY (`process_id`) 		REFERENCES `process`(`id`) 		ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `step_link` ADD CONSTRAINT `FK_step_link_from_process_step` 	FOREIGN KEY (`from_step_id`) 	REFERENCES `process_step`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `step_link` ADD CONSTRAINT `FK_step_link_to_process_step` 		FOREIGN KEY (`to_step_id`) 		REFERENCES `process_step`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

create table actor (
	id           		INT 			NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name				VARCHAR(50)     NOT NULL,
	domain_id			INT				NOT NULL
);

ALTER TABLE `actor` ADD CONSTRAINT `FK_actor_domain` 	FOREIGN KEY (`domain_id`) 	REFERENCES `domain`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;