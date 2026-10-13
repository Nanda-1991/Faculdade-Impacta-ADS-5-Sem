
USE EstudoWeb;
GO

CREATE TABLE Eleitores (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Nome VARCHAR(150) NOT NULL,
    CPF VARCHAR(14) NOT NULL,
    DataCadastro DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    Votou BIT NOT NULL DEFAULT 1,
    Matricula VARCHAR(20) NOT NULL
        DEFAULT (
            'ELE' +
            RIGHT(
                '00000' + CAST(NEXT VALUE FOR SeqMatricula AS VARCHAR(5)),
                5
            )
        ),
)

USE EstudoWeb;

Select * from [dbo].[Eleitores]