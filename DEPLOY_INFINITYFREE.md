# Deploy Laravel to InfinityFree (GitHub + FTP + phpMyAdmin)

Ниже рабочая схема для твоего проекта `itstackpr`.

## 1) Что уже сделано в репозитории

- Обновлен workflow: `.github/workflows/deploy.yml`
- Убраны пароли из файла workflow.
- Деплой теперь идет через `GitHub Secrets`.
- Перед отправкой на FTP workflow собирает проект:
  - `composer install --no-dev`
  - `npm ci && npm run build`

## 2) Подготовка InfinityFree

1. Создай хостинг-аккаунт и домен/сабдомен в InfinityFree.
2. Открой FTP Details в панели InfinityFree:
   - FTP Host
   - FTP Username
   - FTP Password
   - FTP directory (обычно что-то вроде `/htdocs/`).
3. Открой MySQL Databases:
   - создай БД и пользователя;
   - сохрани `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST`.
4. Открой phpMyAdmin:
   - выбери свою БД;
   - `Import` SQL-файла (если есть дамп).

## 3) Настройка GitHub Secrets

В репозитории GitHub открой:
`Settings -> Secrets and variables -> Actions -> New repository secret`

Добавь секреты:

- `INFINITYFREE_FTP_SERVER` = FTP Host (из InfinityFree)
- `INFINITYFREE_FTP_USERNAME` = FTP Username
- `INFINITYFREE_FTP_PASSWORD` = FTP Password
- `INFINITYFREE_REMOTE_DIR` = путь деплоя (например `/htdocs/` или `/htdocs/public/`, см. раздел ниже)

## 4) Важно: структура Laravel на shared-хостинге

Для Laravel документ-рут должен указывать на папку `public`.

Есть 2 варианта:

### Вариант A (предпочтительно)

- Если в InfinityFree можешь сделать документ-рут сразу на `public`, используй:
  - `INFINITYFREE_REMOTE_DIR=/htdocs/public/`
- Тогда деплой проще: сайт сразу смотрит в правильную папку.

### Вариант B (если document root только `/htdocs/`)

- Загружай проект в аккаунт так, чтобы:
  - код Laravel был выше web-root (например вне `htdocs`);
  - в `htdocs` лежало содержимое папки `public`.
- Если это пока сложно, можно временно оставить как есть, но это менее безопасно.

## 5) Создание `.env` на сервере

Создай файл `.env` на хостинге (через File Manager/FTP), пример:

```env
APP_NAME="IT Inventory System"
APP_ENV=production
APP_KEY=base64:PUT_YOUR_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-domain.example

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=sqlXXX.infinityfree.com
DB_PORT=3306
DB_DATABASE=if0_xxxxxxxx_dbname
DB_USERNAME=if0_xxxxxxxx
DB_PASSWORD=your_db_password

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

`APP_KEY` сгенерируй локально:

```powershell
php artisan key:generate --show
```

Скопируй значение в `.env` на сервере.

## 6) Первый деплой

1. Закоммить локальные изменения.
2. Отправь в `main`:

```powershell
git add .
git commit -m "Prepare InfinityFree deployment"
git push origin main
```

3. Проверь GitHub Actions -> workflow `Deploy to InfinityFree`.
4. После успешного деплоя открой сайт.

## 7) Миграции и сиды

На бесплатном shared-хостинге часто нет SSH, поэтому:

- либо импортируй SQL через phpMyAdmin;
- либо один раз локально сделай дамп:

```powershell
mysqldump -u root -p your_local_db > dump.sql
```

и импортируй `dump.sql` в phpMyAdmin InfinityFree.

## 8) Что делать при 500 ошибке

1. Проверь `APP_KEY` и DB-параметры в `.env`.
2. Проверь, что `vendor` и `public/build` действительно загружены.
3. Проверь права папок `storage` и `bootstrap/cache`.
4. Временно поставь `APP_DEBUG=true` (только на время диагностики).

## 9) Как работать дальше

- Ты меняешь код локально.
- Пушишь в `main`.
- GitHub Action автоматически пересобирает и грузит на InfinityFree по FTP.
- БД живет на InfinityFree (phpMyAdmin), сайт живет на InfinityFree-хостинге.

