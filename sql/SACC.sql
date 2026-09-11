CREATE DATABASE SACC;
USE SACC;

-- Tabela de administração
CREATE TABLE Administracao (
    id_admin INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(45) NOT NULL,
    senha VARCHAR(255) NOT NULL
);

-- Contatos
CREATE TABLE Contatos (
    id_contatos INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    telefone VARCHAR(15) NOT NULL,
    email VARCHAR(45) NOT NULL
);

-- Jurados
CREATE TABLE Jurados (
    id_jurados INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(45) NOT NULL,
    usuario VARCHAR(45) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) NOT NULL,
    id_contatos INT NULL
);

-- Escolas
CREATE TABLE Escolas (
    id_escolas INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(45) NOT NULL,
    focalizada VARCHAR(45) NULL,
    ide VARCHAR(45) NULL,
    ideb DECIMAL(6,1) NULL, 
    municipio VARCHAR(45) NOT NULL
);

-- Trabalhos
CREATE TABLE Trabalhos (
    id_trabalhos INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    id_escolas INT NULL,
    id_jurados INT NULL,
    id_areas INT NULL,
    id_categoria INT NULL
);

-- Notas
CREATE TABLE Avaliacoes (
    id_avaliacao INT AUTO_INCREMENT PRIMARY KEY,
    id_trabalho INT NOT NULL,
    id_jurado INT NOT NULL,
    criterio INT NOT NULL,
    nota DECIMAL(4,2) NOT NULL,
    comentario TEXT,
    data_avaliacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_trabalho) REFERENCES Trabalhos(id_trabalhos),
    FOREIGN KEY (id_jurado) REFERENCES Jurados(id_jurados)
);

-- Categorias
CREATE TABLE Categorias (
    id_categoria INT PRIMARY KEY,
    nome_categoria VARCHAR(100) NOT NULL
);
-- Areas
CREATE TABLE Areas (
    id_area INT PRIMARY KEY,
    nome_area VARCHAR(100) NOT NULL
);

-- Relacionamento de Jurados com Categorias e Áreas
CREATE TABLE Jurados_Categorias_Areas (
    id_jurados_categorias_areas INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_jurados INT NOT NULL,
    id_categoria INT NULL,
    id_area INT NULL,
    FOREIGN KEY (id_jurados) REFERENCES Jurados(id_jurados) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_categoria) REFERENCES Categorias(id_categoria) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_area) REFERENCES Areas(id_area) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Jurado_Trabalho (
    id_jurado_trabalho INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_jurado INT NOT NULL,
    id_trabalho INT NOT NULL,
    UNIQUE KEY unq_jurado_trabalho (id_jurado, id_trabalho),
    FOREIGN KEY (id_jurado) REFERENCES Jurados(id_jurados) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_trabalho) REFERENCES Trabalhos(id_trabalhos) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Categorias_Areas (
    id_categorias_areas INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria INT NOT NULL,
    id_area INT NOT NULL,
    FOREIGN KEY (id_categoria) REFERENCES Categorias(id_categoria) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_area) REFERENCES Areas(id_area) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Inserindo valores fixos
INSERT INTO Categorias (id_categoria, nome_categoria) VALUES
(1, 'Ensino Médio'),
(2, 'Ensino Médio - Ações Afirmativas e CEJAs EM'),
(3, 'Pesquisa Júnior'),
(4, 'PcD');

INSERT INTO Areas (id_area, nome_area) VALUES
(1, 'Linguagens, Códigos e suas Tecnologias - LC'),
(2, 'Matemática e suas Tecnologias - MT'),
(3, 'Ciências da Natureza, Educação Ambiental e Engenharias - CN'),
(4, 'Ciências Humanas e Sociais Aplicadas - CH'),
(5, 'Robótica, Automação e Aplicação das TIC'),
(6, 'Ensino Fundamental'),
(7, 'Ensino Médio');

-- Inserindo relacionamento Categorias x Áreas
-- Para categorias 1 e 2 (Ensino Médio e Ações Afirmativas), vinculamos áreas 1 a 5
INSERT INTO Categorias_Areas (id_categoria, id_area) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5);

-- Pesquisa Júnior (3) não recebe nenhuma área (nenhuma linha inserida)

-- PcD (4) recebe só as áreas Ensino Fundamental (6) e Ensino Médio (7)
INSERT INTO Categorias_Areas (id_categoria, id_area) VALUES
(4, 6),
(4, 7);

-- Inserindo um usuário padrão para administração
INSERT INTO Administracao (usuario, senha) VALUES
('Crede07', 'cr701201'); 

-- Relacionamentos
ALTER TABLE Jurados 
ADD CONSTRAINT fk_jurados_contatos 
FOREIGN KEY (id_contatos) 
REFERENCES Contatos(id_contatos) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

ALTER TABLE Trabalhos
ADD CONSTRAINT fk_trabalhos_escolas
FOREIGN KEY (id_escolas)
REFERENCES Escolas(id_escolas)
ON DELETE SET NULL
ON UPDATE CASCADE;


ALTER TABLE Trabalhos
ADD CONSTRAINT fk_trabalhos_area
FOREIGN KEY (id_areas)
REFERENCES Areas(id_area)
ON DELETE SET NULL
ON UPDATE CASCADE;

ALTER TABLE Trabalhos
ADD CONSTRAINT fk_trabalhos_categoria
FOREIGN KEY (id_categoria)
REFERENCES Categorias(id_categoria)
ON DELETE SET NULL
ON UPDATE CASCADE;

ALTER TABLE Jurados_Categorias_Areas
ADD CONSTRAINT fk_jurados_categorias_areas_jurados
FOREIGN KEY (id_jurados)
REFERENCES Jurados(id_jurados)
ON DELETE CASCADE
ON UPDATE CASCADE;

ALTER TABLE Jurados_Categorias_Areas
ADD CONSTRAINT fk_jurados_categorias_areas_categorias
FOREIGN KEY (id_categoria)
REFERENCES Categorias(id_categoria)
ON DELETE CASCADE
ON UPDATE CASCADE;

ALTER TABLE Jurados_Categorias_Areas
ADD CONSTRAINT fk_jurados_categorias_areas_areas
FOREIGN KEY (id_area)
REFERENCES Areas(id_area)
ON DELETE CASCADE
ON UPDATE CASCADE;

SELECT 
id_jurados,
GROUP_CONCAT(id_categoria ORDER BY id_categoria SEPARATOR ', ') AS categorias,
GROUP_CONCAT(id_area ORDER BY id_area SEPARATOR ', ') AS areas
FROM Jurados_Categorias_Areas
GROUP BY id_jurados;
