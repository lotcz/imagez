FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libjpeg-dev \
    libavif-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libmagickwand-dev \
    ghostscript \
    libgs-dev \
    imagemagick

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure and install GD with JPEG and WebP support
RUN docker-php-ext-configure gd --with-jpeg --with-webp
RUN docker-php-ext-install -j$(nproc) gd

# Install PHP extensions
RUN docker-php-ext-install mbstring && docker-php-ext-enable mbstring

# Install Imagemagick extension
RUN pecl install imagick && docker-php-ext-enable imagick

COPY docker/policy.xml /etc/ImageMagick-6/policy.xml

# Install Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
