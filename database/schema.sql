-- ============================================================
--  WhiteArt - Esquema de base de datos (Segunda Entrega)
--  Motor: MariaDB / MySQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS whiteart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE whiteart;

-- Roles: estudiante, instructor, administrador
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  apellido VARCHAR(80) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('estudiante','instructor','administrador') NOT NULL DEFAULT 'estudiante',
  foto_perfil VARCHAR(255) DEFAULT NULL,
  biografia TEXT DEFAULT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  intentos_fallidos INT NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME DEFAULT NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_usuarios_email (email),
  INDEX idx_usuarios_rol (rol)
) ENGINE=InnoDB;

-- Categorias para publicaciones/obras
CREATE TABLE IF NOT EXISTS categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL UNIQUE,
  descripcion VARCHAR(255) DEFAULT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Publicaciones estilo galeria (Instagram-like)
CREATE TABLE IF NOT EXISTS publicaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  categoria_id INT DEFAULT NULL,
  titulo VARCHAR(150) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  ruta_imagen VARCHAR(255) DEFAULT NULL,
  ruta_video VARCHAR(255) DEFAULT NULL,
  es_publico TINYINT(1) NOT NULL DEFAULT 1,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pub_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_pub_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
  INDEX idx_pub_fecha (fecha_creacion),
  INDEX idx_pub_publico (es_publico)
) ENGINE=InnoDB;

-- Compras / inscripciones (soporte para PayPal)
CREATE TABLE IF NOT EXISTS compras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  paypal_order_id VARCHAR(100) NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  moneda VARCHAR(10) NOT NULL DEFAULT 'USD',
  estado ENUM('CREATED','COMPLETED','CANCELED','FAILED') NOT NULL DEFAULT 'CREATED',
  datos_respuesta JSON DEFAULT NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_compra_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  UNIQUE KEY uq_paypal_order (paypal_order_id)
) ENGINE=InnoDB;

-- Cuentas pre-creadas: Administrador y Maestros/Instructores
-- Contraseña por defecto para todos: WhiteArt123!
INSERT IGNORE INTO usuarios (id, nombre, apellido, email, password_hash, rol, activo) VALUES
(1, 'Admin', 'WhiteArt', 'admin@whiteart.test', '$2y$10$0i1xzV1EsM8LC/Z0UPTfauqxvTAk6JwqysRKEHbiOqkZnevIHtKTi', 'administrador', 1),
(2, 'Carlos', 'MartÃ­nez', 'carlos.martinez@whiteart.com', '$2y$10$0i1xzV1EsM8LC/Z0UPTfauqxvTAk6JwqysRKEHbiOqkZnevIHtKTi', 'instructor', 1),
(3, 'Ana', 'GonzÃ¡lez', 'ana.gonzalez@whiteart.com', '$2y$10$0i1xzV1EsM8LC/Z0UPTfauqxvTAk6JwqysRKEHbiOqkZnevIHtKTi', 'instructor', 1),
(4, 'Luis', 'RodrÃ­guez', 'luis.rodriguez@whiteart.com', '$2y$10$0i1xzV1EsM8LC/Z0UPTfauqxvTAk6JwqysRKEHbiOqkZnevIHtKTi', 'instructor', 1);

INSERT IGNORE INTO categorias (nombre, descripcion) VALUES
('Arte Digital', 'Ilustraciones y arte con herramientas digitales'),
('Fotografia', 'Fotografias y retratos'),
('Poesia', 'Textos y poesia'),
('Animacion', 'Animaciones 2D/3D');

