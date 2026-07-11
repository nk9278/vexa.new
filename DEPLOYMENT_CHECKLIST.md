# VEXA Deployment Checklist

## Environment Prep
1. Configure `BASE_URL` in `config/constants.php` to your host (e.g. `https://agency.com`).
2. Set `APP_ENV` to `production` to hide backend stack errors and tokens.
3. Replace DB credentials (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
4. Generate and deploy secure OAuth keys for Google Drive integrations in `api/google-drive-auth.php`.

## Database
1. Run `database.sql` to instantiate architecture.
2. Insert initial `Super Admin` seed record to begin UI agency mappings.

## Upload Policy & Permissions
1. **Google Drive Uploads:** Employee work submissions handle large production media (RAW photos, videos). These are not hard-limited by the application. Ensure your PHP configuration (`upload_max_filesize`, `post_max_size`) supports your desired maximum file size.
2. **Local Uploads:** Voice notes strictly bypass Google Drive and are stored locally with a hard-coded application limit of 10MB to protect your server.
3. Apply `0755` permissions to `/uploads` and its subdirectories (`/voice_notes`, `/submissions`) to allow application media to be written locally.
