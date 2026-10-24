# Architecture

Somero Budget uses a Vue 3 single-page app and a Laravel API.

- `npm start` launches Laravel server and queue processes with PHP 8.2, plus the Vite dev server.
- `src/main.ts` starts the Vue app; `src/router/` maps routes to Vue pages.
- `src/home/` renders the budget table and owns its display settings and calculations.
- `src/forecast/` builds an extendable forecast from unfinished account updates, groups amounts by Thursday pay week, shows list or calendar views, and adds optional account-scoped weekly income saved in browser storage.
- `src/store/` holds client state and fetches authenticated API data with Axios.
- `app/Http/Controllers/` serves API resources; `routes/api.php` applies Sanctum authentication.
- `app/Models/` owns account and scheduled-update data. `AccountBatchUpdate` stores date, amount links, repeat interval, and next-recurrence link.
- `app/Jobs/` runs scheduled updates. Handling a repeated update creates or refreshes its next recurrence.
- `database/migrations/` defines persistent schema; `database/seeders/` supplies data. The forecast demo seeder runs only in local environments.
- `tests/` contains Laravel test suites.

The frontend reads account updates from the accounts API. The API loads unfinished updates with their account pivot amounts. The batch-update model advances repeated updates when scheduled jobs handle them.
