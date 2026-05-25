# Audit de sécurité applicative — lovelywedding.fr

> Audit OWASP / AppSec en mode read-only du site PHP legacy `/Users/ludovic.lasry/dev/perso/lovelywedding/`.
> Objectif : identifier les vulnérabilités exploitables et fournir des préconisations concrètes pour la version modernisée (PHP 8 + Composer).
>
> Sévérité utilisée : **Critical** (compromission immédiate possible) / **High** / **Medium** / **Low** / **Info**.

---

## 1. Résumé exécutif

Le site présente une **surface d'attaque très importante** pour un site quasi-statique :

- **Credentials de production en clair** dans le repo (`libs/sql.h.php`) — fuite immédiate des accès MySQL 1&1.
- **`phpinfo()` exposé** en racine + dans `v2/phpoldfichiers/test.php` — divulgation versions et chemins.
- **Plusieurs fichiers de backup et historiques** exposés via HTTP : `.bash_history`, `.viminfo`, `.htaccess24/25/good23`, `bckup/`, `old/`, `phpoldfichiers/`.
- **Composants tiers extrêmement obsolètes** : Piwik **1.11.1** (2013) et Smarty **3.1.12** (2012), cumulant des CVE Critical (RCE).
- **XSS stockée** dans le livre d'or (sortie Smarty non échappée par défaut + valeurs HTML attributes sans `escape`).
- **Pas de CSRF, pas de rate-limit, pas de captcha** sur les endpoints d'écriture (livre d'or, newsletter).
- **`.htaccess`** ne contient **aucun en-tête de sécurité** ni protection des fichiers sensibles.

Le risque global est **CRITIQUE** dans l'état actuel.

---

## 2. Findings

### 2.1 — Quick wins (fichiers exposés via HTTP)

#### F-01 — `phpinfo()` exposé à la racine — **Critical**
- **Fichier** : `/Users/ludovic.lasry/dev/perso/lovelywedding/info.php` (3 lignes)
- **Description** : `phpinfo()` divulgue version PHP, extensions, chemins absolus, variables d'environnement, configuration `php.ini`. Cadeau pour un attaquant.
- **Remediation** : SUPPRIMER `info.php`. Idem pour `v2/phpoldfichiers/test.php` (ligne 7) et `logs/info.php5` qui appellent aussi `phpinfo()`.

#### F-02 — Credentials MySQL en clair dans le repo — **Critical**
- **Fichier** : `/Users/ludovic.lasry/dev/perso/lovelywedding/libs/sql.h.php:20`
  ```php
  $this->pdo = new PDO('mysql:host=db439618928.db.1and1.com;dbname=db439618928', "dbo439618928", ".Sacdoss69");
  ```
- Idem `/Users/ludovic.lasry/dev/perso/lovelywedding/v2/phpoldfichiers/test.php:19` et `v2/mobile/livre_dor_read.php:20` : `mysql_connect("localhost", "site", ".Tadoss69")`.
- **Description** : Tout commit/clone du repo expose la base de données 1&1 en lecture/écriture. Le mot de passe est aussi accessible si `*.php` est servi en mode plain par erreur (downgrade Apache, mauvaise extension, etc.).
- **Remediation** : **Rotation immédiate des mots de passe**. Stocker dans `.env` (lu via `vlucas/phpdotenv`), `.env` ajouté au `.gitignore`. Ne jamais committer de credentials.

#### F-03 — Fichiers de backup `.htaccessXX` accessibles — **High**
- **Fichiers** :
  - `/Users/ludovic.lasry/dev/perso/lovelywedding/.htaccess24`
  - `/Users/ludovic.lasry/dev/perso/lovelywedding/.htaccess25`
  - `/Users/ludovic.lasry/dev/perso/lovelywedding/.htaccessgood23`
- **Description** : Apache n'interdit `.htaccess` que parce que c'est ce nom exact. `.htaccess24` est servi comme un fichier texte → expose la logique de routing (user-agents bloqués, hosts internes piwik).
- **Remediation** : SUPPRIMER ces fichiers. Ajouter une règle générique `<FilesMatch>` (cf. §5).

