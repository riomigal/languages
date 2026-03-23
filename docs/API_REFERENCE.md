# API Reference

All protected endpoints require request body:

```json
{
  "api_key": "your-shared-secret"
}
```

Auth middleware:

- `Riomigal\Languages\Middleware\AuthApi`

## Version

### `GET /api/version`

Returns package version.

## Protected Endpoints

Route group prefix: `/api`

### `POST /api/cancelJobs`

Cancels/deletes language batches/jobs.

Response:

- `204 No Content`

### `POST /api/languages-has-jobs-running`

Checks running language jobs/batches.

Response:

```json
{
  "process_running": true
}
```

### `POST /api/languages-get-languages`

Returns all languages as resource collection.

Fields:

- `id`, `name`, `native_name`, `code`, `created_at`, `updated_at`

### `POST /api/languages-get-paginated-translations`

Returns translations paginated at 500 items/page.

Supports standard Laravel `?page=N` query parameter.

### `POST /api/languages-force-export`

Dispatches force-export jobs for all languages and returns start message.

Response:

```json
{
  "message": "Export on https://example.com started."
}
```

## Security Notes

- Keep `LANGUAGES_API_SHARED_SECRET` secret and rotate on compromise.
- Use HTTPS for all inter-host API traffic.
- Restrict endpoint access at network/firewall layer when possible.
