# Avada Business Theme Test

A WordPress website built for business purposes, utilizing the powerful Avada theme.

## Overview

This project is a standard WordPress installation customized with the Avada Child Theme. It incorporates various functionalities suitable for a business environment, including e-commerce capabilities via WooCommerce and form handling with Gravity Forms.

## Tech Stack

*   **CMS:** WordPress
*   **Theme:** Avada (Child Theme active)
*   **Key Plugins:**
    *   WooCommerce
    *   Gravity Forms
    *   Advanced Custom Fields Pro
    *   The Events Calendar
    *   Cloudflare

## Getting Started

To set up this project locally, follow these steps:

1.  **Clone the repository:**
    ```bash
    git clone <repository-url>
    ```

2.  **Environment Setup:**
    Ensure you have a local server environment capable of running WordPress (e.g., LocalWP, XAMPP, MAMP, or a Docker setup) with:
    *   PHP (Recommended version compatible with the installed WordPress version)
    *   MySQL or MariaDB
    *   Apache or Nginx

3.  **Database:**
    *   Create a new MySQL database for the project.
    *   Import the provided database dump (if available) into your new database.

4.  **Configuration:**
    *   Copy `wp-config-sample.php` to `wp-config.php`.
    *   Update the database connection details in `wp-config.php`:
        ```php
        define( 'DB_NAME', 'database_name_here' );
        define( 'DB_USER', 'username_here' );
        define( 'DB_PASSWORD', 'password_here' );
        define( 'DB_HOST', 'localhost' );
        ```

5.  **Installation:**
    *   Point your local server to the project root.
    *   Access the site via your browser. If you haven't imported a database, you may be greeted by the WordPress installation screen.

## Theme Development

The active theme is located in `wp-content/themes/Avada-Child-Theme/`. All custom styles and functions should be added here to ensure they are preserved during parent theme updates.
