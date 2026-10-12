-- ============================================================
--  ESQUEMA DE LA BASE DE DATOS "ventas_php"
--  Reconstruido a partir del código del proyecto
--  Compatible con MySQL / MariaDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS ventas_php
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ventas_php;

-- ------------------------------------------------------------
--  TABLA: usuarios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  usuario   VARCHAR(50)  NOT NULL UNIQUE,
  nombre    VARCHAR(100) NOT NULL,
  telefono  VARCHAR(20)  NULL,
  direccion VARCHAR(150) NULL,
  password  VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
--  TABLA: productos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS productos (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  codigo    VARCHAR(20)  NOT NULL,
  nombre    VARCHAR(100) NOT NULL,
  compra    DECIMAL(10,2) NOT NULL DEFAULT 0,
  venta     DECIMAL(10,2) NOT NULL DEFAULT 0,
  existencia INT         NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
--  TABLA: ventas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ventas (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  idUsuario INT         NOT NULL,
  total    DECIMAL(10,2) NOT NULL DEFAULT 0,
  fecha    DATETIME    NOT NULL,
  anulada  TINYINT(1)  NOT NULL DEFAULT 0,
  CONSTRAINT fk_ventas_usuario
    FOREIGN KEY (idUsuario) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
--  TABLA: productos_ventas  (detalle de cada venta)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS productos_ventas (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  idVenta   INT NOT NULL,
  idProducto INT NOT NULL,
  cantidad  INT NOT NULL,
  precio    DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_pv_venta
    FOREIGN KEY (idVenta) REFERENCES ventas(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_pv_producto
    FOREIGN KEY (idProducto) REFERENCES productos(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
--  TABLA: kardex  (movimientos de stock)
--  tipo_movimiento: INGRESO / SALIDA / AJUSTE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kardex (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  idProducto       INT NOT NULL,
  tipo_movimiento  VARCHAR(20) NOT NULL,
  cantidad         INT NOT NULL,
  stock_resultante INT NOT NULL,
  fecha            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  observacion      VARCHAR(255) NULL,
  CONSTRAINT fk_kardex_producto
    FOREIGN KEY (idProducto) REFERENCES productos(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices para acelerar las consultas del kardex y reportes
CREATE INDEX idx_kardex_producto_fecha ON kardex (idProducto, fecha);
CREATE INDEX idx_ventas_fecha          ON ventas (fecha);
CREATE INDEX idx_pv_venta              ON productos_ventas (idVenta);
