# Specifications fonctionnelles - LovelyWedding

> Audit READ-ONLY du site mariage Audrey & Ludovic (03/03/2013), PHP 5.x + Smarty 3.1.12,
> hébergé chez 1&1 sur MySQL. Document destiné à guider la modernisation sans changer le
> comportement utilisateur observable.

---

## 1. Architecture URL & Routing

### 1.1 Fichiers `.htaccess` présents à la racine

| Fichier | Statut | Différences notables |
|---|---|---|
| `.htaccess` | **ACTIF** | Version "complète" : ajoute la règle de réécriture `.xml` et exempte `/newsletter/` de la redirection mobile ET du rewrite v2. |
| `.htaccess24` | inactif (sauvegarde) | Identique à `.htaccess` SANS l'exemption `/newsletter/` du rewrite v2 (newsletter passait donc par `/v2/newsletter/...`). |
| `.htaccess25` | inactif | Identique à `.htaccess24` + exemption `/newsletter/` du rewrite v2. Très proche du fichier actif mais sans la règle `.xml`. |
| `.htaccessgood23` | inactif (ancien) | Version la plus ancienne, sans aucune des deux règles d'extension (`.php` rewrite désactivé via commentaire). |

> Les fichiers `.htaccess24/25/good23` sont des **snapshots** conservés "au cas où". À ignorer pour la migration : seul `.htaccess` est appliqué par Apache.

### 1.2 Règles du `.htaccess` actif (3 blocs)

**Bloc 1 — Redirection mobile (`L,R=301`)**
- Si le User-Agent matche une liste blanche d'appareils mobiles : Windows CE, BlackBerry, Htc, Lg, Sony, NetFront, Opera Mini, Palm OS, Blazer, Elaine, WAP*, Plucker, Nokia, Samsung, Sec-, iPhone, iPad, AvantGo, Android
- ET la requête n'est PAS déjà sur `/v2/mobile/...`
- ET le host n'est PAS `mobile.lovelywedding.fr`
- ET la requête n'est PAS sur `/v2/libs/...`
- ET le host n'est PAS `piwik.lovelywedding.fr`
- ET la requête n'est PAS sur `/newsletter/...`
- → **redirige 301 vers `http://mobile.lovelywedding.fr/$1`** (qui pointe par DNS sur `/v2/mobile/`).

**Bloc 2 — Préfixage automatique `/v2/`**
- Pour tout ce qui n'est pas déjà `/v2/...`, `/v2/mobile/...`, `/newsletter/...`, ni sur le host piwik :
- → réécriture interne `^(.*)$` vers `/v2/$1`.
- C'est ce qui fait que `lovelywedding.fr/maries.html` sert en réalité `/v2/maries.html`.

**Bloc 3 — Extensions implicites**
```
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}\.php -f
RewriteRule ^(.*)$ $1.php

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}\.xml -f
RewriteRule ^(.*)$ $1.xml
```
- Si l'URL ne pointe pas vers un dossier ET qu'un fichier `<url>.php` existe → on le sert.
- Idem pour `.xml`.
- Permet les URLs propres : `/livre_dor_read` → `/v2/livre_dor_read.php`.

### 1.3 Exemple de résolution d'URL : `https://lovelywedding.fr/foo`

1. Apache reçoit `/foo`.
2. UA desktop → bloc 1 sauté. UA mobile → 301 vers `mobile.lovelywedding.fr/foo`.
3. Bloc 2 : `/foo` ne matche pas les exceptions → réécriture interne en `/v2/foo`.
4. Bloc 3 : `/v2/foo` n'existe pas en tant que fichier ; `/v2/foo.php` existe ? alors servi. Sinon `/v2/foo.xml` ? Sinon 404 (ou page d'accueil HTML statique si `foo.html` existe, mais la règle `.html` est commentée — donc en pratique l'utilisateur doit taper `/foo.html` explicitement, OU le fichier `.html` existe et Apache le sert tel quel via le rewrite v2).

> **Cas concret :** `/livre_dor_read` → `/v2/livre_dor_read` → fichier `/v2/livre_dor_read.php` existe → servi.
> `/maries.html` → `/v2/maries.html` → servi en statique.
> `/news` → `/v2/news` → pas de PHP/XML mais `/v2/news.html` existe → ne marche QUE si les liens du site pointent vers `news.html` (ce qui est le cas dans le HTML).

