# PowerClan

Ein PHP/MySQL basiertes Clan-Portal-Management-System.

**Version:** 2.1 (PHP 8.4)
**Lizenz:** MIT
**Repository:** https://github.com/schubertnico/PowerClan.git

## Beschreibung

PowerClan ist ein klassisches Clan-Portal für Gaming-Communities. Es ermöglicht die Verwaltung von:

- **Mitgliedern** - Profilverwaltung, Kontaktdaten, Hardware-Infos
- **News** - Nachrichten und Ankündigungen
- **Clanwars** - War-Ergebnisse, Berichte, Screenshots

## Systemanforderungen

- PHP 8.4 oder höher
- MySQL 8.0 oder höher
- Apache mit mod_rewrite (optional)
- Docker & Docker Compose (empfohlen)

## Setup mit Docker (Empfohlen)

### 1. Repository klonen

```bash
git clone https://github.com/schubertnico/PowerClan.git
cd PowerClan
```

### 2. Docker-Container starten

```bash
cd .docker
docker compose build
docker compose up -d
```

### 3. Verwendete Ports

| Service     | Port | URL                        |
|-------------|------|----------------------------|
| Web (PHP)   | 8086 | http://localhost:8086      |
| MySQL       | 3316 | localhost:3316             |
| phpMyAdmin  | 8089 | http://localhost:8089      |

### 4. Prüfen ob alles läuft

```bash
# Container-Status prüfen
docker compose ps

# Logs anzeigen
docker compose logs -f

# PHP Error-Log prüfen
cat ../logs/php-error.log
```

### 5. Installation abschließen

1. Öffne http://localhost:8086/install.php
2. Folge dem Installationsassistenten
3. **Wichtig:** Lösche `install.php` nach der Installation!

## Setup ohne Docker

### 1. Konfiguration anpassen

Bearbeite `config.inc.php`:

```php
$mysql = [
    'host'     => 'localhost',
    'user'     => 'dein_user',
    'password' => 'dein_passwort',
    'database' => 'powerclan',
    'port'     => 3306,
];
```

### 2. Datenbank importieren

```bash
mysql -u root -p powerclan < powerclan.sql
```

### 3. Berechtigungen setzen

```bash
chmod 755 logs/
chmod 644 config.inc.php
```

## Nutzung

### Öffentliche Seiten

- **Startseite:** `index.php` - News und aktuelle Wars
- **Mitglieder:** `member.php` - Mitgliederliste und Profile
- **Wars:** `wars.php` - Clanwar-Übersicht und Berichte

### Admin-Bereich

- **Login:** `admin/index.php`
- **Funktionen:**
  - Mitglieder verwalten (hinzufügen, bearbeiten, löschen)
  - News verwalten
  - Clanwars verwalten
  - Konfiguration bearbeiten (nur Superadmin)

## Entwicklung

### PHPStan ausführen

```bash
composer install
composer run phpstan
```

### Rector ausführen

```bash
# Vorschau der Änderungen
composer run rector:dry

# Änderungen anwenden
composer run rector
```

## Lizenz

MIT License - siehe [LICENSE](LICENSE)

Copyright (c) 2001-2025 PowerScripts

---

## Änderungen / Migration auf PHP 8.4

Diese Version wurde umfassend auf PHP 8.4 aktualisiert und enthält wichtige Sicherheitsfixes:

### PHP 8.4 Kompatibilität

- `declare(strict_types=1)` in allen PHP-Dateien aktiviert
- `eregi()` durch `preg_match()` mit `/i` Flag ersetzt
- `stripslashes()` entfernt (Magic Quotes seit PHP 7.0 nicht mehr vorhanden)
- Array-Zugriffe mit Quotes korrigiert (`$row["key"]` statt `$row[key]`)
- Null-Coalescing-Operator (`??`) für sichere Variable-Zugriffe
- Type Declarations für Funktionsparameter und Rückgabewerte

### Sicherheitsfixes

- **SQL Injection Prevention:** Prepared Statements mit `mysqli_prepare()` und `bind_param()`
- **XSS-Schutz:** `htmlspecialchars()` für alle Ausgaben über `e()` Helper-Funktion
- **Passwort-Hashing:** `password_hash()` mit PASSWORD_DEFAULT statt base64
- **Automatische Passwort-Migration:** Bestehende base64-Passwörter werden beim Login automatisch auf sichere Hashes migriert
- **Path Traversal Fix:** `showpic.php` validiert jetzt Pfade gegen Whitelist
- **Cookie-Sicherheit:** HttpOnly und SameSite-Attribute für Session-Cookies
- **Include-Sicherheit:** Dynamische Includes auf `basename()` beschränkt
- **Cookie-Typo behoben:** `setcookie("pcadmin_password]"` → `setcookie("pcadmin_password"`

### Version 2.1 - Umfassende Sicherheitsüberarbeitung (Januar 2026)

**Komplett neu geschriebene Admin-Dateien:**

Alle Admin-Dateien wurden komplett überarbeitet mit:
- Prepared Statements für alle SQL-Abfragen
- XSS-Schutz durch `e()` Helper für alle Ausgaben
- Sichere Variablen-Initialisierung mit Null-Coalescing (`??`)
- Strikte Typisierung (`declare(strict_types=1)`)

| Kategorie | Dateien |
|-----------|---------|
| Mitglieder | `addmember.php`, `editmember.php`, `delmember.php`, `choosemember.php` |
| News | `addnews.php`, `editnews.php`, `delnews.php`, `choosenews.php` |
| Wars | `addwar.php`, `editwar.php`, `delwar.php`, `choosewar.php` |
| Sonstige | `profile.php`, `header.inc.php`, `functions.inc.php` |

**Cookie/Session-Fix:**

- Login-Persistenz behoben - Benutzer bleiben nach Navigation eingeloggt
- `checklogin()` Funktion in `functions.inc.php` korrigiert
- Hash-Vergleich erfolgt jetzt vor `password_verify()` für Cookie-Authentifizierung

**PHPStan:**

- 0 Fehler bei Level 5 Analyse
- Bootstrap-Datei für globale Variablen
- Konfigurierte ignoreErrors für Legacy-Patterns

### Neue Dateien

- `LICENSE` - MIT-Lizenztext
- `README.md` - Diese Dokumentation
- `README.html` - HTML-Version der Dokumentation
- `composer.json` - Composer-Setup mit PHPStan und Rector
- `phpstan.neon` - PHPStan-Konfiguration (Level 5)
- `rector.php` - Rector-Konfiguration für PHP 8.4
- `.docker/` - Docker-Konfiguration (Dockerfile, docker-compose.yml, php.ini)
- `logs/` - Verzeichnis für PHP Error-Logs

### Lizenz-Änderung

- Von GNU GPL v2 auf MIT-Lizenz umgestellt
- Alle PHP-Dateien enthalten den neuen MIT-Lizenz-Header

---

*PowerClan &copy; 2001-2025 PowerScripts*
