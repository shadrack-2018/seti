# SETI Platform - Updated

This commit adds:

- Email verification and password reset migrations (migrations/002_auth_tokens.sql)
- TokenRepository for verification and reset tokens
- MailService (minimal) for sending verification & reset emails
- Extended AuthService with verifyEmail, forgotPassword, resetPassword
- Updated AuthController with /auth/verify, /auth/forgot, /auth/reset endpoints
- Admin HTML templates (resources/views/admin/{dashboard,products}.html)
- Minimal admin JS for Chart.js and DataTables (public/assets/js/admin.js)

How to apply migrations:

1. Ensure .env is configured and DB credentials are correct.
2. Run:

   php scripts/migrate.php

Notes:
- The MailService uses PHP mail() as a placeholder. For production, configure SMTP (PHPMailer or Symfony Mailer) and set MAIL_* env vars.
- The auth flows are intentionally minimal and should be integrated with CSRF protection, input validation, rate limiting, and a proper templating layer for full UI.
