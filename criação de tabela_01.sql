CREATE DATABASE EstudoWeb;
GO

USE EstudoWeb;
GO

CREATE TABLE Usuarios (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Nome VARCHAR(100) NOT NULL,
    Email VARCHAR(150) NOT NULL,
    Foto VARBINARY(MAX) NULL, 
    Matricula VARCHAR(20) NULL,
    CPF VARCHAR(14) NULL
    )


USE EstudoWeb;

    Select * from Usuarios

    Go