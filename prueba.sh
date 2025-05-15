#!/bin/bash

set -e

USERNAME="admin"
PASSWORD="1234"
PGADMIN_EMAIL="admin@admin.com"

mkdir -p proxy/nginx/conf.d web/html web/nginx backend/postgres admin/pgadmin

if [ ! -f docker-compose.yml ]; then
cat > docker-compose.yml <<EOF
version: '3.8'

services:
  postgres:
    image: postgres:15
    container_name: pg_container
    restart: always
    ports:
      - "5433:5432"
    environment:
      POSTGRES_USER: ${USERNAME}
      POSTGRES_PASSWORD: ${PASSWORD}
      POSTGRES_DB: appdb
    volumes:
      - ./backend/postgres:/var/lib/postgresql/data
    networks:
      - internal

  pgadmin:
    image: dpage/pgadmin4
    container_name: pgadmin_container
    restart: always
    ports:
      - "5051:80"
    environment:
      PGADMIN_DEFAULT_EMAIL: ${PGADMIN_EMAIL}
      PGADMIN_DEFAULT_PASSWORD: ${PASSWORD}
    depends_on:
      - postgres
    volumes:
      - ./admin/pgadmin:/var/lib/pgadmin
    networks:
      - internal

  nginx:
    image: nginx:latest
    container_name: nginx_container
    restart: always
    ports:
      - "8080:80"
    volumes:
      - ./proxy/nginx/conf.d:/etc/nginx/conf.d:ro
    networks:
      - internal

  portainer:
    image: portainer/portainer-ce
    container_name: portainer
    restart: always
    ports:
      - "9000:9000"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock
      - portainer_data:/data
    networks:
      - internal

  php:
    image: php:8.2-fpm
    container_name: php_container
    restart: always
    volumes:
      - ./web/html:/var/www/html
    networks:
      - internal

  webserver:
    image: nginx:latest
    container_name: nginx_php_container
    restart: always
    ports:
      - "8081:80"
    volumes:
      - ./web/html:/var/www/html
      - ./web/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - php
    networks:
      - internal

volumes:
  portainer_data:

networks:
  internal:
    name: infra_stack_internal
    driver: bridge
EOF
fi

[ -f proxy/nginx/conf.d/default.conf ] || cat > proxy/nginx/conf.d/default.conf <<EOF
server {
    listen 80;
    server_name localhost;
    location / {
        return 200 'Nginx funcionando. pgAdmin: :5051 | Portainer: :9000';
        add_header Content-Type text/plain;
    }
}
EOF

[ -f web/nginx/default.conf ] || cat > web/nginx/default.conf <<EOF
server {
    listen 80;
    server_name localhost;

    root /var/www/html;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ =404;
    }

    location ~ \.php\$ {
        include fastcgi_params;
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/html\$fastcgi_script_name;
    }
}
EOF

[ -f web/html/index.php ] || cat > web/html/index.php <<EOF
<?php
echo "<h1>PHP funcionando correctamente</h1>";
phpinfo();
?>
EOF

for port in 5433 5051 8080 9000 8081; do
  sudo ufw status | grep -qw "$port" || sudo ufw allow "$port"/tcp
done

for service in pg_container pgadmin_container nginx_container portainer php_container nginx_php_container; do
  if ! docker ps -a --format '{{.Names}}' | grep -qw "$service"; then
    echo "Creando contenedor: $service"
    docker compose up -d "$service"
  else
    echo "Contenedor $service ya existe"
  fi
done

echo "Reiniciando todos los contenedores para verificar estado..."
docker compose restart

echo "Comprobando estado de los servicios..."

errores=0

for service in pg_container pgadmin_container nginx_container portainer php_container nginx_php_container; do
  estado=$(docker inspect -f '{{.State.Status}}' "$service")
  salud=$(docker inspect -f '{{.State.Health.Status 2>/dev/null}}' "$service")

  if [[ "$estado" != "running" ]] || [[ "$salud" == "unhealthy" ]]; then
    echo "❌ Problema detectado en $service → Estado: $estado, Salud: $salud"
    errores=$((errores + 1))
  else
    echo "✅ $service está funcionando correctamente"
  fi
done

IP=$(hostname -I | awk '{print $1}')

echo
echo "Usuario: ${USERNAME} | Contraseña: ${PASSWORD}"
echo "pgAdmin: http://$IP:5051"
echo "Portainer: http://$IP:9000"
echo "Nginx simple: http://$IP:8080"
echo "Nginx con PHP: http://$IP:8081"

if [[ $errores -eq 0 ]]; then
  echo "✅ Todos los servicios están funcionando correctamente"
else
  echo "⚠️ Se detectaron $errores servicios con fallos. Usa 'docker ps -a' y 'docker logs <container>' para investigar."
fi
