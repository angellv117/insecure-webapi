
/*use webapps;*/

create table if not exists Usuario(
	id int AUTO_INCREMENT, 
	uname varchar(50) not null,
	email varchar(250) not null,
	password varchar(250) not null,
	PRIMARY KEY (id)
)ENGINE=InnoDB;

create table if not exists AccesoToken(
	id_Usuario int primary key, 
	token varchar(250) not null,
	fecha datetime not null
)ENGINE=InnoDB;

create table if not exists Imagen(
	id int AUTO_INCREMENT, 
	name varchar(250) not null,
	ruta text not null,
	id_Usuario int not null,
	PRIMARY KEY (id)
)ENGINE=InnoDB;

create table if not exists Historial(
	id int AUTO_INCREMENT, 
	id_Usuario int not null,
	accion varchar(250) not null,
	fecha datetime not null,
	PRIMARY KEY (id)
)ENGINE=InnoDB;


ALTER TABLE Historial 
ADD CONSTRAINT FK_His_U
FOREIGN KEY (id_Usuario) REFERENCES Usuario(ID); 

ALTER TABLE Usuario
ADD CONSTRAINT U_name
Unique (uname),
ADD CONSTRAINT U_email
Unique (email);

ALTER TABLE AccesoToken 
ADD CONSTRAINT FK_AT_U
FOREIGN KEY (id_Usuario) REFERENCES Usuario(ID); 

ALTER TABLE Imagen 
ADD CONSTRAINT FK_I_U
FOREIGN KEY (id_Usuario) REFERENCES Usuario(ID),
ADD CONSTRAINT U_Name_U
UNIQUE (name,id_Usuario);
