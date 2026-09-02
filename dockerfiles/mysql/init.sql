-- Bootstraps the databases used by the admin-panel and api-service Laravel apps.
-- Executed automatically by the MySQL container on first initialization.
CREATE DATABASE IF NOT EXISTS `laravel` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `api_backend` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `api_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
