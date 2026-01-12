# PowerClan

Ein PHP/MySQL basiertes Clan-Portal-Management-System.

**Version:** 2.1 (PHP 8.4)  
**Lizenz:** MIT  
**Repository:** https://github.com/schubertnico/PowerClan.git

---

## Inhaltsverzeichnis

- [Beschreibung](#beschreibung)
- [Systemanforderungen](#systemanforderungen)
- [Installation](#installation)
  - [Mit Docker (Empfohlen)](#mit-docker-empfohlen)
  - [Ohne Docker](#ohne-docker)
- [Nutzung](#nutzung)
- [Entwicklung](#entwicklung)
  - [Qualitaetssicherung](#qualitaetssicherung)
  - [Verfuegbare Scripts](#verfuegbare-scripts)
- [Sicherheit](#sicherheit)
- [Changelog](#changelog)
- [Lizenz](#lizenz)

---

## Beschreibung

PowerClan ist ein klassisches Clan-Portal fuer Gaming-Communities. Es ermoeglicht die Verwaltung von:

- **Mitgliedern** - Profilverwaltung, Kontaktdaten, Hardware-Infos
- **News** - Nachrichten und Ankuendigungen
- **Clanwars** - War-Ergebnisse, Berichte, Screenshots

---

## Systemanforderungen

| Komponente | Version | Hinweis |
|------------|---------|---------|
| PHP | 8.4+ | Mit mysqli-Extension |
| MySQL | 8.0+ | Oder MariaDB 10.6+ |
| Composer | 2.x | Fuer Entwicklung |
| Docker | 24.x | Optional, empfohlen |

---

## Installation

### Mit Docker (Empfohlen)

```bash
# 1. Repository klonen
git clone https://github.com/schubertnico/PowerClan.git
cd PowerClan

# 2. Dependencies installieren
composer install

# 3. Docker-Container starten
cd .docker
docker compose up -d

# 4. Container-Status pruefen
docker compose ps
```

**Ports:**

| Service | Port | URL |
|---------|------|-----|
| Web (PHP) | 8086 | http://localhost:8086 |
| MySQL | 3316 | localhost:3316 |
| phpMyAdmin | 8089 | http://localhost:8089 |

**Installation abschliessen:**

1. Oeffne http://localhost:8086/install.php
2. Folge dem Installationsassistenten
3. **Wichtig:** Loesche install.php nach der Installation!

### Ohne Docker

```bash
# 1. Repository klonen
git clone https://github.com/schubertnico/PowerClan.git
cd PowerClan

# 2. Dependencies installieren
composer install

# 3. Konfiguration anpassen
cp config.inc.php.example config.inc.php
# config.inc.php bearbeiten mit Datenbankzugangsdaten

# 4. Datenbank importieren
mysql -u root -p powerclan < powerclan.sql

# 5. Berechtigungen setzen (Linux/Mac)
chmod 755 logs/
chmod 644 config.inc.php
```

---

## Nutzung

### Oeffentliche Seiten

| Seite | URL | Beschreibung |
|-------|-----|--------------|
| Startseite | index.php | News und aktuelle Wars |
| Mitglieder | member.php | Mitgliederliste und Profile |
| Wars | wars.php | Clanwar-Uebersicht und Berichte |

### Admin-Bereich

| Funktion | URL | Berechtigung |
|----------|-----|--------------|
| Login | admin/index.php | Alle Admins |
| Mitglieder verwalten | admin/choosemember.php | member_* |
| News verwalten | admin/choosenews.php | news_* |
| Wars verwalten | admin/choosewar.php | wars_* |
| Konfiguration | admin/editconfig.php | Nur Superadmin |

---

## Entwicklung

### Qualitaetssicherung

PowerClan verwendet moderne PHP-Qualitaetswerkzeuge:

| Tool | Version | Level | Beschreibung |
|------|---------|-------|--------------|
| **PHPStan** | 2.x | Level 8 | Statische Analyse |
| **Psalm** | 6.x | Level 4 | Strikte Typ-Analyse |
| **PHP-CS-Fixer** | 3.x | PSR-12 | Code Style |
| **PHPUnit** | 11.x | - | Unit Tests |
| **Infection** | 0.32.x | - | Mutation Testing |
| **Rector** | 2.x | PHP 8.4 | Automatische Refactoring |
| **PHPCS** | 4.x | PSR-12 | Code Sniffer |

### Verfuegbare Scripts

```bash
# Alle Checks auf einmal (CS, PHPStan, Psalm, Tests)
composer run check

# Code Style
composer run cs-check      # Pruefen
composer run cs-fix        # Automatisch beheben

# Statische Analyse
composer run phpstan       # PHPStan Level 8
composer run psalm         # Psalm Level 4

# Tests
composer run test          # Alle Tests
composer run test:unit     # Unit Tests
composer run test:integration  # Integration Tests
composer run test:security # Security Tests
composer run test:coverage # Mit Coverage Report

# Mutation Testing
composer run infection     # Lokal (benoetigt Xdebug)
composer run infection:ci  # CI mit Coverage

# Code Modernisierung
composer run rector:dry    # Vorschau
composer run rector        # Anwenden

# Code Sniffer
composer run phpcs         # Pruefen
composer run phpcbf        # Automatisch beheben
```

### Test-Struktur

```
tests/
├── bootstrap.php           # Test-Initialisierung
├── Unit/                   # Unit Tests (67+ Tests)
│   ├── FunctionsTest.php
│   ├── AdminFunctionsTest.php
│   └── ValidationTest.php
├── Integration/            # Integration Tests
│   ├── IntegrationTestCase.php
│   ├── AuthenticationTest.php
│   ├── MemberTest.php
│   ├── NewsTest.php
│   └── WarTest.php
├── Security/               # Security Tests (28 Tests)
│   ├── CSRFProtectionTest.php
│   ├── SQLInjectionTest.php
│   └── XSSProtectionTest.php
└── Fixtures/
    └── TestDatabase.php
```

### CI/CD Pipeline

GitHub Actions fuehrt bei jedem Push/PR aus:

1. **PHP Syntax Check** - Alle PHP-Dateien
2. **PHPStan Level 8** - Statische Analyse
3. **Psalm Level 4** - Typ-Analyse
4. **PHPUnit** - Tests mit Coverage
5. **Infection** - Mutation Testing
6. **Security Audit** - composer audit
7. **PHP-CS-Fixer** - Code Style Check

---

## Sicherheit

PowerClan implementiert moderne Sicherheitsmassnahmen:

- **SQL Injection Prevention** - 100% Prepared Statements
- **XSS-Schutz** - e() Helper fuer alle Ausgaben
- **CSRF-Token** - Auf allen Formularen
- **Bcrypt Passwort-Hashing** - PASSWORD_DEFAULT
- **Session-Sicherheit** - HttpOnly, SameSite, Secure

Siehe [SECURITY.md](SECURITY.md) fuer Details.

**Sicherheitsluecke melden:** security@powerscripts.org

---

## Changelog

### Version 2.1 (Januar 2026)

**Qualitaetssicherung:**
- PHPStan auf Level 8 erhoeht
- Psalm Level 4 hinzugefuegt
- Infection Mutation Testing konfiguriert
- 67+ Unit Tests, 28 Security Tests
- GitHub Actions CI/CD Pipeline

**Sicherheit:**
- CSRF-Schutz auf allen Admin-Formularen
- SQL Injection komplett behoben (Prepared Statements)
- XSS-Schutz mit e() Helper
- Sichere Passwort-Migration (base64 -> bcrypt)

### Version 2.0 (2025)

- PHP 8.4 Kompatibilitaet
- declare(strict_types=1) in allen Dateien
- Moderne Array-Syntax und Null-Coalescing
- Docker-Entwicklungsumgebung
- MIT-Lizenz

---

## Lizenz

MIT License - siehe [LICENSE](LICENSE)

Copyright (c) 2001-2026 PowerScripts

---

*PowerClan - https://www.powerscripts.org*
