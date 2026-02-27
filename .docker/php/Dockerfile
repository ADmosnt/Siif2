FROM php:8.3-fpm

# Argumentos para usuario
ARG USER_ID=1000
ARG GROUP_ID=1000

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    zip unzip curl git libzip-dev libonig-dev libxml2-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libicu-dev libxslt1-dev \
    supervisor \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# Configurar e instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_mysql mbstring zip xml gd \
        intl xsl opcache

# Instalar Composer
COPY --from=composer:2.8.6 /usr/bin/composer /usr/bin/composer

# Crear grupo y usuario con los mismos IDs del host
RUN groupadd -g ${GROUP_ID} laravel \
    && useradd -u ${USER_ID} -g laravel -m laravel \
    && usermod -p "*" laravel -s /bin/bash

# Configurar directorio de trabajo y permisos
WORKDIR /var/www
RUN chown -R laravel:laravel /var/www

# Copiar archivos de composer primero (para cache de layers)
COPY --chown=laravel:laravel composer.json composer.lock ./

# Cambiar a usuario laravel para instalar dependencias
USER laravel

# Instalar dependencias de Composer
RUN composer install --no-dev --prefer-dist --no-scripts

# Volver a root para copiar archivos
USER root

# Copiar el resto del código
COPY --chown=laravel:laravel . .

# Cambiar a usuario laravel para completar composer
USER laravel

# Completar instalación de Composer
RUN composer run-script post-autoload-dump

# Volver a root para configuraciones finales
USER root

# Crear directorios necesarios y ajustar permisos
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R laravel:laravel /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Configurar php-fpm para usar el usuario laravel
RUN sed -i 's/user = www-data/user = laravel/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/group = www-data/group = laravel/' /usr/local/etc/php-fpm.d/www.conf

# Configuración PHP optimizada
RUN echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/custom.ini

# Script de inicialización
COPY .docker/php/init.sh /usr/local/bin/init.sh
RUN chmod +x /usr/local/bin/init.sh

EXPOSE 9000

CMD ["/usr/local/bin/init.sh"]