### 1.4 Domaines impliqués
- `www.lovelywedding.fr` / `lovelywedding.fr` — desktop
- `mobile.lovelywedding.fr` — DocumentRoot mappé sur `/v2/mobile/` (ou via le 301)
- `piwik.lovelywedding.fr` — installation Piwik (dossier `/piwik/`, exempté du routing)

---

## 2. Inventaire des pages publiques

Toutes sous `/v2/` (sauf `/newsletter/` qui est privé, voir §3.3).

### 2.1 Pages statiques HTML

| URL publique | Fichier | Fonctionnalité |
|---|---|---|
| `/` ou `/index.html` | `v2/index.html` | Accueil avec compte à rebours JS jusqu'au 03/03/2013 19h30 (`MyCountDown`). |
| `/maries.html` | `v2/maries.html` | Présentation des deux mariés (Audrey & Ludovic), liens vers leurs pages persos. |
| `/audrey.html` | `v2/audrey.html` | Page perso Audrey : description, interview, sliders photos (nini1..9, nous1..9), animation parallax. |
| `/ludovic.html` | `v2/ludovic.html` | Page perso Ludovic (même structure que audrey). |
| `/infos.html` | `v2/infos.html` | Hub d'informations pratiques (liens hôtels / réception / Google Maps). Mailto `contact@lovelywedding.fr`. |
| `/hotels.html` | `v2/hotels.html` | Liste d'hôtels à proximité du lieu de réception. |
| `/reception.html` | `v2/reception.html` | Détails du lieu de réception (Hilton Lyon, Quai Charles de Gaulle). |
| `/news` ou `/news.html` | `v2/news.html` | Fil d'actualités (lightbox sur images). Affiche aussi un lien d'inscription newsletter. |
| `/news2.html` | `v2/news2.html` | Variante / brouillon de news. |
| `/aide.html` | `v2/aide.html` | Tutoriel "comment trouver une URL d'image sur Google" pour le livre d'or (6 étapes, scroll horizontal). |
| `/photos_livre_dor.html` | `v2/photos_livre_dor.html` | Tutoriel "comment héberger sa photo sur minus.com" pour le livre d'or. |
| `/newsletter.html` | `v2/newsletter.html` | Formulaire d'inscription newsletter (identique à `newsletter_form.html`). |
| `/newsletter_form` | `v2/newsletter_form.html` | Idem (cible des liens dans `news.html`). |
| `/googlee337276f525a01ea.html` | idem | Fichier de vérification Google Search Console. |
| `/google_sitemap.xml` | `v2/google_sitemap.xml` + `.gz` racine | Sitemap SEO. |

### 2.2 Pages dynamiques PHP (Smarty)

| URL publique | Fichier | Méthode HTTP | Rôle |
|---|---|---|---|
| `/livre_dor` ou `/livre_dor.php` | `v2/livre_dor.php` + `livre_dor.tpl` | GET (avec optional `?id=`) | Affiche le formulaire d'écriture du livre d'or. Si `?id=N` ET l'IP matche : pré-remplit pour édition. |
| `/livre_dor_read` | `v2/livre_dor_read.php` + `livre_dor_read.tpl` | GET | Affiche le livre d'or (booklet) avec tous les messages `actif=1`. |
| `/livre_dor_write` | `v2/livre_dor_write.php` | POST AJAX | Crée / met à jour un message (validation + insert/update). Retourne du HTML Smarty (`livre_dor_success.tpl` ou `livre_dor_error.tpl`). |
| `/register_newsletter.php` | `v2/register_newsletter.php` | POST AJAX | Inscrit un email dans la table `newsletter`. Retourne JSON `{message, error}`. |

### 2.3 Versions mobile (`/v2/mobile/`)

