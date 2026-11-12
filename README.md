# demo
# final

## Database

The application stores appointments, contact messages, FAQs, admin accounts and
rate limits in MongoDB. PHP must have the `mongodb` extension enabled. Configure
`MONGODB_URI` and `MONGODB_DATABASE` in the private `.env` file on the PHP host;
do not put the connection URI in source control.

When migrating an existing MySQL installation, set the `LEGACY_MYSQL_*` values
in `.env` (or use the old `DB_*` values already in that file), then run this
once before serving the app:

```powershell
php tools/migrate-mysql-to-mongodb.php
```

The import keeps existing numeric record IDs when possible, brings across
appointment and message JSONL fallback files, and skips records already present,
so it can be rerun safely. After migration, the app does not connect to MySQL.
If MongoDB is temporarily unreachable, appointment and contact submissions are
kept in `storage/submissions/` for later import.
