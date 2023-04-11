## Sart Project

--

1. `./vendor/bin/sail up -d` start docker containers [Laravel Sail Docs](https://laravel.com/docs/9.x/sail#introduction)
2. `./vendor/bin/sail composer install` run composer command insedi docker containers
3. `./vendor/bin/sail artisan migrate:fresh --seed` run artisan migration command insedi docker containers
4. `http://$localhost_url/admin`

## ADMIN PANEL

--
[[Filament Docs]](https://filamentphp.com/docs/2.x/admin/resources/getting-started)

# Laravel Template Admin Panel

--
This project was created to simplify the creation of Admin panels aka Backoffice and APIs to serve web, mobile apps or
just another backend project.

The project is managed in the following Trello board: https://trello.com/b/AcEeAj8K/laravel-template-bo-admin-panel

## Features

--
The Admin Panel contains:

-   Admin users management
-   Terms and conditions / Privacy Policy management
-   FAQs management
-   App users management

The API contains endpoints for:

-   Authentication for App users (Registration, Login, Recover Password)
-   Edit Profile (password included)
-   Get Terms and conditions / Privacy Policy
-   Get FAQs

## Rules

--
To improve and help us to keep our code code style, we are using [Laravel Pint](https://github.com/laravel/pint), just run `npm run pint` before each commit :)
Install VSCode extension: https://marketplace.visualstudio.com/items?itemName=open-southeners.laravel-pint

## Roles

--
https://spatie.be/docs/laravel-permission/v5/introduction

Two roles available: **Super Admin** and **Admin**

## Account Status

--
Backoffice users have one of the three types of Account Status:

-   Invited
-   Active
-   Blocked

## API Authentication

--
We are using [Sanctum](https://laravel.com/docs/9.x/sanctum) to authenticate API users (app users).
Sanctum is part of the Laravel ecosystem, and the Laravel started boilerplate brings already sanctum, so we will use this to handle the Authentication and Authorization in API.

## Important commands

--

1. `npm install`
2. `npm run dev` to build assets using vite
3. `composer install`
4. `php artisan storage:link`
5. `php artisan migrate:fresh`
6. `php artisan migrate:fresh --seed`
7. `npm run pint` to format your code using Laravel Pint
8. `php artisan l5-swagger:generate` to generate Swagger docs for the API
