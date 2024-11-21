#!/bin/bash

# Check if Laravel is installed
if [ ! -f "artisan" ]; then
    echo "Creating new Laravel project..."
    composer create-project laravel/laravel .
    
    # Configure SQLite
    touch database/database.sqlite
    sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env
    sed -i 's/DB_DATABASE=.*$/DB_DATABASE=\/var\/www\/html\/database\/database.sqlite/' .env
    
    # Add RabbitMQ configuration
    echo "RABBITMQ_HOST=${RABBITMQ_HOST}" >> .env
    echo "RABBITMQ_PORT=${RABBITMQ_PORT}" >> .env
    echo "RABBITMQ_USER=${RABBITMQ_USER}" >> .env
    echo "RABBITMQ_PASSWORD=${RABBITMQ_PASSWORD}" >> .env
    
    # Install PHP-AMQP package
    composer require php-amqp/php-amqp
fi

# Start PHP development server
php artisan serve --host=0.0.0.0 