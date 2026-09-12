# Laravel Messenger

Учебный realtime-мессенджер на Laravel: личные чаты, статусы прочтения, online-presence и тёмная/светлая тема.

## Возможности

- Регистрация / вход (Laravel Breeze + email verification)
- Поиск пользователей и создание direct-чатов
- Отправка, soft-delete и read receipts сообщений
- Live-обновление списка чатов и переписки (Laravel Reverb)
- Online / last seen через Redis heartbeat
- Тёмная и светлая (бело-красная) тема с сохранением в Redis на пользователя
- Rate limit на отправку сообщений, поиск и создание чатов

## Стек

| Слой | Технологии |
|------|------------|
| Backend | PHP 8.4, Laravel 13, Inertia |
| Frontend | Vue 3, Tailwind CSS, Vite, Echo |
| Realtime | Laravel Reverb (WebSockets) |
| Метаданные чатов | MariaDB |
| История сообщений | MongoDB |
| Presence / тема | Redis |
| Dev-окружение | Docker Compose (nginx, PHP-FPM, Mailpit, …) |

### Архитектура данных

```
MariaDB     users, chats, chat_participants, last_message_*
MongoDB     messenger_messages, chat_reads
Redis       online / last_seen / theme
Reverb      MessageSent, MessageRead, MessageDeleted, ChatListUpdated
```

Доменная логика вынесена в сервисы (`MessageService`, `DirectChatService`, `ChatQueryService`, `ChatPresenter`, …), контроллеры тонкие.

## Быстрый старт (Docker)

### Требования

- Docker + Docker Compose
- Git

### 1. Клонирование и окружение

```bash
git clone <repo-url> laravel-messenger
cd laravel-messenger

cp .env.example .env
```

`.env.example` уже настроен под Compose (`mariadb`, `mongodb`, `redis`, `reverb`, `mailpit`).

### 2. Поднять контейнеры

```bash
docker compose up -d --build
```

### 3. Установка зависимостей и миграции

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app npm install
docker compose exec app npm run build
```

Для hot-reload фронтенда:

```bash
docker compose exec app npm run dev -- --host 0.0.0.0
```

### 4. Открыть приложение

| Сервис | URL |
|--------|-----|
| Приложение | http://localhost:8000 |
| Vite (dev) | http://localhost:5173 |
| Reverb WS | ws://localhost:8080 |
| Mailpit UI | http://localhost:8025 |
| RabbitMQ UI | http://localhost:15672 *(сервис в Compose есть, очередь по умолчанию — database)* |

Зарегистрируйте пользователя и откройте `/chats`.

### Тестовые данные (опционально)

```bash
docker compose exec app php artisan db:seed
# или сидер с чатами:
docker compose exec app php artisan db:seed --class=ChatTestSeeder
```

## Структура проекта

```
app/
  Http/Controllers/   # Auth, Chat, Message, User, Profile
  Http/Requests/      # валидация + authorize для сообщений
  Services/           # бизнес-логика чатов и сообщений
  Events/             # broadcast-события
  Models/             # User/Chat (MariaDB), Message/ChatRead (Mongo)
resources/js/
  Pages/Chat/         # список и страница чата
  Pages/Auth/         # логин / регистрация
  Layouts/            # Authenticated + Guest
  composables/        # useTheme и др.
docker/               # PHP Dockerfile, nginx
```

## Rate limiting

Именованные лимитеры в `AppServiceProvider` (ключ — user id):

| Лимитер | Лимит | Маршрут |
|---------|-------|---------|
| `messages` | 60/мин | `POST /chats/{chat}/messages` |
| `chat-search` | 30/мин | `GET /chats/search` |
| `chat-direct` | 10/мин | `POST /chats/direct` |

## Тема оформления

Кнопка ☀ / 🌙 справа в шапке. Выбор хранится в Redis:

```
user:{id}:theme → dark | light
```

Тема также шарится в Inertia и cookie, чтобы не мигал фон при загрузке.

## Отладка SQL списка чатов

Команда сравнивает план того же запроса, что `ChatQueryService::forUser()`:

```bash
docker compose exec app php artisan debug:explain-user-chats 1
```

Аргумент — ID пользователя (если не передан — первый из БД). Смотрите `key`, `access_type`, `rows` / `r_rows` в JSON-плане (MariaDB 12: `ANALYZE FORMAT=JSON`).

## Полезные команды

```bash
# логи PHP-приложения
docker compose logs -f app

# Reverb
docker compose logs -f reverb

# тесты (нужна настроенная БД под phpunit)
docker compose exec app php artisan test

# Pint
docker compose exec app vendor/bin/pint
```

## Лицензия

MIT. Учебный проект на базе [Laravel](https://laravel.com).
