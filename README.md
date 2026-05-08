# PowerClan

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white)
![Tests](https://img.shields.io/badge/tests-237%20passing-brightgreen?style=flat-square)
![Coverage](https://img.shields.io/badge/coverage-85%25-brightgreen?style=flat-square)
![License](https://img.shields.io/badge/license-MIT-yellow?style=flat-square)
![PHPStan](https://img.shields.io/badge/PHPStan-level%208-1abc9c?style=flat-square)
![CI](https://img.shields.io/github/actions/workflow/status/schubertnico/PowerClan/ci.yml?branch=main&style=flat-square&label=CI)

Ein PHP/MySQL-basiertes Clan-Portal-Management-System.

**Version:** 2.2 (PHP 8.4)
**Lizenz:** MIT
**Repository:** <https://github.com/schubertnico/PowerClan>
**Projektseite:** <https://www.powerscripts.org/projects-4.html>

---

## Schnellstart (Docker, < 5 Minuten)

```bash
# 1. Repository klonen
git clone https://github.com/schubertnico/PowerClan.git
cd PowerClan

# 2. PHP-Dependencies installieren
composer install

# 3. Docker-Container starten (Web, MySQL, phpMyAdmin, Mailpit)
docker compose -f .docker/docker-compose.yml up -d

# 4. Im Browser Installation abschließen
#    http://localhost:8086/install.php
#    -> MySQL Hostname: db  /  User: powerclan  /  Passwort: powerclan_secure_2024
#    -> Datenbank: powerclan_v2.0  /  Port: 3306

# 5. Admin-Login-Daten aus der Installations-Mail holen
#    http://localhost:8034  (Mailpit Web-UI)

# 6. Einloggen
#    http://localhost:8086/admin/
```

Nach der Installation wird automatisch `install.lock` erzeugt – der Installer
sperrt sich selbst. Zum erneuten Ausführen `install.lock` löschen.

---

## Inhaltsverzeichnis

- [Features](#features)
- [Systemanforderungen](#systemanforderungen)
- [Installation](#installation)
  - [Mit Docker (empfohlen)](#mit-docker-empfohlen)
  - [Ohne Docker](#ohne-docker)
- [Nutzung](#nutzung)
- [Entwicklung](#entwicklung)
- [Sicherheit](#sicherheit)
- [Changelog](#changelog)
- [Dokumentation](#dokumentation)
- [Kontakt & Impressum](#kontakt--impressum)
- [Lizenz](#lizenz)

---

## Features

- **Mitgliederverwaltung** – Profil, Kontaktdaten, Hardware-Infos, Avatar
- **News-System** – BBCode-Editor, Kategorien, Mehrautoren-Fähigkeit
- **Clanwar-Verwaltung** – Ergebnisse, Berichte, Screenshots, Ligen
- **Rollenbasierte Rechte** – `member_*`, `news_*`, `wars_*`, `superadmin`
- **CSRF-Schutz** auf allen Formularen inkl. Login
- **Serverseitige Sessions** (kein Passwort-Hash im Cookie)
- **Brute-Force-Drossel** (max. 10 Loginversuche/Minute/Session)
- **HTTP-Security-Header** (X-Frame-Options, X-Content-Type-Options, Referrer-Policy)
- **Mailpit-Integration** für lokale Mail-Zustellung
- **Installer-Lockfile** – verhindert versehentliche Re-Installation

---

## Systemanforderungen

| Komponente  | Version  | Hinweis                              |
| ----------- | -------- | ------------------------------------ |
| PHP         | 8.4+     | Mit `mysqli`-Extension               |
| MySQL       | 8.0+     | Oder MariaDB 10.6+                   |
| Composer    | 2.x      | Für Entwicklung / Dependencies       |
| Docker      | 24.x     | Optional, empfohlen                  |

---

## Installation

### Mit Docker (empfohlen)

```bash
# 1. Repository klonen
git clone https://github.com/schubertnico/PowerClan.git
cd PowerClan

# 2. PHP-Dependencies installieren
composer install

# 3. Container starten
docker compose -f .docker/docker-compose.yml up -d

# 4. Container-Status prüfen (alle sollen 'healthy' sein)
docker compose -f .docker/docker-compose.yml ps
```

**Ports & Dienste:**

| Service        | Container             | Port | URL                        |
| -------------- | --------------------- | ---- | -------------------------- |
| Web (PHP 8.4)  | `powerclan_web`       | 8086 | <http://localhost:8086>    |
| MySQL 8.0      | `powerclan_db`        | 3316 | `localhost:3316`           |
| phpMyAdmin     | `powerclan_phpmyadmin`| 8089 | <http://localhost:8089>    |
| Mailpit (SMTP) | `powerclan_mailpit`   | 1034 | `localhost:1034`           |
| Mailpit (UI)   | `powerclan_mailpit`   | 8034 | <http://localhost:8034>    |

**Installation abschließen:**

1. Aufruf: <http://localhost:8086/install.php>
2. Im 2. Schritt MySQL-Host auf `db` setzen (Docker-intern),
   User `powerclan`, Passwort `powerclan_secure_2024`,
   Datenbank `powerclan_v2.0`, Port `3306`
3. Im 4. Schritt Nickname und E-Mail des ersten Superadmins eingeben
4. Generiertes Passwort aus der Mailpit-UI abholen: <http://localhost:8034>
5. Einloggen unter <http://localhost:8086/admin/>

Nach erfolgreichem Lauf wird `install.lock` im Projekt-Root angelegt. Dadurch
wird `install.php` gesperrt (HTTP 403).

### Ohne Docker

```bash
# 1. Repository klonen und Dependencies installieren
git clone https://github.com/schubertnico/PowerClan.git
cd PowerClan
composer install

# 2. Konfiguration anlegen
cp config.inc.php.example config.inc.php
# config.inc.php bearbeiten – MySQL-Zugangsdaten eintragen

# 3. Installation per Webbrowser starten
#    http://<dein-host>/install.php
#    Falls mail() nicht lokal zugestellt wird, Klartext-Passwort wird
#    zur Absicherung im Browser angezeigt.

# 4. Berechtigungen (Linux/macOS)
chmod 755 logs/
chmod 644 config.inc.php
```

---

## Nutzung

### Öffentliche Seiten

| Seite      | Route         | Inhalt                                    |
| ---------- | ------------- | ----------------------------------------- |
| Startseite | `/index.php`  | News-Übersicht + letzte Wars              |
| Mitglieder | `/member.php` | Mitgliederliste + Detailprofile           |
| Wars       | `/wars.php`   | Clanwar-Übersicht + Berichte              |

### Admin-Bereich

| Funktion             | Route                       | Benötigte Rolle   |
| -------------------- | --------------------------- | ----------------- |
| Login                | `/admin/`                   | Mitglied          |
| Eigenes Profil       | `/admin/profile.php`        | Mitglied          |
| News anlegen         | `/admin/addnews.php`        | `news_add`        |
| News editieren       | `/admin/choosenews.php`     | `news_edit`       |
| Wars anlegen         | `/admin/addwar.php`         | `wars_add`        |
| Wars editieren       | `/admin/choosewar.php`      | `wars_edit`       |
| Mitglieder anlegen   | `/admin/addmember.php`      | `member_add`      |
| Mitglieder editieren | `/admin/choosemember.php`   | `member_edit`     |
| Konfiguration        | `/admin/editconfig.php`     | `superadmin`      |

---

## Entwicklung

### Qualitätssicherung

| Tool            | Version | Einstellung | Zweck                    |
| --------------- | ------- | ----------- | ------------------------ |
| **PHPStan**     | 2.x     | Level 8     | Statische Analyse        |
| **Psalm**       | 6.x     | Level 4     | Strikte Typ-Analyse      |
| **PHP-CS-Fixer**| 3.x     | PSR-12      | Code Style               |
| **PHPUnit**     | 11.x    | —           | 237 Tests (Unit/Int/Sec) |
| **Infection**   | 0.32.x  | —           | Mutation Testing         |
| **Rector**      | 2.x     | PHP 8.4     | Automatisches Refactoring|
| **PHPCS**       | 4.x     | PSR-12      | Code Sniffer             |

### Wichtige Composer-Scripts

```bash
composer run check              # Komplett-Check (CS + PHPStan + Psalm + Tests)
composer run phpstan            # PHPStan Level 8
composer run psalm              # Psalm Level 4
composer run test               # Alle PHPUnit-Tests
composer run test:unit          # Nur Unit-Tests
composer run test:integration   # Nur Integration-Tests
composer run test:security      # Nur Security-Tests
composer run test:coverage      # Tests mit HTML-Coverage-Report
composer run infection          # Mutation Testing (benötigt Xdebug)
composer run rector:dry         # Rector-Vorschau
composer run rector             # Rector anwenden
composer run cs-fix             # Code Style automatisch fixen
```

### Testsuite

```text
tests/
├── bootstrap.php             # Test-Initialisierung
├── Unit/                     # Unit-Tests
│   ├── AdminFunctionsTest.php
│   ├── FunctionsTest.php
│   └── ValidationTest.php
├── Integration/              # Integration-Tests (DB + HTTP)
│   ├── IntegrationTestCase.php
│   ├── PageTestCase.php
│   ├── AuthenticationTest.php
│   ├── AdminPageTest.php
│   ├── FrontendPageTest.php
│   ├── MemberTest.php
│   ├── NewsTest.php
│   └── WarTest.php
├── Security/                 # Security-Tests
│   ├── CSRFProtectionTest.php
│   ├── SQLInjectionTest.php
│   └── XSSProtectionTest.php
└── Fixtures/                 # Test-Fixtures
    └── TestDatabase.php
```

### CI/CD (GitHub Actions)

Bei jedem Push/PR laufen:

1. **PHP-Syntax-Check** aller `*.php`-Dateien
2. **PHPStan Level 8**
3. **Psalm Level 4**
4. **PHPUnit** mit Coverage
5. **Infection** Mutation Testing
6. **Composer Audit** (bekannte Sicherheitslücken)
7. **PHP-CS-Fixer** (Code-Style)

---

## Sicherheit

Eingebaute Schutzmaßnahmen:

- **SQL-Injection** – ausschließlich Prepared Statements
- **XSS** – Output-Escaping via `e()`/`htmlspecialchars()`
- **CSRF** – Token auf allen POST-Formularen inkl. Login; Rotation nach Submit
- **Session-Management** – serverseitige PHP-Sessions, `session_regenerate_id()`
  nach Login, Invalidierung bei Passwortwechsel
- **Passwort-Hashing** – `password_hash(..., PASSWORD_DEFAULT)` (bcrypt cost 12)
- **Legacy-Migration** – base64-Passwörter werden beim ersten Login automatisch
  auf bcrypt umgestellt
- **Brute-Force-Drossel** – 10 Fehlversuche/Minute/Session → HTTP 429
- **HTTP-Security-Header** – `X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy`
- **Cookie-Flags** – `HttpOnly`, `SameSite=Lax`, `Secure` unter HTTPS
- **Path-Traversal-Schutz** in `showpic.php`
- **Installer-Lockfile** – `install.php` sperrt sich nach Abschluss

Details siehe [`SECURITY.md`](SECURITY.md).

**Sicherheitslücke melden:** <security@powerscripts.org>

---

## Changelog

### Version 2.2 (April 2026)

**Audit-Fixes (33 Findings):**

- Installer hart abgesichert: Lockfile-Mechanismus, Prepared Statements,
  bcrypt statt base64, CSRF-Schutz, 16-Zeichen-Passwort, Klartext-Hinweis
- Serverseitige PHP-Sessions statt Passwort-Hash im Cookie
- Login-CSRF-Token, Fehlermeldung, serverseitige E-Mail-Format-Prüfung
- Brute-Force-Drossel am Login
- HTTP-Security-Header in `admin/` und `install.php`
- `editmember.php`: CSRF-Schutz, Integer-Bindung für `icq`/`age`,
  try/catch mit sichtbarer Fehlermeldung
- CSRF-Token-Rotation nach jedem erfolgreichen Submit
- `addwar`/`editwar`: `checkdate()`-Validierung + Stunden/Minuten-Range
- News-Titel: kein `strip_tags` mehr (Output-Escaping reicht)
- `choose*.php`: Aktionslinks nur bei Berechtigung
- Mailpit-Container integriert (`powerclan_mailpit`), `msmtp` im Web-Container,
  UI-Warnung bei fehlgeschlagenem Mailversand in `addmember`/`editmember`
- Zahlreiche UX-/Grammatik-Korrekturen
- Dead-Code entfernt (`admin/editmember2.php`)
- Dokumentation: `docs/2026-04-23-Userbereichs-*.md`
  (Bugs, Improvements, Test-Coverage)

**Regressionsprüfung:** 237 PHPUnit-Tests / 460 Assertions grün,
PHPStan Level 8 sauber.

### Version 2.1 (Januar 2026)

- PHPStan Level 8, Psalm Level 4, Infection, PHPCS
- 67+ Unit-Tests, 28 Security-Tests, CI/CD-Pipeline
- CSRF-Schutz auf allen Admin-Formularen
- SQL-Injection-Fixes mit Prepared Statements
- XSS-Schutz via `e()`-Helper
- Passwort-Migration base64 → bcrypt

### Version 2.0 (2025)

- PHP 8.4-Kompatibilität
- `declare(strict_types=1)` in allen Dateien
- Docker-Entwicklungsumgebung
- Umstellung auf MIT-Lizenz

---

## Dokumentation

Weiterführende Dokumente im Repository:

| Datei                                            | Inhalt                                     |
| ------------------------------------------------ | ------------------------------------------ |
| `README.md`                                      | Dieses Dokument                            |
| `readme.html`                                    | HTML-Version der Dokumentation             |
| `SECURITY.md`                                    | Security-Policy + Meldewege                |
| `CONTRIBUTING.md`                                | Mitwirken am Projekt                       |
| `docs/2026-04-23-Userbereichs-bugs.md`           | Audit-Report: 33 Bugs (alle behoben)       |
| `docs/2026-04-23-Userbereichs-improvements.md`   | Audit-Report: 32 UX/Workflow-Vorschläge    |
| `docs/2026-04-23-Userbereichs-test-coverage.md`  | Audit-Report: vollständige Testmatrix      |
| `docs/superpowers/plans/2026-04-23-userbereichs-bugs-fixen.md` | Fix-Plan zu den Audit-Findings |

Online:

- Projekt-Portal: <https://www.powerscripts.org>
- Projektseite: <https://www.powerscripts.org/projects-4.html>
- Quellcode: <https://github.com/schubertnico/PowerClan>

---

## Kontakt & Impressum

**Entwicklung & Vertrieb:**

```text
SchubertMedia
Inhaber: Nico Schubert
Stauffenbergallee 57
99085 Erfurt
Deutschland
```

- Telefon: +49 (0) 3612 3002247 (Mo.–Fr. 9–12 Uhr und 13–18 Uhr)
- Telefax: +49 (0) 3612 3004636
- E-Mail: <info@schubertmedia.de>
- Web: <https://www.powerscripts.org>

**Support:**

- Allgemeine Anfragen: <info@schubertmedia.de>
- Sicherheitshinweise: <security@powerscripts.org>
- Bugs & Features: <https://github.com/schubertnico/PowerClan/issues>

---

## Lizenz

MIT License – siehe [`LICENSE`](LICENSE).

Copyright © 2001–2026 PowerScripts / SchubertMedia

---

*PowerClan – <https://www.powerscripts.org/projects-4.html>*
