# LovelyWedding

Site mariage Audrey & Ludovic (03/03/2013) — version modernisée PHP 8.2+.

L'ancien code legacy reste dans son arborescence d'origine (`v2/`, `libs/`, `newsletter/`, `piwik/`, etc.) ; la nouvelle stack vit en parallèle et sera promue après validation.

## Prérequis

- PHP 8.2+ (avec extensions `pdo_mysql`, `curl`, `mbstring`, `json`)
- Composer 2.x
- Docker / Docker Compose (option recommandée pour la stack locale)

## Installation

```bash
cp .env.example .env
composer install
docker compose up -d        # MySQL 8 + Mailpit + Apache PHP 8.2
```

Le schéma SQL est chargé automatiquement au premier démarrage de MySQL via `sql/schema.sql`.
Pour ré-importer manuellement :

```bash
docker compose exec -T db mysql -u app -pchangeme lovelywedding < sql/schema.sql
```

Le site est servi sur http://localhost:8080. Mailpit (interface web pour lire les mails de test) sur http://localhost:8025.

## Arborescence

```
.
├── bin/send-newsletter      # CLI mass-mailer
├── config/routes.php        # table de routage
├── public/                  # DocumentRoot (front controller + assets statiques)
├── src/                     # code applicatif PSR-4 (LovelyWedding\)
├── sql/schema.sql           # 3 tables MySQL utf8mb4
├── templates/               # templates Smarty 5
├── tests/Unit/              # tests PHPUnit
├── var/{cache,log}/         # caches Smarty + logs Monolog (gitignored)
└── .modernization/          # audits qui ont guidé la migration
```

## Commandes

```bash
composer test         # PHPUnit
composer phpstan      # analyse statique level 6
composer cs-check     # diff PHP-CS-Fixer
composer cs-fix       # appliquer les fixes
composer ci           # cs-check + phpstan + test

php bin/send-newsletter --campaign=default --dry-run
php bin/send-newsletter --campaign=default
```

## Décisions de migration

- Piwik retiré (le snippet n'est pas porté sur les pages dynamiques)
- Doublons mobile (`v2/mobile/`) abandonnés ; le responsive CSS suffit
- Smarty 5 (auto-escape HTML par défaut) à la place de Smarty 3.1.12
- Validation email via `egulias/email-validator` (la regex legacy était cassée)
- CSRF token + honeypot conservé sur le livre d'or
- SSRF mitigé dans `RemoteImageInspector` (rejette IPs privées / link-local / loopback)
- Notifications mail via `symfony/mailer` (DSN dans `.env`), plus de `mail()` direct
- Newsletter envoyée uniquement en CLI via `bin/send-newsletter` (jamais exposée en HTTP)

## Sécurité (résumé)

- Tous les secrets sont dans `.env` (gitignored). Aucun credential dans le code.
- Headers de sécurité (CSP, X-Frame-Options, Referrer-Policy, etc.) dans `public/.htaccess`.
- `.env`, `*.sql`, `*.log`, `*.h.php`, `composer.*`, dotfiles : bloqués par `.htaccess`.
- Prepared statements PDO partout, charset utf8mb4.
- Sessions PHP utilisées uniquement pour le token CSRF.
