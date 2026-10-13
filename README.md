# YouthHub Partner

Laravel 13 application with server-rendered Blade pages and React components bundled by Vite. React mounts only where a Blade view declares a `data-react-app` element; routing, page responses, and backend logic remain in Laravel.

## Requirements

- PHP 8.3 or newer with the extensions required by Laravel
- Composer 2
- Node.js 22.12+ and npm
- MySQL 8+ or MariaDB for local development

On Windows, ensure the terminal resolves `php` to PHP 8.3 or newer. XAMPP's PHP may appear earlier on `PATH`; put the supported PHP directory first before running Artisan.

## Local setup (PowerShell)

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

The project uses the `youthhubpartner` MySQL database by default. Start MySQL in XAMPP before running migrations. Update `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` to match your local MySQL setup. The old `database/database.sqlite` file is left in place and is no longer used by the default connection.

Run the web server and Vite in separate terminals:

```powershell
php artisan serve
npm run dev
```

Open `http://127.0.0.1:8000`.

## Background jobs

The default queue connection is `database`. The jobs, batches, and failed-jobs tables are included in the initial migrations. Run a worker in a separate terminal:

```powershell
php artisan queue:work --tries=3
```

`App\Jobs\ProcessPartnerImport` is the starting example. Dispatch it from application code with `ProcessPartnerImport::dispatch($partnerId)` and put the actual import workflow in its `handle` method. For production, set `QUEUE_CONNECTION=redis` and configure Redis in `.env` if a Redis service is available.

## Project layout

- `app/Http/Controllers`: request handling
- `app/Jobs`: asynchronous work
- `app/Models`: Eloquent models
- `app/Services`: business workflows and integrations
- `resources/views`: Blade layouts and pages
- `resources/js/components`: React components mounted by Blade
- `routes/web.php`: web routes
- `database/migrations`: schema and queue tables
- `tests/Feature` and `tests/Unit`: application tests

Keep controllers focused on HTTP concerns; move reusable business rules into services/actions, and dispatch slow or retryable work through jobs.

## Useful commands

```powershell
php artisan test
php artisan queue:failed
php artisan queue:retry all
npm run build
```

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


## Trumcate Tables
USE youthhubpartner;

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE event_transaction_logs;
TRUNCATE TABLE event_transactions;

SET FOREIGN_KEY_CHECKS = 1;


## Admin Status
Admin ke statuses
Status	    Admin ke liye meaning	                                                Admin action
Draft	    Field Agent ne transaction banaya hai, abhi submit nahi kiya	        View, as per permission
Submitted	Field Agent ne submit kar diya	                                        Start Review
Under Review	Admin transaction verify kar raha hai	                            Approve, Return, Reject
Returned	Correction ke liye Field Agent ko wapas bheja	                        Reason dekhna, resubmission ka wait
Rejected	Admin ne reject kiya; aapke rule ke mutabik resubmission allowed hai	Reason dekhna, resubmission ka wait
Resubmitted	Field Agent ne correction ke baad dobara submit kiya	            Review dobara start karna
Approved	Transaction approve ho chuka hai	                                    View, history
