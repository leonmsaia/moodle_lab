FROM php:8.0-apache

RUN apt-get update && apt-get install -y \
  git \
  unzip \
  libicu-dev \
  libxml2-dev \
  libzip-dev \
  libpng-dev \
  libjpeg-dev \
  libfreetype6-dev \
  libonig-dev \
  libcurl4-openssl-dev \
  libxslt1-dev \
  && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
  intl \
  mysqli \
  pdo \
  pdo_mysql \
  gd \
  mbstring \
  xml \
  soap \
  zip \
  opcache \
  exif \
  curl

RUN a2enmod rewrite

COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
RUN apache2ctl configtest

RUN { \
  echo "display_errors=On"; \
  echo "display_startup_errors=On"; \
  echo "error_reporting=E_ALL & ~E_DEPRECATED & ~E_STRICT"; \
  echo "memory_limit=256M"; \
  echo "upload_max_filesize=50M"; \
  echo "post_max_size=50M"; \
  echo "max_execution_time=300"; \
  echo "max_input_vars=5000"; \
  } > /usr/local/etc/php/conf.d/moodle-dev.ini

WORKDIR /var/www/html