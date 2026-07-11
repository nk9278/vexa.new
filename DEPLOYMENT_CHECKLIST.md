# Deployment Checklist

- [ ] Configure `BASE_URL` in `config/constants.php` to the production domain.
- [ ] Configure Database credentials (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) in `config/constants.php`.
- [ ] Set `APP_ENV` to `production` (ensure it is added if missing).
- [ ] Ensure web server (Apache/Nginx) is configured to handle routing and PHP execution.
- [ ] Verify directory permissions (especially for temporary uploads if any).
- [ ] Set up cron jobs if applicable.
