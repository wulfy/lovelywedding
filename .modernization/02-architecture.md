# Architecture cible — Migration LovelyWedding vers PHP 8.x

## 1. Audit des libs existantes

### 1.1 Inventaire (`libs/` et `v2/libs/`)

| Lib | Emplacement | Type | Compat PHP 8.2/8.3 |
|---|---|---|---|
| **Smarty 3.1.12** (sept. 2012) | `libs/Smarty-3.1.12/` | Moteur de template PHP | **NON** — utilise `each()`, `create_function()`, `${var}`, paramètres optionnels non-trailing |
| `config.h.php` | `libs/` | 4 lignes (`error_reporting` + `ini_set`) | OK trivial |
| `remote.h.php` | `libs/` | `getRemoteFileInfos()` via cURL | OK (cURL toujours là) |
| `sql.h.php` | `libs/` | Singleton PDO MySQL | OK — credentials en dur à externaliser |
| `tools.h.php` | `libs/` | helpers (HTML entities, `mail()` warn) | **Risque**: balise courte `<?` ligne 1 + charset iso-8859-1 |
| `v2/libs/fancyBox` | client | JS frontend | sans impact PHP |
| `v2/libs/jquery.booklet.1.4.0` | client | JS frontend | sans impact PHP |

### 1.2 Smarty 3.1.12 — décision

Smarty 3.1.12 (sept. 2012) **n'est PAS compatible PHP 8**. La branche 3.x a été corrigée jusqu'en 3.1.48 (juin 2023) qui supporte officiellement PHP 5.2 → 8.2. **Smarty 5.x** (LGPL, PHP 8.2+) est la cible recommandée: API très proche, support actif.

**Choix retenu: Smarty 5 (`smarty/smarty:^5.4`)** plutôt que Twig.
Justification:
- Les `.tpl` existants utilisent syntaxe Smarty (`{if isset(...)}`, `{$last_post_values.nom}`, `{literal}{/literal}`). Réécrire en Twig = ~6 templates × ~250 lignes à traduire (`{% if %}`, `{{ var.nom }}`, `{% verbatim %}`).
- L'utilisateur veut "conserver les libs si possible".
- Templates contiennent beaucoup de JS inline avec `$` et `{`, mieux gérés par Smarty `{literal}` que par Twig `{% verbatim %}`.
- Smarty 5 = chargement Composer natif (`vendor/smarty/smarty/src`).

Twig serait justifié pour un site neuf, pas ici.

## 2. Audit du code PHP — risques PHP 8

### 2.1 Constructions dangereuses détectées

| Risque | Fichier(s) | Niveau |
|---|---|---|
| Balise courte `<?` ligne 1 | `libs/tools.h.php` | **Bloquant** PHP 8 si `short_open_tag=Off` |
| Charset `iso-8859-1` en dur (mail + meta) | `libs/tools.h.php` ligne 32, tous les `.tpl` | Migration UTF-8 nécessaire |
| `mysql_*` | aucun en code actif (seulement Piwik vendored + commentaires) | OK |
| `each()`, `create_function()` | Smarty 3.1.12 internals | Réglé par upgrade Smarty 5 |
| `pathinfo($image)['extension']` sans clé garantie | `livre_dor_write.php:166` | Warning PHP 8 |
| `$_POST['x']` sans `isset` | `livre_dor_write.php:135-177`, `register_newsletter.php:35` | **Undefined index → warning fatal en strict** |
| Email regex incorrecte (`^…$^` au lieu de `/…/`) | `livre_dor_write.php:44`, `register_newsletter.php:21`, mobile copies | Bug ancien — déclenche `preg_match` warning |
| `mail()` avec `$to = ""` et BCC | `newsletter/php/newsletter*.php` | Risque spam classifier — à remplacer par PHPMailer/Symfony Mailer |
| Credentials BDD en clair | `libs/sql.h.php:20` | **Critique** — à externaliser |
| `error_reporting(E_ALL)` + `display_errors='false'` (string) | `libs/config.h.php` | Faux silence en PHP 8 |
| Duplication massive `v2/` vs `v2/mobile/` | toute l'arbo | Dette à factoriser |

### 2.2 Fichiers d'entrée PHP applicatifs (hors vendored)