Miroir simplifié du desktop. Présence des mêmes pages : `index, maries, audrey, ludovic, infos, hotels, reception, news, livre_dor (.html), livre_dor_read.php, livre_dor_write.php, register_newsletter.php, newsletter(_form).html, help.html, photos_livre_dor.html`. Le sous-domaine `mobile.lovelywedding.fr` pointe sur ce dossier. La version mobile est plus minimaliste (CSS différent, pas de fancyBox sur livre d'or write côté mobile car `livre_dor.html` est statique au lieu de Smarty).

### 2.4 Formulaires (récapitulatif)

| Page | `<form>` ID | Méthode | Action | Champs |
|---|---|---|---|---|
| `livre_dor.tpl` | `livreform` | POST (AJAX via `$.post`) | `livre_dor_write` | `nom` (required), `prenom` (honeypot), `id` (édition), `email` (required), `ville`, `image` (URL externe), `message` (required, max 1900 chars) |
| `newsletter.html` / `newsletter_form.html` | `newsletterForm` | POST (AJAX) | `register_newsletter.php` | `email` (required) |

---

## 3. Fonctionnalités dynamiques

### 3.1 Livre d'or

**Composants :**
- `livre_dor.php` (GET) — affiche le formulaire d'écriture, charge un message existant si `?id=N&` et l'IP correspond (édition de son propre message).
- `livre_dor.tpl` — template du formulaire (Smarty). Form submit via `$.post` vers `livre_dor_write`. Inclut fancyBox pour modale d'erreur/succès et bouton "tester image".
- `livre_dor_read.php` (GET) — SELECT all `WHERE actif=1 order by id ASC`. Pour chaque message dont l'IP correspond à `$ip` (variable globale via `tools.h.php`), ajoute `editable=true`.
- `livre_dor_read.tpl` — affiche le livre d'or en booklet (jquery.booklet.1.4.0). Chaque message = 1 page image (gauche) + 1 page texte (droite), avec split automatique si message > 700 caractères. Bouton "aller à la page" en bas. Lightbox sur images. Lien "Editer votre message" affiché si `$page.editable`.
- `livre_dor_write.php` (POST AJAX) — validation + INSERT ou UPDATE.
- `livre_dor_success.tpl` / `livre_dor_error.tpl` — fragments affichés dans la modale fancyBox.

**Validation côté serveur (`livre_dor_write.php`) :**
- `nom` : obligatoire, longueur > 1, accents supprimés via `wd_remove_accents()` (utilise htmlentities + regex).
- `email` : obligatoire, regex (basique mais permissive ; **bug : delimiters `^...^` au lieu de `/.../`** — la regex ne match en réalité jamais, mais comme `preg_match` retourne `false`/`0`, l'erreur "L'adresse mail n'est pas valide" se déclenche → en pratique la validation email est BROKEN sauf si le pattern compile par chance. À vérifier en runtime.).
- `message` : obligatoire, longueur > 1, traité par `prepareString()` = `nl2br(htmlentities(stripslashes(...), ENT_QUOTES, "UTF-8"))`.
- `prenom` : **honeypot anti-bot**. Doit être vide. Si rempli → erreur "BOT DETECTE!".
- `image` (optionnel) : URL externe d'image. Validation :
  - extension dans `['jpg','gif','png']`
  - taille distante < `MAX_IMAGE_SIZE = 200` Ko (vérifiée via `getRemoteFileInfos()` qui fait un HEAD cURL).
- `ville` : libre, optionnel.
- `UNIQUE_MESSAGE_IP` : flag à `false` en prod ; s'il était `true`, refuserait un 2e message le même jour depuis la même IP (sauf si l'utilisateur édite via son `id`).

**Logique d'écriture :**
- Si `isIdForIp($ip, $id)` (le couple `id+ip` existe en base) → UPDATE.
- Sinon → INSERT avec `date=now()`, `actif=1` (publication immédiate, pas de modération).
- Email de notification envoyé à `contact@lovelywedding.fr` via `sendWarnMailForLivreDor()` (avec headers iso-8859-1, contient le nom et l'image).
- Retour : `livre_dor_success.tpl` (avec `$update` true/false : "mis à jour" vs "merci pour votre message... il sera affiché après validation"). **NOTE : le message dit "après validation" mais en réalité `actif=1` est mis à l'insert, donc il n'y a PAS de modération réelle.**

**Modération :** aucune. Le flag `actif` existe mais est forcé à 1 à l'insert. Désactivation manuelle possible en éditant la BDD directement.

**Édition par l'utilisateur :** seulement si on relance `/livre_dor?id=N` depuis la même IP que celle ayant initialement posté. Le nom/email/ville/message/image sont rechargés dans le formulaire.

### 3.2 Inscription Newsletter (`register_newsletter.php`)

**Endpoint :** POST AJAX, retour JSON `{"message": "...", "error": bool}`.

**Logique :**
1. Récupère `$_POST['email']`.
2. Valide (regex idem livre d'or, même bug potentiel).
3. `existsEmail($email)` → SELECT dans `newsletter` ; si déjà inscrit → renvoie `{error:true, message:"Vous êtes déjà inscris"}`.
4. Sinon INSERT `(email, ip, date=now(), actif=1)`.
5. Envoie un email de confirmation à l'utilisateur via `mail()`, sujet `[Lovelywedding] Inscription à la newsletter`. Pas de double opt-in.

**Champs côté client :** simple `<input type='email' name='email'>` avec placeholder cliquable. Soumission AJAX, message inline coloré rouge/vert selon `obj.error`.

### 3.3 Système d'envoi de newsletter (`/newsletter/php/`)

**Dossier protégé** par `.htaccesss` (sic, double `s`) avec `deny from all` sauf localhost + 2 IPs whitelistées (`109.212.170.87`, `195.88.195.146` — probablement IPs de l'admin).

**Fichiers d'envoi** (un par campagne historique) :
- `newsletter.php` — campagne "Sur le départ / voyage de noces"
- `newsletter_normal.php` — campagne "Gagnants du livre d'or"
- `newsletter_j.php` — campagne "Top départ jour J"
- `newsletter_evjf.php` — campagne EVJF (envoyée à une liste hardcodée, PAS depuis la BDD)
- `newsletter_evg.php` — campagne EVG (idem, liste hardcodée)
- `newsletter.html` / `newsletter_evjf.html` / `newsletter_evg.html` — gabarits HTML d'aperçu

**Pattern récurrent :**
```php
define('TEST', true);                  // Mode dry-run : envoie à TEST_EMAIL au lieu de la vraie liste
define('TEST_EMAIL', "ludovic.lasry@gmail.com,...");
$results = SELECT email FROM newsletter_emails;  // <-- table différente de "newsletter" !
foreach (...) { $to .= $email . ","; }
mail("", $subject, $message, $headers); // To: vide, Bcc: liste
```

**Anomalie :** le formulaire d'inscription publique enregistre dans `newsletter` (table), mais les scripts d'envoi lisent `newsletter_emails`. Ce sont **deux tables différentes** (ou un alias / une vue, à vérifier en base). Probablement un héritage : `newsletter_emails` était la liste initiale, `newsletter` la liste des nouveaux inscrits via le formulaire. Une migration de schéma devra unifier.

**Encodage :** tous les mails sont en `iso-8859-1`. Le from est `Lovelywedding newsletter <contact@lovelywedding.fr>`. Pas de désinscription.

### 3.4 Services utilitaires (`libs/*.h.php`)

| Fichier | Rôle |
|---|---|
| `libs/config.h.php` | Active `error_reporting(E_ALL)` mais désactive `display_errors`. |
| `libs/sql.h.php` | Classe `sqlConnector` Singleton qui retourne une instance PDO MySQL. **Credentials hardcodés** : host `db439618928.db.1and1.com`, db `db439618928`, user `dbo439618928`, password `.Sacdoss69`. À déplacer en env vars lors de la migration. |
| `libs/tools.h.php` | Initialise `$ip = $_SERVER["REMOTE_ADDR"]` en variable globale. Fournit : `prepareString()` (échappement + nl2br), `unprepareString()` (inverse), `wd_remove_accents()`, `sendWarnMailForLivreDor($nom, $message, $image)` (mail iso-8859-1 vers contact@). |
| `libs/remote.h.php` | `getRemoteFileInfos($url)` via cURL HEAD : retourne `{status, length}` pour valider taille d'image distante. |
| `libs/Smarty-3.1.12/` | Moteur de templates Smarty 3.1.12 (déposé en lib vendored). |
| `v2/includes.php` | Bootstrap : require Smarty + sql + tools + config (chemins relatifs `../libs/`). |

---

## 4. Modèle de données (déduit)

Base MySQL `db439618928` (hébergement 1&1 mutualisé).

### 4.1 Table `livre_dor`

Déduite des requêtes dans `livre_dor.php`, `livre_dor_read.php`, `livre_dor_write.php`.

```sql
CREATE TABLE livre_dor (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  nom     VARCHAR(?),       -- accents supprimés avant insert
  email   VARCHAR(?),
  ville   VARCHAR(?),
  date    DATETIME,         -- now() à l'insert/update
  message TEXT,             -- htmlentities + nl2br + ENT_QUOTES UTF-8
  image   VARCHAR(?),       -- URL externe d'image (jpg/gif/png, ≤200 Ko)
  ip      VARCHAR(45),      -- IP du posteur (utilisée pour autoriser l'édition)
  actif   TINYINT(1)        -- 1 = publié, 0 = masqué (forcé à 1 à l'insert)
);
```

Index probables : PK sur `id`, index sur `ip` (lookup édition) et sur `actif` (filtre read).

### 4.2 Table `newsletter`

Déduite de `register_newsletter.php`.

```sql
CREATE TABLE newsletter (
  email VARCHAR(?),         -- clé fonctionnelle, vérifiée par existsEmail()
  ip    VARCHAR(45),
  date  DATETIME,           -- now() à l'insert
  actif TINYINT(1)          -- 1 par défaut, aucun mécanisme pour le changer
);
```

Pas de PK explicite dans le code. À vérifier en base.

### 4.3 Table `newsletter_emails`

Utilisée uniquement en LECTURE par les scripts d'envoi (`/newsletter/php/newsletter*.php`).

```sql
CREATE TABLE newsletter_emails (
  email VARCHAR(?)          -- liste des destinataires "officiels"
);
```

> **À clarifier lors de la migration :** unifier `newsletter` (inscrits via formulaire) et `newsletter_emails` (liste manuelle) en une seule table avec source d'inscription.

---

## 5. Dépendances tierces

### 5.1 Côté serveur (PHP)
- **Smarty 3.1.12** (`libs/Smarty-3.1.12/`) — moteur de templates. Cache dans `v2/templates_c/`. Migration : remplacer par Twig ou rendu PHP natif lors du portage.
- **PDO MySQL** (extension PHP standard).
- **cURL** (extension PHP standard) — pour `getRemoteFileInfos()`.
- **mail()** (sendmail système) — envoi via le MTA local 1&1.

### 5.2 Côté client (JS)

| Bibliothèque | Version | Usage |
|---|---|---|
| jQuery | 1.x (`jquery.js` ~95 Ko + `jquery1_2.js` legacy) | Base AJAX & DOM. |
| jQuery UI | 1.8.21 custom | Animations booklet livre d'or. |
| jquery.booklet | 1.4.0 | Effet "livre qui se feuillette" sur `livre_dor_read`. |
| fancyBox | 2.1.1 (`v2/libs/fancyBox/`) | Modales : help iframe, modale d'erreur livre d'or, "tester image". |
| Lightbox | (`js/lightbox.js`) | Zoom images sur livre d'or et news. |
| jquery.parallax | 1.1.3 | Effet parallax sur audrey/ludovic. |
| jquery.nivo.slider | présent | Probablement non utilisé sur les pages actives (à confirmer). |
| Moment.js | présent | Utilisé par `MyCountDown` accueil. |
| `MyCountDown` (`mycountdown.js`) | maison | Compte à rebours vers `2013-03-03T19:30:00`. |
| `PIE.js` | présent | Polyfill CSS3 pour IE. |

### 5.3 Analytics
- **Piwik** auto-hébergé sur `piwik.lovelywedding.fr/piwik.php?idsite=1` — code embarqué en pied de toutes les pages.
- **Google Analytics** classic (`ga.js`, account `UA-36817805-1`) — en parallèle de Piwik.
- **Pas de `tracker.js` custom** détecté à la racine ; le terme "tracker" n'apparaît que dans les README de fancyBox.

### 5.4 Liens externes embarqués
- Google Maps custom (msid `205677463434792390984.0004c960971b9b1c8ff87`) sur `infos.html`.
- `minus.com` (mort) référencé pour l'hébergement d'images dans `photos_livre_dor.html`.
- `wulfy.synology.me:5080` (NAS perso) référencé dans la newsletter EVJF pour partage de photos.
- Ajax googleapis CDN (jQuery) sur `aide.html` et `under_construction/index.html`.

---

## 6. Comportements à PRESERVER absolument

Lors de la migration, les éléments suivants doivent rester strictement identiques côté utilisateur :

1. **URLs sans extension** : `/livre_dor`, `/livre_dor_read`, `/news`, `/newsletter_form`, `/maries.html`, `/hotels`, etc. Tous les liens internes pointent vers ces formes. Mettre en place une couche de routing équivalente.
2. **Préfixage transparent `/v2/`** : la base de code vit dans `/v2/` mais l'utilisateur ne le voit jamais dans l'URL.
3. **Édition de son propre message du livre d'or via `?id=N` + IP match** — UX peu commune mais existante.
4. **Honeypot `prenom`** sur le formulaire livre d'or — anti-spam silencieux à conserver.
5. **Message "il sera affiché après validation"** dans `livre_dor_success.tpl` même si en pratique l'insert est immédiat (le texte est mensonger mais existe — décision produit à prendre : soit ajouter la vraie modération, soit changer le texte).
6. **Compte à rebours sur l'accueil** — il vise une date passée (`2013-03-03T19:30:00`) mais doit rester en place ou être remplacé par un message "le mariage a eu lieu".
7. **Notifications mail à `contact@lovelywedding.fr`** à chaque nouveau message livre d'or.
8. **Confirmation mail à l'inscrit** de la newsletter.
9. **Redirection mobile par User-Agent** — comportement old-school, à remplacer par un design responsive en cible mais à conserver tant que la version mobile reste séparée.
10. **Tracking Piwik + GA double** — selon politique RGPD, mais c'est l'état actuel.
11. **Encodage iso-8859-1** des mails sortants (le contenu littéral contient des caractères accentués mal encodés ; passer en UTF-8 demanderait de réencoder tout le texte).
12. **Splitting auto du message > 700 chars** sur 2 pages du booklet (logique Smarty non triviale ligne 84+ de `livre_dor_read.tpl`).
13. **Limite 1900 caractères** sur le textarea du message (JS côté client, message tronqué + modale fancyBox).
14. **Limite 200 Ko sur les images distantes** du livre d'or (`MAX_IMAGE_SIZE`).
15. **Singleton PDO** (`sqlConnector::getInstance()`) — pattern à conserver ou remplacer par un container DI.
16. **Le dossier `/newsletter/php/` est privé** (accès par IP whitelist). Toute interface admin moderne doit reproduire cette restriction (auth + scope IP ou auth seule).

---

## 7. Zones d'ombre / questions ouvertes

1. **Regex email cassée ?** Le pattern `"^[_a-z0-9-]+...$^"` utilise `^` comme delimiter ce qui est probablement invalide. À tester en runtime : si la regex échoue toujours, **aucune adresse email n'a jamais été acceptée par la validation serveur** depuis la création — pourtant la table contient des entrées. Soit le délimiteur `^` est exotiquement accepté par PCRE, soit les inscriptions/messages réussissent malgré l'erreur (effet de bord de `preg_match` retournant false). À vérifier impérativement avant migration.
2. **Table `newsletter` vs `newsletter_emails`** : aucune jointure dans le code. Sont-elles synchronisées par un cron ? Manuellement ? Les inscrits via le formulaire reçoivent-ils réellement les newsletters ? À investiguer côté DBA.
3. **Modération du livre d'or** : le wording dit "après validation" mais le code publie en direct. Décider : implémenter une vraie modération (admin UI) ou retirer le wording.
4. **Charset global** : mélange ISO-8859-1 (mails, meta tags) et UTF-8 (`prepareString` utilise UTF-8 sur `htmlentities`). La DB est probablement en latin1 ou utf8_general_ci ; à confirmer pour la migration.
5. **Sous-domaine `mobile.lovelywedding.fr`** : DocumentRoot exact ? Probable mapping sur `/v2/mobile/` mais à confirmer avec l'hébergeur.
6. **Sous-domaine `piwik.lovelywedding.fr`** : Piwik est toujours actif ? La table `piwik_*` consomme du quota MySQL. Migration vers Matomo Cloud ou Plausible ?
7. **Le dossier `under_construction/`** est-il accessible publiquement ? Aucune règle htaccess pour y rediriger en cas de maintenance. À conserver comme placeholder ou supprimer.
8. **Le dossier `old/`** racine : non auditée ici (hors scope), probablement V1 du site.
9. **Sécurité** : les credentials DB en clair dans `libs/sql.h.php`, l'absence de CSRF token sur les formulaires AJAX, et la concaténation SQL dans `existsIp($ip, ...)` et `isIdForIp($ip, $id)` (variables passées en strings concatenés, pas en bindValue) sont autant de risques d'injection SQL. À corriger.
10. **`info.php`** racine (17 octets) : probablement un `phpinfo()`. À supprimer en prod (fuite d'info).
11. **Date `2013-03-03`** : tout le contenu est figé sur cette date. La modernisation est-elle pour archiver le site ou pour rénover en vue d'un autre événement ?
12. **Conformité RGPD** : stockage d'IP en clair sur 2 tables, mails de notification, double tracker analytics — politique à définir.

---

*Document généré à partir d'un audit READ-ONLY. Aucun fichier source n'a été modifié.*
