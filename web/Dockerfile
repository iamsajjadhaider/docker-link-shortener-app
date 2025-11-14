# Use the official PHP image as the base, running on Apache
FROM php:8.3-apache

# Install necessary PHP extensions
# mysqli is required to connect to the MySQL database
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable the Apache Rewrite module
# This module is CRITICAL because it allows us to handle requests 
# for short codes (like /abc12) and redirect them to index.php.
RUN a2enmod rewrite

# Copy the application code into the container's web root
COPY . /var/www/html

# Set the working directory
WORKDIR /var/www/html
