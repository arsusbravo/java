# Moving the content to the live site

Everything you created locally (destinations, hotels, photos, articles, reviews, types, affiliate networks and the admin account) travels in two files. Temporary data such as logins, cache and queued jobs stays behind.

## 1. Export on your Mac

```bash
php artisan content:export
```

This writes two files to `storage/app/exports/` and prints the number of rows per table:

- `content-<date>.sql.gz`: the whole database (about 6 MB)
- `uploads-<date>.tar.gz`: the images uploaded in the admin (`storage/app/public`)

## 2. Upload both files to the server

For example, into the site's `storage/app/exports/` folder:

```bash
scp storage/app/exports/content-<date>.sql.gz storage/app/exports/uploads-<date>.tar.gz \
    forge@your-server:your-site/storage/app/exports/
```

## 3. Import on the server

Run this once, after the code is deployed and `.env` has the database login, and **before** `php artisan migrate`:

```bash
php artisan content:import content-<date>.sql.gz uploads-<date>.tar.gz
php artisan migrate --force   # should say "Nothing to migrate"
```

Compare the row counts it prints with the ones from the export. Then log in with your usual admin email and password.

The import refuses to run on a database that already has content, so it can't wipe the live site by accident. Use `--force` only if you really want to replace everything.

### Old MariaDB (5.5) or MySQL (before 5.7)

The database is built to work on these. Indexed text columns are at most 191 characters, and there are no JSON columns. Run `content:import` **on the server**, so it uses the server's own `mysql` client. A recent MySQL client (for example 9.x on a Mac) can't log in to MariaDB 5.5 ("Authentication plugin 'mysql_native_password' cannot be loaded"). The command explains this if it happens.

### No SSH (shared hosting)

- **Database:** in phpMyAdmin, open the empty database and use **Import** with the `.sql.gz` file.
- **Images:** unpack `uploads-<date>.tar.gz` on your Mac and upload its contents to `storage/app/public/` over SFTP. Then make sure `public/storage` links to it (`php artisan storage:link`, or ask your host).

## After launch

Add and edit content in the **live** admin from then on. Exporting from your Mac again and importing with `--force` would replace everything on the live site, including reviews, affiliate clicks and edits made there.
