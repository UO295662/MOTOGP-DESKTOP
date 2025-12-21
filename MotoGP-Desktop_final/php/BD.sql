CREATE DATABASE IF NOT EXISTS UO295662_DB
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE UO295662_DB;

CREATE TABLE IF NOT EXISTS participantes (
    id_participante INT AUTO_INCREMENT PRIMARY KEY,
    codigo_usuario VARCHAR(50) NOT NULL UNIQUE,
    edad INT,
    genero VARCHAR(20),
    pericia_informatica INT CHECK (pericia_informatica BETWEEN 0 AND 10) 
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pruebas (
    id_prueba INT AUTO_INCREMENT PRIMARY KEY,
    id_participante INT NOT NULL,
    dispositivo VARCHAR(50) ,
    tiempo_empleado DECIMAL(10, 2) ,
    completado BOOLEAN NOT NULL ,
    comentarios_usuario TEXT,
    propuestas_mejora TEXT,
    valoracion_aplicacion INT CHECK (valoracion_aplicacion BETWEEN 0 AND 10),
    fecha_prueba DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_prueba_participante
        FOREIGN KEY (id_participante) 
        REFERENCES participantes(id_participante)
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS observaciones (
    id_observacion INT AUTO_INCREMENT PRIMARY KEY,
    id_prueba INT NOT NULL,
    comentario_facilitador TEXT NOT NULL,
    
    CONSTRAINT fk_observacion_prueba
        FOREIGN KEY (id_prueba)
        REFERENCES pruebas(id_prueba)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;