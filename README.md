# Noema

Сервис для создания миров: бестиарии, таймлайны, вики-разделы, карты и многое другое.

## Технологии

- Laravel 12
- PHP 8.2+
- SQLite (по умолчанию)

## Установка

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

## Запуск

```bash
php artisan serve
```

Приложение будет доступно по адресу http://localhost:8000

## Лицензия

MIT