```
v2/livre_dor.php            (formulaire)
v2/livre_dor_read.php       (liste)
v2/livre_dor_write.php      (POST + mail)
v2/register_newsletter.php  (POST AJAX)
v2/mobile/*.php             (4 doublons mobile - à SUPPRIMER)
newsletter/php/newsletter*.php (5 scripts d'envoi mass mail)
```

Total: ~12 fichiers PHP à refactorer (le reste est statique `.html` ou Piwik vendored).

## 3. Architecture cible

### 3.1 Principes

- **PHP 8.2 minimum** (LTS jusque 2025-12, 8.3 préférable).
- **Composer** comme unique gestionnaire de dépendances.
- **PSR-4 autoload** pour code applicatif sous namespace `LovelyWedding\`.
- **Front controller** `public/index.php` derrière `.htaccess` — toutes les anciennes URLs résolues par un mini-routeur.
- **Séparation stricte** code (`src/`) / vues (`templates/`) / public (`public/`) / config (`config/`).
- **Secrets** dans `.env` (jamais commité), chargé par `vlucas/phpdotenv`.
- **DB** : PDO direct via un service `Database`. Pas de Doctrine (2 tables, overkill).
- **Validation** : maison + `egulias/email-validator` pour corriger la regex cassée.
- **Logger** : Monolog rotating file dans `var/log/`.
- **Mail** : `symfony/mailer` (DSN configurable).
- **Templating** : Smarty 5 — cache dans `var/cache/smarty/`.
- **Tests** : PHPUnit 11.
- **Outillage** : PHPStan niveau 6, PHP-CS-Fixer.

### 3.2 Arborescence cible

```
lovelywedding/
├── .env.example
├── .env                        # gitignored
├── .gitignore
├── .htaccess                   # rewrite vers public/index.php
├── .php-cs-fixer.dist.php
├── composer.json
├── phpstan.neon
├── phpunit.xml.dist
├── README.md
├── docker-compose.yml          # MySQL local
├── bin/
│   └── send-newsletter         # CLI mass-mailer
├── config/
│   ├── app.php
│   ├── database.php
│   ├── mail.php
│   └── routes.php
├── public/                     # DocumentRoot Apache
│   ├── .htaccess
│   ├── index.php               # bootstrap
│   ├── css/
│   ├── js/
│   ├── img/
│   ├── libs/                   # fancyBox + booklet
│   ├── uploads/
│   ├── *.html                  # pages statiques
│   └── google_sitemap.xml
├── src/
│   ├── Controller/
│   │   ├── GuestBookController.php
│   │   ├── NewsletterController.php
│   │   └── HomeController.php
│   ├── Domain/
│   ├── Repository/
│   │   ├── GuestBookRepository.php
│   │   └── NewsletterRepository.php
│   ├── Service/
│   │   ├── Mailer.php
│   │   ├── RemoteImageInspector.php
│   │   └── StringSanitizer.php
│   ├── Validation/
│   ├── Http/
│   │   ├── Request.php
│   │   ├── Response.php
│   │   └── Router.php
│   ├── Template/
│   │   └── SmartyRenderer.php
│   └── Kernel.php
├── templates/                  # ex v2/*.tpl
│   ├── guestbook/
│   │   ├── form.tpl
│   │   ├── read.tpl
│   │   ├── error.tpl
│   │   └── success.tpl
│   └── newsletter/
├── tests/
│   ├── Unit/
│   └── bootstrap.php
├── sql/
│   └── schema.sql              # CREATE TABLE livre_dor, newsletter, newsletter_emails
├── var/
│   ├── cache/smarty/
│   └── log/
└── vendor/
```

### 3.3 Front controller — routing table

```
"livre_dor"            -> GuestBookController::form
"livre_dor.php"        -> GuestBookController::form        (BC)
"livre_dor_read"       -> GuestBookController::read
"livre_dor_read.php"   -> GuestBookController::read        (BC)
"livre_dor_write"      -> GuestBookController::write
"livre_dor_write.php"  -> GuestBookController::write       (BC)
"register_newsletter"  -> NewsletterController::subscribe
"register_newsletter.php" -> NewsletterController::subscribe (BC)
"/"                    -> HomeController::index
```

## 4. `composer.json`

```json
{
    "name": "lovelywedding/site",
    "description": "Site mariage modernisé PHP 8",
    "type": "project",
    "license": "proprietary",
    "require": {
        "php": "^8.2",
        "ext-pdo": "*",
        "ext-pdo_mysql": "*",
        "ext-curl": "*",
        "ext-mbstring": "*",
        "ext-json": "*",
        "smarty/smarty": "^5.4",
        "vlucas/phpdotenv": "^5.6",
        "monolog/monolog": "^3.7",
        "symfony/mailer": "^7.1",
        "egulias/email-validator": "^4.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.3",
        "phpstan/phpstan": "^1.12",
        "friendsofphp/php-cs-fixer": "^3.64",
        "symfony/var-dumper": "^7.1"
    },
    "autoload": {
        "psr-4": { "LovelyWedding\\": "src/" }
    },
    "autoload-dev": {
        "psr-4": { "LovelyWedding\\Tests\\": "tests/" }
    },
    "config": {
        "sort-packages": true,
        "optimize-autoloader": true
    },
    "scripts": {
        "test": "phpunit",
        "phpstan": "phpstan analyse src tests --level=6",
        "cs-check": "php-cs-fixer fix --dry-run --diff",
        "cs-fix": "php-cs-fixer fix",
        "ci": ["@cs-check", "@phpstan", "@test"]
    }
}
```

## 5. Plan de migration

### Étape 1 — Bootstrap
- Arborescence `src/` `public/` `templates/` `config/` `tests/` `var/` `bin/` `sql/`
- `composer init` + require
- `.env.example`, `.gitignore`, `docker-compose.yml` (MySQL local)
- `sql/schema.sql` avec CREATE TABLE pour les 3 tables (utf8mb4)
- Kernel: charge `.env`, instancie PDO/Smarty/Logger/Mailer
- `public/index.php` + `public/.htaccess` + routeur

### Étape 2 — Libs partagées
- `Database` (remplace `sql.h.php`) — PDO avec `ERRMODE_EXCEPTION`, charset utf8mb4
- `StringSanitizer` — `htmlspecialchars(ENT_QUOTES|ENT_HTML5)`
- `RemoteImageInspector` — DTO `RemoteFileInfo`, blocage URLs locales (SSRF)
- `Mailer` — wrap Symfony Mailer, DSN depuis `.env`
- Tout en UTF-8

### Étape 3 — Contrôleurs
- `GuestBookController::form()` — render `guestbook/form.tpl`
- `GuestBookController::read()` — liste + flag editable par IP
- `GuestBookController::write()` — POST handler avec validation, CSRF token, honeypot
- `NewsletterController::subscribe()` — JSON response
- Suppression `v2/mobile/` (doublons)
- Mass-mailer → `bin/send-newsletter` CLI

### Étape 4 — Templates Smarty 5
- Copier `.tpl`, convertir charset meta UTF-8
- Échapper toutes les sorties dans `read.tpl` (XSS fix sur `image`)
- Configurer auto-escape par défaut

### Étape 5 — `.htaccess`

Racine :
```apache
RewriteEngine On
RewriteRule ^(.*)$ public/$1 [L]
```

`public/.htaccess` :
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]

# Sécurité
<FilesMatch "\.(env|sql|md|log|h\.php)$|^composer\.">
  Require all denied
</FilesMatch>

Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set Referrer-Policy strict-origin-when-cross-origin
Header always set Content-Security-Policy "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'"
```

### Étape 6 — Tests + déploiement
- PHPUnit : Validator, Repositories, Sanitizer
- PHPStan niveau 6
- README avec instructions install (Docker + composer + .env)

## 6. Compatibilité ascendante

Les pages statiques `.html` restent servies directement par Apache (`RewriteCond %{REQUEST_FILENAME} -f` les exclut du front controller).
Les URLs PHP existantes (`/livre_dor.php`, etc.) résolues par le routeur via mapping table.

## 7. Décisions utilisateur validées

- **Piwik** : supprimé complètement (dossier `piwik/`)
- **Mobile** : doublons `v2/mobile/` supprimés, redirection UA-based retirée du `.htaccess`
- **DB/secrets** : `.env.example` + `sql/schema.sql` + `docker-compose.yml` MySQL local
- **Scope coder** : migration complète en un seul dispatch
