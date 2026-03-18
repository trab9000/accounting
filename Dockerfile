FROM php:8.3-apache

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli \
    && rm -rf /var/lib/apt/lists/*

# Install fake sendmail that captures emails to files
COPY docker/fakesendmail.sh /usr/local/bin/fakesendmail
RUN chmod +x /usr/local/bin/fakesendmail

# PHP error config: log to file, don't display (prevents HTML leaking into AJAX responses)
COPY docker/errors.ini /usr/local/etc/php/conf.d/errors.ini

# Point PHP's mail() at the fake sendmail
RUN echo 'sendmail_path = "/usr/local/bin/fakesendmail -t"' > /usr/local/etc/php/conf.d/mail.ini

# Enable mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Allow .htaccess overrides (needed for display_errors Off to suppress warnings in AJAX responses)
RUN echo '<Directory /var/www/html>\n    AllowOverride All\n</Directory>' >> /etc/apache2/apache2.conf

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
