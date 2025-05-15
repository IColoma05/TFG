 Infraestructura Docker con PHP y PostgreSQL

Despliegue completo de una aplicación PHP con autenticación de usuarios (registro, inicio y cierre de sesión) utilizando PostgreSQL como base de datos, junto con herramientas de administración como **pgAdmin**, **Portainer** y un entorno de pruebas con **Nginx + PHP-FPM**.

---

## Tabla de Contenidos

- [Prerrequisitos](#prerrequisitos)  
- [Instalación](#instalación)  
- [Uso](#uso)  
- [Deploy](#deploy)  
- [Tecnologías](#tecnologías)   
- [Documentación](#documentación)  


---

## Prerrequisitos

Antes de comenzar, asegúrate de tener las siguientes herramientas instaladas:

- Docker (>= 20.10)  
- Docker Compose (>= 1.29)  
- Git  

---

## Instalación

### Clonar el repositorio

```bash
git clone https://github.com/IColoma05/TFG.git
cd TFG/infra_stack
```

### Configurar la base de datos

Edita el archivo `web/html/config.php` con los parámetros de conexión a PostgreSQL:

```php
<?php
$host = 'postgres';
$port = 5432;
$db   = 'appdb';
$user = 'admin';
$pass = '1234';

$dsn = "pgsql:host={$host};port={$port};dbname={$db};";
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
```

### Construir y desplegar los servicios

```bash
docker compose up -d --build
```

### Crear la tabla de usuarios

```bash
docker exec -i pg_container psql -U admin -d appdb < create_users_table.sql
```

---

## Uso

### Acceso a la aplicación y herramientas:

- **Registro de usuarios:** http://localhost:8081/register.php  
- **Inicio de sesión:** http://localhost:8081/login.php  
- **Cerrar sesión:** http://localhost:8081/logout.php

### Herramientas de administración:

- **pgAdmin:** http://localhost:5051  
  - Host: `postgres`  
  - Puerto: `5432`  
  - Usuario: `admin`  
  - Contraseña: `1234`

- **Portainer:** http://localhost:9000

- **Servidor de pruebas Nginx:** http://localhost:8080

---

## Deploy

Este proyecto utiliza un único archivo `docker-compose.yml` para orquestar todos los servicios.  
**Importante:** modifica las credenciales en `web/html/config.php` antes del despliegue si tu entorno lo requiere.

---

## Tecnologías

- **Docker** – Contenerización de servicios  
- **Docker Compose** – Orquestación de contenedores  
- **PHP** – Lógica de la aplicación  
- **PostgreSQL** – Base de datos relacional  
- **pgAdmin** – Interfaz web para administrar PostgreSQL  
- **Portainer** – Interfaz web para gestionar contenedores Docker  
- **Nginx** – Servidor web ligero para pruebas  
- **PHP-FPM** – Gestor de procesos PHP para alto rendimiento  

---


## Documentación

- Configuración de la base de datos: `web/html/config.php`  
- Servicios definidos en: `docker-compose.yml`  
- Script de inicialización de usuarios: `create_users_table.sql`  

---


