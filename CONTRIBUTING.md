# Contributing to PowerClan

Vielen Dank fuer Dein Interesse an PowerClan! Hier erfaehrst Du, wie Du zum Projekt beitragen kannst.

---

## Inhaltsverzeichnis

- [Voraussetzungen](#voraussetzungen)
- [Entwicklungsumgebung](#entwicklungsumgebung)
- [Coding Standards](#coding-standards)
- [Sicherheitsrichtlinien](#sicherheitsrichtlinien)
- [Qualitaetssicherung](#qualitätssicherung)
- [Pull Request Prozess](#pull-request-prozess)
- [Tests schreiben](#tests-schreiben)

---

## Voraussetzungen

| Software | Version | Hinweis |
|----------|---------|---------|
| PHP | 8.4+ | Mit mysqli Extension |
| MySQL | 8.0+ | Oder MariaDB 10.6+ |
| Composer | 2.x | Dependency Management |
| Docker | 24.x | Optional, empfohlen |
| Git | 2.x | Version Control |

---

## Entwicklungsumgebung

### Mit Docker (empfohlen)

```bash
# Repository klonen
git clone https://github.com/schubertnico/PowerClan.git
cd PowerClan

# Dependencies installieren
composer install

# Docker starten
cd .docker
docker compose up -d

# Zurueck zum Projektverzeichnis
cd ..

# Pruefen ob alles funktioniert
composer run check
```

### Ohne Docker

```bash
# Repository klonen
git clone https://github.com/schubertnico/PowerClan.git
cd PowerClan

# Dependencies installieren
composer install

# Konfiguration anpassen
cp config.inc.php.example config.inc.php
# config.inc.php bearbeiten

# Test-Datenbank erstellen
mysql -u root -p -e "CREATE DATABASE powerclan_test"
mysql -u root -p powerclan_test < powerclan.sql
```

---

## Coding Standards

### Allgemeine Regeln

- **PSR-12** Coding Style (automatisch mit PHP-CS-Fixer)
- **declare(strict_types=1)** in allen PHP-Dateien
- Kurze Array-Syntax: `[]` statt `array()`
- Single Quotes fuer Strings (ausser bei Variablen-Interpolation)
- Maximale Zeilenlaenge: 120 Zeichen

### Beispiel einer korrekten PHP-Datei

```php
<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Beschreibung der Datei
 *
 * @copyright 2001-2026 PowerScripts
 * @license   MIT License
 */

function beispielFunktion(string $input): string
{
    if (empty($input)) {
        return '';
    }

    return e($input);
}
```

### Code Style automatisch korrigieren

```bash
# Pruefen
composer run cs-check

# Automatisch beheben
composer run cs-fix
```

---

## Sicherheitsrichtlinien

### 1. SQL Injection verhindern

```php
// RICHTIG - Prepared Statements
$stmt = $conn->prepare('SELECT * FROM pc_members WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

// FALSCH - Niemals String-Konkatenation!
$query = "SELECT * FROM pc_members WHERE id = $userId";  // GEFAEHRLICH!
```

### 2. XSS verhindern

```php
// RICHTIG - Ausgaben escapen
echo e($userInput);
echo '<a href="' . e($url) . '">' . e($linkText) . '</a>';

// FALSCH - Niemals unescaped ausgeben!
echo $userInput;  // GEFAEHRLICH!
```

### 3. CSRF-Token verwenden

```php
// In Formularen
<form method="post">
    <?= csrf_field() ?>
    <input type="text" name="title">
    <button type="submit">Speichern</button>
</form>

// In der Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();  // Wirft Exception bei ungueltigem Token
    // Formular verarbeiten...
}
```

### 4. Passwoerter sicher speichern

```php
// RICHTIG - Bcrypt
$hash = password_hash($password, PASSWORD_DEFAULT);
$valid = password_verify($password, $hash);

// FALSCH - Unsichere Methoden
$hash = md5($password);           // GEFAEHRLICH!
$hash = sha1($password);          // GEFAEHRLICH!
$hash = base64_encode($password); // GEFAEHRLICH!
```

---

## Qualitaetssicherung

### Vor jedem Commit

```bash
# Alle Checks auf einmal (empfohlen)
composer run check
```

Dies fuehrt aus:
1. PHP-CS-Fixer (Code Style)
2. PHPStan Level 8 (Statische Analyse)
3. Psalm Level 4 (Typ-Analyse)
4. PHPUnit Unit Tests

### Einzelne Tools

```bash
# Code Style
composer run cs-check      # Pruefen
composer run cs-fix        # Beheben

# Statische Analyse
composer run phpstan       # PHPStan Level 8
composer run psalm         # Psalm Level 4

# Tests
composer run test:unit     # Unit Tests
composer run test:security # Security Tests
composer run test          # Alle Tests

# Mutation Testing (benoetigt Xdebug)
composer run infection
```

### Pre-Commit Hooks

Pre-Commit Hooks sind automatisch aktiv und pruefen:
- PHP Syntax
- PHPStan auf geaenderten Dateien
- Unit Tests

---

## Pull Request Prozess

### 1. Branch erstellen

```bash
# Feature
git checkout -b feature/meine-neue-funktion

# Bugfix
git checkout -b fix/beschreibung-des-bugs

# Refactoring
git checkout -b refactor/bereich
```

### 2. Aenderungen implementieren

- Kleine, fokussierte Commits
- Aussagekraeftige Commit-Messages
- Bestehende Code-Patterns befolgen

### 3. Qualitaet sicherstellen

```bash
# Alle Checks muessen bestehen
composer run check

# Optional: Mutation Testing
composer run infection
```

### 4. Pull Request erstellen

- **Titel:** Kurze Beschreibung der Aenderung
- **Beschreibung:**
  - Was wurde geaendert?
  - Warum wurde es geaendert?
  - Wie kann man es testen?
- **Labels:** feature, bugfix, refactor, etc.
- **Linked Issues:** #123

### 5. Code Review

- Warte auf Review von Maintainern
- Reagiere auf Feedback
- Aenderungen pushen bei Bedarf

---

## Tests schreiben

### Verzeichnisstruktur

```
tests/
├── Unit/           # Isolierte Funktionstests
├── Integration/    # Tests mit Datenbank
├── Security/       # Sicherheitstests
└── Fixtures/       # Test-Hilfsmittel
```

### Unit Test Beispiel

```php
<?php

declare(strict_types=1);

namespace PowerClan\Tests\Unit;

use PHPUnit\Framework\TestCase;

class MeineFunktionTest extends TestCase
{
    public function testGibtErwartetesErgebnisZurueck(): void
    {
        $result = meineFunktion('input');
        
        $this->assertSame('expected', $result);
    }

    public function testWirftExceptionBeiLeeremInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        meineFunktion('');
    }

    /**
     * @dataProvider inputProvider
     */
    public function testMitVerschiedenenInputs(string $input, string $expected): void
    {
        $this->assertSame($expected, meineFunktion($input));
    }

    public static function inputProvider(): array
    {
        return [
            'normaler input' => ['hello', 'HELLO'],
            'leerer string' => ['', ''],
            'mit sonderzeichen' => ['<script>', '&lt;script&gt;'],
        ];
    }
}
```

### Security Test Beispiel

```php
<?php

declare(strict_types=1);

namespace PowerClan\Tests\Security;

use PHPUnit\Framework\TestCase;

class XSSProtectionTest extends TestCase
{
    /**
     * @dataProvider xssPayloadProvider
     */
    public function testEscapesXSSPayloads(string $payload): void
    {
        $escaped = e($payload);
        
        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertStringNotContainsString('javascript:', $escaped);
    }

    public static function xssPayloadProvider(): array
    {
        return [
            ['<script>alert("xss")</script>'],
            ['<img onerror="alert(1)" src="x">'],
            ['javascript:alert(1)'],
        ];
    }
}
```

### Tests ausfuehren

```bash
# Alle Tests
composer run test

# Nur Unit Tests
composer run test:unit

# Mit Coverage (benoetigt Xdebug)
composer run test:coverage

# Bestimmte Testsuite
vendor/bin/phpunit --testsuite Security
```

---

## Fragen und Support

- **GitHub Issues:** https://github.com/schubertnico/PowerClan/issues
- **Website:** https://www.powerscripts.org
- **Security:** security@powerscripts.org

---

*Danke fuer Deinen Beitrag zu PowerClan!*
