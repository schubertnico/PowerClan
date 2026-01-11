# Contributing to PowerClan

Vielen Dank für Dein Interesse an PowerClan! Hier erfährst Du, wie Du zum Projekt beitragen kannst.

## Voraussetzungen

- PHP 8.4+
- MySQL 8.0+
- Composer
- Docker (optional, empfohlen)

## Entwicklungsumgebung einrichten

### Mit Docker (empfohlen)

```bash
git clone https://github.com/schubertnico/PowerClan.git
cd PowerClan
docker-compose up -d
composer install
```

### Ohne Docker

```bash
git clone https://github.com/schubertnico/PowerClan.git
cd PowerClan
composer install
# MySQL-Datenbank erstellen und config.inc.php anpassen
```

## Coding Standards

### Allgemeine Regeln

- **PSR-12** Coding Style
- **declare(strict_types=1)** in allen PHP-Dateien
- Kurze Array-Syntax: `[]` statt `array()`
- Single Quotes für Strings (außer bei Variablen-Interpolation)

### Sicherheitsrichtlinien (WICHTIG!)

1. **SQL Injection verhindern**
   ```php
   // RICHTIG - Prepared Statements
   $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
   $stmt->bind_param('i', $userId);

   // FALSCH - Niemals String-Konkatenation!
   $query = "SELECT * FROM users WHERE id = $userId";
   ```

2. **XSS verhindern**
   ```php
   // RICHTIG - Ausgaben escapen
   echo e($userInput);

   // FALSCH - Niemals unescaped ausgeben!
   echo $userInput;
   ```

3. **CSRF-Token verwenden**
   ```php
   // In Formularen
   <form method="post">
       <?= csrf_field() ?>
       <!-- Formularfelder -->
   </form>

   // In der Verarbeitung
   csrf_check();
   ```

4. **Passwörter sicher speichern**
   ```php
   // RICHTIG
   $hash = password_hash($password, PASSWORD_DEFAULT);

   // FALSCH
   $hash = md5($password);
   $hash = base64_encode($password);
   ```

## Vor dem Commit

Führe alle Checks lokal aus:

```bash
# Alle Checks auf einmal
composer run check

# Oder einzeln:
composer run cs-check    # Code Style prüfen
composer run phpstan     # Statische Analyse
composer run test:unit   # Unit Tests
```

### Code Style automatisch korrigieren

```bash
composer run cs-fix
```

## Pull Request Prozess

1. **Branch erstellen**
   ```bash
   git checkout -b feature/meine-neue-funktion
   ```

2. **Änderungen implementieren**
   - Kleine, fokussierte Commits
   - Aussagekräftige Commit-Messages

3. **Tests schreiben/aktualisieren**
   ```bash
   composer run test
   ```

4. **Checks ausführen**
   ```bash
   composer run check
   ```

5. **Pull Request erstellen**
   - Beschreibe die Änderungen
   - Verlinke relevante Issues
   - Warte auf Code Review

## Tests schreiben

Tests befinden sich im `tests/` Verzeichnis:

```
tests/
├── Unit/           # Einzelne Funktionen testen
├── Integration/    # Zusammenspiel von Komponenten
└── Fixtures/       # Test-Hilfsmittel
```

### Beispiel Unit Test

```php
<?php
declare(strict_types=1);

namespace PowerClan\Tests\Unit;

use PHPUnit\Framework\TestCase;

class MeineFunktionTest extends TestCase
{
    public function testMeineFunktionGibtErwartetesErgebnis(): void
    {
        $result = meineFunktion('input');
        $this->assertEquals('expected', $result);
    }
}
```

## Fragen?

- GitHub Issues: [github.com/schubertnico/PowerClan/issues](https://github.com/schubertnico/PowerClan/issues)
- Website: [powerscripts.org](https://www.powerscripts.org)
