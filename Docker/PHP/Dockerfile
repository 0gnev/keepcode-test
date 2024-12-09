FROM php:8.3-fpm

# Set default values for build arguments
ARG user=laraveluser
ARG uid=1000

# Install system dependencies and PHP extensions
RUN apt update && apt install -y \
    git \
    zip \
    curl \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev && \
    docker-php-ext-configure zip && \
    docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl && \
    apt clean && rm -rf /var/lib/apt/lists/*

# Copy Composer from the official Composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set environment variables
ENV USER=$user
ENV UID=$uid

# Create a system user with the specified UID and username
RUN useradd -G www-data,root -u $UID -d /home/$USER $USER && \
    mkdir -p /home/$USER/.composer && \
    chown -R $USER:$USER /home/$USER && \
    mkdir -p /var/www/html && chown -R $USER:$USER /var/www/html

WORKDIR /var/www

# Switch to the non-root user
USER $USER

EXPOSE 9000