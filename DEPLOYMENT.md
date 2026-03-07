# TaskBook – Production deployment checklist

Use this before pushing to production or running on a server with ~50 users and campaigns of up to 1500 messages.

## 1. Environment

- Copy `.env.example` to `.env` and set:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL` to your real URL
  - `APP_KEY` (run `php artisan key:generate` if missing)
- **Database:** Use MySQL/PostgreSQL in production (not SQLite). Set `DB_*` in `.env`.
- **Queue:** Keep `QUEUE_CONNECTION=database` (or use Redis if you prefer). For campaigns of 1500 messages, set:
  - `DB_QUEUE_RETRY_AFTER=300`
  so batch jobs are not marked failed while still running.
- **Aisensy (WhatsApp):** Set `AISENSY_API_KEY` and optionally `AISENSY_URL` in `.env`.

## 2. Queue (required for campaigns)

Campaigns are sent in the background. The app **schedules queue processing automatically** every minute (see `routes/console.php`). Ensure the Laravel scheduler runs via cron so those jobs are processed:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

That single cron entry runs the scheduler every minute, which in turn runs `queue:work --stop-when-empty --max-time=55` to process campaign jobs. No separate long-running queue worker is required.

**Optional:** For higher throughput you can still run a dedicated worker (e.g. `php artisan queue:work --timeout=300`) or use Supervisor; the scheduler will then process any jobs the worker hasn’t picked up. Each campaign processes up to 200 recipients per job and queues the next batch until done; 1500 messages are handled over several jobs.

## 3. After deploy

```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
npm ci && npm run build
```

Ensure the queue worker is running (see above).

## 4. Capacity (50 users, 1500 messages per campaign)

- **Sessions:** Default `SESSION_DRIVER=database` is fine for 50 users.
- **Campaigns:** Sending is batched (200 per job by default). Multiple campaigns can run in parallel if multiple workers are running. Aisensy/WhatsApp rate limits apply; batching spreads load and avoids a single long-running process.
- **Web server:** Use PHP-FPM (or equivalent) and a normal PHP memory limit (e.g. 256M). No special tuning needed for 50 users if the stack is standard.

## 5. Before pushing to Git

- Do not commit `.env` (it should be in `.gitignore`).
- Run tests if you have them; run `php artisan migrate` and a quick smoke test (login, create campaign, trigger send with queue worker running).
- Ensure `.env.example` is up to date (APP_NAME, DB_QUEUE_RETRY_AFTER note, Aisensy vars documented).
