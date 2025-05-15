Infraestructura Docker con PHP y PostgreSQL
Descripción
Despliegue de una aplicación PHP con autenticación (registro, inicio de sesión y cierre de sesión) sobre PostgreSQL. Incluye interfaces administrativas mediante pgAdmin y Portainer, un servidor de prueba con Nginx y PHP-FPM.

Requisitos
Docker (>= 20.10)

Docker Compose (>= 1.29)

Git

Configuración de la base de datos
Edite el fichero web/html/config.php y sustituya los valores por los que corresponden a su entorno. Por ejemplo:

Host: postgres

Puerto: 5432

Base de datos: appdb

Usuario: admin

Contraseña: 1234

La sección de creación de la conexión quedaría así:
<?php
$host = 'postgres';
$port = 5432;
$db   = 'appdb';
$user = 'admin';
$pass = '1234';
$dsn  = "pgsql:host={$host};port={$port};dbname={$db};";
$pdo  = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
Despliegue
Clonar el repositorio y posicionarse en la carpeta de despliegue:
git clone https://github.com/IColoma05/TFG.git
cd TFG/infra_stack

Construir y levantar todos los servicios:
docker compose up -d --build

Crear la tabla de usuarios en la base de datos:
docker exec -i pg_container psql -U admin -d appdb < create_users_table.sql

Servicios y puertos
Servicio	URL desde host
pgAdmin	http://localhost:5051
Portainer	http://localhost:9000
Nginx de prueba	http://localhost:8080
Aplicación PHP (Nginx+FPM)	http://localhost:8081

Credenciales
Servicio	Usuario / Email	Contraseña	Base de datos
PostgreSQL	admin	1234	appdb
pgAdmin	admin@admin.com	1234	—
Portainer	admin	1234	—
Aplicación PHP	admin	1234	appdb
