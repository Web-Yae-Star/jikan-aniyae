# Jikan Aniyae REST API - Fork Privado

[![PHP stable](https://img.shields.io/badge/PHP-^8.1-blue.svg?style=flat)]()
[![Docker](https://img.shields.io/badge/Docker-enabled-blue.svg?style=flat&logo=docker)]()

Este repositorio es un fork de uso privado y de codigo abierto de Jikan REST API v4. Jikan realiza web scraping de MyAnimeList.net para ofrecer endpoints REST del sitio de manera local y estable.

## Requisitos Previos

- PHP 8.1 o superior (si se ejecuta manualmente)
- Docker y Docker Compose (instalacion recomendada)

## Instalacion y Despliegue con Docker

El metodo recomendado para desplegar este servicio es a traves de Docker Compose, el cual inicializara el servidor API, MongoDB, Redis y Typesense de manera automatica.

1. Asegurate de tener los archivos de configuracion env configurados en la carpeta `docker/config/.env.compose`.
2. Inicia los contenedores construyendo la imagen local:
   ```bash
   docker compose up -d --build
   ```
3. La API estara disponible en:
   ```
   http://localhost:8080
   ```

Si se necesita reconstruir la imagen para ver los ultimos cambios usa.
```bash
docker compose up -d --build --force-recreate
```
o
```bash
docker compose run --rm --entrypoint composer jikan_rest update mongodb/mongodb --with-all-dependencies --no-interaction
```

Para apagar los servicios:
```bash
docker compose down
```

## Instalacion Manual

1. Instala las dependencias de Composer:
   ```bash
   composer install
   ```
2. Configura tu archivo de entorno `.env` basandote en `.env.dist`.
3. Inicia el servidor usando RoadRunner o el comando de desarrollo correspondiente.

## Pruebas de Software

Para correr los tests unitarios e integracion:
```bash
php vendor/bin/phpunit tests
```

Nota: Las pruebas pueden fallar si MyAnimeList limita las peticiones por IP (HTTP 429).

## Descargo de Responsabilidad

- Este proyecto no esta afiliado con MyAnimeList.net.
- El uso de esta API es bajo su propia responsabilidad. Respete los terminos de servicio de MyAnimeList.net.
