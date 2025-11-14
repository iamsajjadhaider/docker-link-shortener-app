🔗 Dockerized Link Shortener App

A simple, lightweight, and self-hosted URL shortening service built using PHP, MySQL, and deployed effortlessly with Docker Compose. This solution provides a quick way to generate and manage short links without relying on external services.

✨ Features

Fully Containerized: Easy setup and consistent environment using Docker Compose.

Simple Stack: Built on PHP 8.2 (Apache) and MySQL 8.0 for reliability.

Real-time Redirection: Fast 301 Permanent redirection for SEO and speed.

Unique Code Generation: Automatically generates a unique 7-character alphanumeric code for each long URL.

Collision Handling: Checks for existing long URLs to prevent duplication.

Clean Interface: Minimalist front-end for generating and copying short links.

🚀 Getting Started

This project requires Docker and Docker Compose installed on your system.

1. Clone the Repository

First, clone the project from your remote host:

git clone [https://github.com/YOUR_USERNAME/docker-link-shortener-app.git](https://github.com/YOUR_USERNAME/docker-link-shortener-app.git)
cd docker-link-shortener-app


2. Configure Environment Variables

Create a file named .env in the project root to securely store your database credentials.

# Example content for .env
# This file is ignored by Git for security.
MYSQL_DATABASE=shortener_db
MYSQL_USER=shortener_user
MYSQL_PASSWORD=supersecure
MYSQL_ROOT_PASSWORD=rootpassword


3. Build and Run the Containers

The docker-compose.yml file defines two services: app (PHP/Apache) and db (MySQL).

Run the following command to build the PHP image, start both containers, and initialize the database schema:

docker compose up --build -d


4. Access the Application

Once the containers are running, the application will be available on your host machine at:

Web App: http://localhost:8080

The first time you access the app, the MySQL service will finish initializing the links table.

⚙️ Architecture

The application is structured around two primary services defined in docker-compose.yml:

Service

Technology

Role

Port

app

PHP 8.2 & Apache

Handles all routing, short code generation, persistence, and redirection logic.

8080 (Host) -> 80 (Container)

db

MySQL 8.0

Stores the long_url and short_code pairings in the links table.

Internal network only

Key Files

File

Description

web/index.php

The core application logic: form handling, database interaction, and URL redirection.

db/schema.sql

SQL script executed on container startup to create the links table.

web/Dockerfile

Defines the Apache/PHP environment, ensuring the mysqli extension and mod_rewrite are enabled.

web/.htaccess

Routes all non-file requests (the short codes) to index.php.

.env

Environment variables for secure database configuration.

🛑 Troubleshooting

If you encounter a "Forbidden" error when accessing http://localhost:8080, it is often a file permission issue between your host operating system and the Docker container.

Try resetting permissions in the project root:

chmod -R 775 web
docker compose down
docker compose up -d
