FROM node:22.11.0-alpine

# Argumentos para usuario - USAR LOS MISMOS QUE PHP
ARG USER_ID=1000
ARG GROUP_ID=1000

# Instalar herramientas necesarias
RUN apk add --no-cache \
    python3 make g++ \
    && rm -rf /var/cache/apk/*

# Actualizar npm
RUN npm install -g npm@10.9.2

# Configurar directorio de trabajo
WORKDIR /var/www

# Cambiar propietario del directorio (lo hará root directamente)
RUN chown -R ${USER_ID}:${GROUP_ID} /var/www

RUN mkdir -p /var/www/node_modules && chown -R ${USER_ID}:${GROUP_ID} /var/www/node_modules

# Cambiar a usuario no-root directamente (sin crear usuario/grupo)
USER ${USER_ID}:${GROUP_ID}

# Copiar package files
COPY --chown=${USER_ID}:${GROUP_ID} package*.json ./

# Configurar carpeta de caché donde el usuario 1001 sí tenga permiso
ENV NPM_CONFIG_CACHE=/tmp/.npm

# Instalar dependencias
RUN npm install

# Copiar código fuente
COPY --chown=${USER_ID}:${GROUP_ID} . .

# Crear directorio public con permisos correctos
USER root
RUN mkdir -p /var/www/public && chown -R ${USER_ID}:${GROUP_ID} /var/www/public
USER ${USER_ID}:${GROUP_ID}

# Exponer puerto
EXPOSE 5173

USER root
RUN mkdir -p /var/www/node_modules/.vite-temp && chown -R ${USER_ID}:${GROUP_ID} /var/www/node_modules
USER ${USER_ID}:${GROUP_ID}

# Comando por defecto
CMD ["npm", "run", "dev"]
