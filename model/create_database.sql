create database carto;
use carto;
create table area (
    id        INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code      VARCHAR(20),
    name      VARCHAR(100),
    parent_id INT,
    position  INT,
    view_id   INT,
    display   VARCHAR(20)
);

create table view (
    id        INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(100)
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
    id        INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code      VARCHAR(20),
    name      VARCHAR(100)
);


create table service_has_tag (
    service_id INT,
    tag_id     INT
);

create table environment (
    id     INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code   VARCHAR(10),
    name   VARCHAR(50)
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

create table process (
    id           INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100),
    description  VARCHAR(1000),
    domain_id	 INT
);

create table step_type (
    id           INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100)
);

insert into step_type (name) values ("START");
insert into step_type (name) values ("SERVICE");
insert into step_type (name) values ("ACTOR");
insert into step_type (name) values ("SUB-PROCESS");
insert into step_type (name) values ("CHOICE");
insert into step_type (name) values ("END");

create table process_step (
    id           	INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    process_id          INT,
    name                VARCHAR(100),
    step_type_id  	INT,
    service_id          INT,
    actor_id            INT,
    sub_process_id      INT
);

create table step_link (
    process_id           INT,
    from_step_id  	 INT,
    to_step_id		 INT,
    label		 VARCHAR(50),
    data		 VARCHAR(50)
);