#### F-04 — `.bash_history` et `.viminfo` exposés — **High**
- **Fichiers** : `/Users/ludovic.lasry/dev/perso/lovelywedding/.bash_history` (révèle `cd v2`, `git status`) et `.viminfo` (révèle l'édition de `~/libs/sql.h.php` et `~/libs/config.h.php` — donc montre où se trouvent les credentials).
- **Remediation** : SUPPRIMER. Bloquer côté serveur via `<FilesMatch "^\.">` (cf. §5).

#### F-05 — Dossier `logs/` accessible publiquement — **High**
- **Fichier** : `/Users/ludovic.lasry/dev/perso/lovelywedding/logs/` contient `access.log.*.gz`, `sftp.log`, `traffic.db`, et un `.htaccess` qui active `Options +Indexes` (listing des fichiers !).
- Le `.htaccess` interne ajoute une BasicAuth (`AuthUserFile /kunden/homepages/35/.../htpasswd`), mais :
  1. `Options +Indexes` reste actif si l'auth est contournée (ex. en cas de mauvaise interprétation du `Require user`).
  2. `sftp.log` peut contenir des chemins, IPs, noms d'utilisateurs.
  3. Le path absolu du fichier htpasswd est divulgué (info de structure serveur).
- **Remediation** : Sortir `logs/` du DocumentRoot. Si vraiment hébergé sous le webroot, désactiver `+Indexes` et ajouter `Require all denied` (Apache 2.4) ou `Deny from all` (2.2).

#### F-06 — Dossier `v2/bckup/`, `v2/phpoldfichiers/`, `old/` accessibles — **Medium/High**
- **Fichiers** :
  - `v2/bckup/livre_dor_read.tplold`, `livre_dor.html`, `indexold.html`, etc.
  - `v2/phpoldfichiers/test.php` (appelle `phpinfo()` + contient des credentials MySQL)
  - `old/old_rob.old` (un `robots.txt: Disallow: /` qui n'a aucun effet en `.old`)
- **Remediation** : SUPPRIMER ces dossiers. Aucune raison qu'ils soient en production.

#### F-07 — `under_construction/` toujours présent — **Low**
- Page statique de chantier toujours déployée — pas une vulnérabilité directe mais à nettoyer.

---

### 2.2 — Composants tiers obsolètes

#### F-08 — Piwik 1.11.1 (2013) — **Critical**
- **Fichier** : `/Users/ludovic.lasry/dev/perso/lovelywedding/piwik/core/Version.php` → `const VERSION = '1.11.1';`
- **Description** : Piwik 1.11.1 date de mars 2013. Depuis :
  - Le projet a été renommé **Matomo** ; toutes les versions < 3.x sont EOL.
  - CVE multiples sur les versions 1.x/2.x : **XSS stockée**, **SQL injection** sur `Visitor.php`, **CSRF**, **SSRF**, **RCE** via `Custom alerts plugin`, etc. (NVD : CVE-2013-0193, CVE-2013-1844, CVE-2014-9118, CVE-2017-15321, etc.).
  - Surface d'attaque énorme (interface admin avec login).
- **Remediation** : **Supprimer complètement le dossier `piwik/`**. Si analytics encore nécessaire, déployer Matomo récent (≥ 5.x) sur un sous-domaine isolé, OU passer sur Plausible / GA4 / Umami (SaaS) pour éliminer la surface d'attaque PHP.

#### F-09 — Smarty 3.1.12 (2012) — **High**
- **Fichier** : `/Users/ludovic.lasry/dev/perso/lovelywedding/libs/Smarty-3.1.12/`
- **CVE applicables** :
  - **CVE-2017-1000480** : SSTI / PHP code injection via `{php}` ou `Smarty_Security` mal configuré (impacte 3.1.31).
  - **CVE-2018-13982** : Path traversal dans `Smarty_Security::isTrustedResourceDir()` (≤ 3.1.32).
  - **CVE-2018-16831** : Sandbox bypass (3.1.x ≤ 3.1.33).
  - **CVE-2021-26119** : SSTI (≤ 3.1.39).
  - **CVE-2021-29454** : SSTI via mathématique (≤ 3.1.42) — particulièrement applicable ici.
  - **CVE-2023-28447** : XSS via Javascript escaping (≤ 4.3.1).
- **Description** : 3.1.12 cumule **toutes** ces CVE. De plus, **Smarty n'auto-escape pas par défaut** : les sorties `{$page.message}`, `{$page.image}`, `{$page.nom}`, `{$jsonFields}` (cf. §2.3) sont vulnérables à XSS.
- **Remediation** : Migrer vers **Twig 3** (pas d'historique de SSTI, auto-escape HTML par défaut), ou Smarty ≥ 5 avec `$smarty->escape_html = true;`. Composer : `composer require twig/twig`.

#### F-10 — jQuery très ancien (1.x), Lightbox, Booklet — **Medium**
- **Fichiers** : `v2/js/jquery.js`, `jquery-ui-1.8.21.custom.min.js`, etc.
- jQuery ≤ 1.x : CVE-2015-9251 (XSS via `$.ajax`), CVE-2019-11358 (Prototype pollution), CVE-2020-11022/11023 (XSS via `html()`).
- **Remediation** : Migrer vers jQuery 3.7+ (ou se passer de jQuery — la majeure partie est utilisable en vanilla JS aujourd'hui).

---

### 2.3 — Audit du code PHP applicatif

#### F-11 — XSS stockée dans le livre d'or — **High**
- **Fichier** : `v2/livre_dor_read.tpl` lignes 74, 94, 97, 109, etc.
  ```smarty
  <a href='{$page.image}' rel="lightbox"> <img src='{$page.image}' width='100%'/> </a>
  <h3>{$page.nom|upper|truncate:20}</h3>
  <span class='firstLetter'>{$page1|substr:0:1}</span>{$page1|substr:1}
  ```
- **Description** : Smarty 3.1.12 **n'auto-escape rien**. `$page.image` est juste validé sur son extension (`pathinfo`) ; un attaquant peut envoyer `http://evil.com/x.jpg" onerror="alert(1)` (l'extension `pathinfo` retournera `jpg` mais l'attribut HTML est cassé). De même `nom` est inséré tel quel.
- Le code applicatif essaie d'échapper côté serveur via `prepareString()` (`htmlentities`) mais **uniquement sur `message`**, pas sur `nom`, `ville`, `image`, `email`. De plus le double-pass `unprepareString()` (livre_dor.php:19) **re-décode** le message pour ré-éditer → faille de logique.
- **Remediation** :
  - Twig ou Smarty avec auto-escape.
  - **Ne jamais stocker du HTML pré-échappé** en base : stocker la donnée brute, échapper UNIQUEMENT en sortie.
  - Pour les images : valider que c'est bien une URL `https://` et fetch côté serveur pour vérifier le content-type, ou héberger localement.

#### F-12 — Validation email cassée — **Medium**
- **Fichier** : `v2/livre_dor_write.php:44`, `register_newsletter.php:21`
  ```php
  return preg_match("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$^", $email);
  ```
- **Description** : Le délimiteur PCRE utilisé est `^` (au lieu de `/` ou `#`), donc `^` est interprété **comme délimiteur** et non comme ancre de début. Le regex est en pratique invalide / interprété différemment selon les versions PHP. Sur PHP 8, ce `preg_match` retourne `false` → tous les emails sont rejetés OU acceptés selon contexte.
- **Remediation** : Utiliser `filter_var($email, FILTER_VALIDATE_EMAIL)`. Pour de la validation forte : `egulias/email-validator`.

#### F-13 — Pas de protection CSRF — **High**
- **Fichiers** : `v2/livre_dor_write.php`, `v2/register_newsletter.php`, `v2/mobile/livre_dor_write.php`
- **Description** : Aucun token CSRF. Un site malveillant peut faire poster un message ou inscrire des emails arbitrairement (worst-case : remplir la newsletter de milliers d'emails fictifs et déclencher l'envoi de mails).
- **Remediation** : Token CSRF par session (`paragonie/anti-csrf` ou maison via `random_bytes(32)` + `hash_equals`). Templater le token dans le form, le valider côté serveur.

#### F-14 — Pas de rate-limit / pas de captcha — **High**
- **Fichiers** : Identique F-13.
- **Description** : Un bot peut spammer le livre d'or et la newsletter. La protection actuelle est un **honeypot** sur un champ `prenom` (lignes 157-158 de `livre_dor_write.php`) — bonne idée mais insuffisante face à un bot ciblé.
- **Remediation** :
  - reCAPTCHA v3 / hCaptcha / Cloudflare Turnstile sur les formulaires publics.
  - Rate-limit par IP (ex. via Symfony RateLimiter ou Redis token-bucket).
  - Conserver le honeypot en defense-in-depth.

#### F-15 — SSRF dans `getRemoteFileInfos()` — **High**
- **Fichier** : `/Users/ludovic.lasry/dev/perso/lovelywedding/libs/remote.h.php:2-27`
- **Description** : Appelé depuis `livre_dor_write.php:167` avec l'URL fournie par l'utilisateur (`$_POST['image']`). cURL suit les redirections (`CURLOPT_FOLLOWLOCATION = true`) sans whitelist de domaines/IPs. Un attaquant peut :
  - Cibler `http://169.254.169.254/` (AWS metadata) si hébergé sur EC2.
  - Scanner les services internes (`http://localhost:3306`, etc.).
  - Forcer le serveur à interroger une URL longue/lente pour DoS.
- **Remediation** :
  - Whitelist explicite d'hôtes/protocoles.
  - Bloquer les IPs privées (`filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)`).
  - Timeout cURL (`CURLOPT_TIMEOUT`, `CURLOPT_CONNECTTIMEOUT`).
  - Désactiver les redirections OU re-valider l'URL après chaque redirect.
  - Stocker les images localement après upload validé, ne pas accepter d'URL externe.

#### F-16 — Code de fonction d'upload mort mais conservé — **Medium**
- **Fichier** : `v2/livre_dor_write.php` lignes 11-37 (commenté) et `v2/mobile/livre_dor_write.php` lignes 11-37.
- **Description** : Le code d'upload utilise `basename($_FILES['file']['name'])` directement comme chemin de destination, sans validation MIME, sans vérification d'extension, sans renommage. Si réactivé, **upload de webshell PHP** trivial (`shell.php` → `/uploads/shell.php` exécutable).
- Le dossier `v2/uploads/` existe (`photo3.JPG`, `Thumbs.db`).
- **Remediation** : Supprimer ce code mort. Si upload nécessaire dans la v2 : valider MIME via `finfo_file()`, vérifier l'extension via whitelist (`jpg`, `png`, `gif`, `webp`), renommer en `bin2hex(random_bytes(16)).$ext`, stocker hors webroot ou dans un dossier avec `php_flag engine off` + `RemoveType .php`.

#### F-17 — Détection d'identité par IP côté écriture (IDOR) — **Medium**
- **Fichier** : `v2/livre_dor_write.php:179-189`, `v2/livre_dor.php:13`
- **Description** : Un utilisateur peut **éditer son message** s'il vient depuis la même IP. Comme la majorité des FAI utilisent du CGNAT (IPv4 partagée) ou que les IPs changent (DHCP, VPN), un autre visiteur peut éditer le message d'un autre, et inversement un utilisateur légitime peut perdre l'accès.
- **Remediation** : Lien d'édition signé envoyé par email (HMAC SHA-256 + expiration), ou compte authentifié.

#### F-18 — Session non sécurisée (mais ici, absence totale de session) — **Info**
- Aucun `session_start()` dans le code applicatif → pas d'espace admin PHP (la modération du livre d'or se fait probablement directement en base via phpMyAdmin 1&1). Pour la v2, prévoir une vraie gestion de session sécurisée (cf. §3).

#### F-19 — `error_reporting(E_ALL)` activé — **Low**
- **Fichier** : `libs/config.h.php:3` (`error_reporting(E_ALL); ini_set('display_errors','false');`)
- **Description** : `display_errors` est passé en string `'false'` (non vide), donc évalué à `true` par PHP → les erreurs sont affichées en production. Doit être `'0'` ou `'Off'`.
- **Remediation** : `ini_set('display_errors', '0');` + `log_errors=On` + logs dans un fichier hors webroot.

#### F-20 — `prepareString` utilise `stripslashes` — **Low**
- **Fichier** : `libs/tools.h.php:6` : `nl2br(htmlentities(stripslashes($string), ENT_QUOTES, "UTF-8"))`
- **Description** : Reliquat de l'époque `magic_quotes_gpc` (supprimé en PHP 5.4). `stripslashes` peut casser les chaînes contenant des `\` légitimes. Sans incidence sécurité directe mais corruption potentielle.
- **Remediation** : Retirer `stripslashes`. PHP 8 n'a plus de magic quotes.

#### F-21 — Mass mailer newsletter sans auth — **Critical** (si exposé via HTTP)
- **Fichier** : `/Users/ludovic.lasry/dev/perso/lovelywedding/newsletter/php/newsletter.php` (et `newsletter_normal.php`, `newsletter_evjf.php`, etc.)
- **Description** : Endpoint qui, si appelé via HTTP (et la regex de `.htaccess` permet `/newsletter/.*$`), **envoie un mail à TOUTE la table `newsletter_emails`**. La constante `TEST` est mise à `true` actuellement, mais :
  - Un attaquant peut ne PAS pouvoir flipper la constante directement, MAIS peut spammer l'endpoint et déclencher des envois TEST illimités (mail flood, blacklist du serveur SMTP).
  - Si un dev a un jour mis `TEST=false`, l'endpoint devient un mass-mailer abusable.
  - Les emails de la liste TEST_EMAIL sont **divulgués** dans le code (PII).
- **Remediation** : Ces scripts ne doivent **JAMAIS** être appelables en HTTP. Les déplacer hors du DocumentRoot, ou les transformer en commande CLI (Symfony Console, `bin/console newsletter:send`). Ajouter du HMAC/auth obligatoire.

#### F-22 — Email headers injection possible — **Medium**
- **Fichier** : `register_newsletter.php:60` : `mail($to, $subject, $message)` avec `$to = $_POST['email']` validé seulement via la regex cassée (F-12).
- **Description** : Si la validation email est cassée (F-12), un attaquant pourrait injecter `\r\nBcc: victim@example.com` dans le `$to`. Le sujet/message sont des constantes ici, donc moindre impact, mais ce code est dupliqué (`v2/mobile/register_newsletter.php` identique).
- **Remediation** : Utiliser `symfony/mailer` ou `PHPMailer` avec validation stricte.

---

### 2.4 — Configuration serveur (`.htaccess`)

#### F-23 — Aucun en-tête de sécurité — **High**
- **Fichier** : `/Users/ludovic.lasry/dev/perso/lovelywedding/.htaccess`
- **Headers manquants** :
  - `Content-Security-Policy` — pas de protection XSS en defense-in-depth.
  - `X-Frame-Options: DENY` — clickjacking trivial.
  - `Strict-Transport-Security` — pas de HSTS.
  - `X-Content-Type-Options: nosniff` — MIME sniffing possible.
  - `Referrer-Policy: strict-origin-when-cross-origin`.
  - `Permissions-Policy` — pas de restriction des APIs navigateur.
- **Remediation** : Cf. modèle `.htaccess` §5.

#### F-24 — Pas de HTTPS forcé — **High**
- **Fichier** : `.htaccess` lignes 28, 40 → `RewriteRule ^(.*)$ http://mobile.lovelywedding.fr/$1` (en `http://` explicite).
- **Remediation** : Forcer HTTPS via `RewriteCond %{HTTPS} off` + `RewriteRule (.*) https://%{HTTP_HOST}/$1 [R=301,L]` en tout début de `.htaccess`.

#### F-25 — Cookies Smarty / session sans flags — **Medium**
- **Description** : Pas de `session_set_cookie_params` dans le code → les cookies sortent avec les valeurs par défaut PHP (souvent `httponly=false`, `secure=false`, `samesite=Lax` ou vide).
- **Remediation** : Cf. §3.

#### F-26 — Pas de protection des fichiers sensibles — **High**
- **Description** : `.env`, `composer.json`, `composer.lock`, `.git/`, `*.sql`, `*.log`, `*.md`, `*.bak`, fichiers `*.h.php` (visiblement préfixés `.h.` pour "header" — pas de protection particulière) sont tous accessibles si déposés dans le webroot.
- **Remediation** : Cf. §5.

---

## 3. Recommandations pour la v2 (PHP 8 + Composer)

### 3.1 Stack et dépendances

```bash
composer require \
  twig/twig:^3 \
  symfony/http-foundation:^7 \
  symfony/mailer:^7 \
  symfony/rate-limiter:^7 \
  symfony/validator:^7 \
  vlucas/phpdotenv:^5 \
  paragonie/anti-csrf:^2 \
  egulias/email-validator:^4 \
  monolog/monolog:^3 \
  symfony/security-csrf:^7
composer require --dev \
  vimeo/psalm:^5 \
  phpstan/phpstan:^1 \
  phpunit/phpunit:^11
```

### 3.2 Checklist actionnable

- [ ] **Supprimer immédiatement** :
  - `info.php`
  - `.bash_history`, `.viminfo`
  - `.htaccess24`, `.htaccess25`, `.htaccessgood23`
  - `v2/bckup/`, `v2/phpoldfichiers/`, `old/`, `under_construction/`
  - `piwik/` (et migrer vers Matomo Cloud ou Plausible)
  - `logs/` (déplacer hors du DocumentRoot)
- [ ] **Rotation des credentials** :
  - Mot de passe MySQL 1&1 → généré aléatoire, stocké dans `.env`.
  - Vérifier les logs d'accès pour détecter une compromission antérieure.
- [ ] **Charger la config via `.env`** (`vlucas/phpdotenv`) et ajouter `.env` au `.gitignore`.
- [ ] **PDO + prepared statements** partout, plus aucune concaténation. (Le code actuel utilise déjà PDO prepared dans `livre_dor_write.php`, à généraliser et auditer.)
- [ ] **Twig 3** avec auto-escape HTML par défaut. Remplacer Smarty.
- [ ] **Validation systématique** :
  - `filter_var($email, FILTER_VALIDATE_EMAIL)` ou `egulias/email-validator`.
  - `symfony/validator` pour les contraintes complexes (length, NotBlank, Regex).
- [ ] **CSRF token** par session via `paragonie/anti-csrf` ou `symfony/security-csrf` sur tous les `POST`.
- [ ] **reCAPTCHA v3 / Turnstile** sur livre d'or et newsletter + conserver le honeypot.
- [ ] **Rate-limiting** : `symfony/rate-limiter` avec backend Redis ou filesystem (ex. 5 messages/h/IP sur le livre d'or, 1 inscription/h/IP sur la newsletter).
- [ ] **Sessions sécurisées** :
  ```php
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '.lovelywedding.fr',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
  if (empty($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
  }
  ```
- [ ] **Mailer** : `symfony/mailer` (SMTP authentifié, pas `mail()`). Newsletter en CLI via `bin/console newsletter:send`, jamais via HTTP.
- [ ] **SSRF** : si l'upload d'image distante est conservé, whitelist d'hôtes + bloquer IPs privées + timeouts. Sinon, upload local seulement.
- [ ] **Upload** : `finfo_file()` pour MIME, whitelist extensions, renommage aléatoire (`bin2hex(random_bytes(16))`), stockage hors webroot.
- [ ] **Logging** : Monolog avec channels (security, app, db). Logger tentatives suspectes (rate-limit hit, CSRF fail, upload reject).
- [ ] **HTTPS forcé** + HSTS (`max-age=63072000; includeSubDomains; preload`).
- [ ] **Headers de sécurité** complets (cf. §5).
- [ ] **CI** : SAST avec Psalm (`--security-analysis`) + `composer audit` + `roave/security-advisories` en `require-dev`.
- [ ] **Tests** : PHPUnit pour livre d'or (XSS payload, SQLi payload, CSRF absent, rate-limit).

---

## 4. Smarty/Twig — exemple de migration sortie sûre

```php
// Avant (Smarty 3.1.12, vulnérable XSS)
$smarty->assign('page', $page);
$smarty->display('livre_dor_read.tpl'); // {$page.nom} non échappé

// Après (Twig 3, auto-escape par défaut)
$loader = new \Twig\Loader\FilesystemLoader(__DIR__.'/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => __DIR__.'/var/cache/twig',
    'autoescape' => 'html',
    'strict_variables' => true,
]);
echo $twig->render('livre_dor_read.html.twig', ['pages' => $pages]);
// {{ page.nom }} automatiquement htmlspecialchars
// {{ page.image|escape('html_attr') }} pour les attributs
// {{ page.url|escape('url') }} pour les URLs
```

---

## 5. Modèle `.htaccess` durci (Apache 2.4)

```apache
# ===== Forcer HTTPS =====
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]

# ===== Headers de sécurité =====
<IfModule mod_headers.c>
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "camera=(), microphone=(), geolocation=(), interest-cohort=()"
    Header always set Content-Security-Policy "default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; upgrade-insecure-requests"
    Header always set Cross-Origin-Opener-Policy "same-origin"
    Header always set Cross-Origin-Resource-Policy "same-origin"
    # Retirer la divulgation
    Header unset X-Powered-By
    Header unset Server
</IfModule>

# ===== Désactiver le listing =====
Options -Indexes -MultiViews
ServerSignature Off

# ===== Bloquer les fichiers sensibles =====
<FilesMatch "^\.">
    Require all denied
</FilesMatch>

<FilesMatch "(?i)\.(env|env\..*|ini|log|sql|bak|backup|old|orig|swp|tmp|sh|json|lock|md|yml|yaml|dist|h\.php|inc|tpl|twig)$">
    Require all denied
</FilesMatch>

<FilesMatch "(composer\.(json|lock)|package\.json|package-lock\.json|yarn\.lock|webpack\.config\.js|\.htaccess.*)$">
    Require all denied
</FilesMatch>

# Bloquer .git, .svn, vendor, var/cache, logs
RedirectMatch 404 /\.(git|svn|hg|bzr|DS_Store)(/|$)
RedirectMatch 404 /vendor(/|$)
RedirectMatch 404 /var/(cache|log)(/|$)
RedirectMatch 404 /logs(/|$)

# ===== PHP : pas d'exécution dans /uploads =====
<Directory "/var/www/html/uploads">
    <FilesMatch "\.(php|phtml|php3|php4|php5|php7|php8|pht|phar)$">
        Require all denied
    </FilesMatch>
    php_flag engine off
    RemoveHandler .php .phtml
    RemoveType .php .phtml
</Directory>

# ===== Limite taille upload =====
LimitRequestBody 5242880   # 5 MB

# ===== Cookies sécurisés (à compléter en PHP via session_set_cookie_params) =====
<IfModule mod_headers.c>
    Header always edit Set-Cookie ^(.*)$ "$1; HttpOnly; Secure; SameSite=Lax"
</IfModule>

# ===== Rate limiting basique (mod_evasive si disponible) =====
<IfModule mod_evasive20.c>
    DOSHashTableSize    3097
    DOSPageCount        10
    DOSSiteCount        50
    DOSPageInterval     1
    DOSSiteInterval     1
    DOSBlockingPeriod   60
</IfModule>
```

---

## 6. Top 5 — Findings les plus critiques

| # | Sévérité | Finding |
|---|---|---|
| 1 | Critical | **F-02** — Credentials MySQL 1&1 en clair dans `libs/sql.h.php` (rotation immédiate requise) |
| 2 | Critical | **F-08** — Piwik 1.11.1 (2013) : multiples CVE Critical (RCE, SQLi, XSS) — supprimer |
| 3 | Critical | **F-21** — Mass-mailer newsletter HTTP sans auth (mail-flood + leak emails utilisateurs) |
| 4 | Critical | **F-01** — `phpinfo()` exposé via `/info.php` (+ `v2/phpoldfichiers/test.php`, `logs/info.php5`) |
| 5 | High | **F-09 + F-11** — Smarty 3.1.12 sans auto-escape + XSS stockée dans le livre d'or |

---

## 7. Annexes — Fichiers à supprimer en priorité

```
/info.php
/.bash_history
/.viminfo
/.htaccess24
/.htaccess25
/.htaccessgood23
/old/
/under_construction/
/logs/info.php5
/logs/info.pl
/logs/info.py
/v2/bckup/
/v2/phpoldfichiers/
/v2/uploads/   (vider, ne garder que les images effectivement utilisées)
/piwik/        (migrer vers Matomo Cloud / Plausible)
/newsletter/   (les .php doivent passer en CLI)
```
