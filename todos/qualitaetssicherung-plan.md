# Umfassender Qualitätssicherungsplan für PowerClan

**Erstellt:** 2026-01-10
**Status:** Offen

---

## Analyse-Ergebnis: Kritische Befunde

| Bereich | Status | Risiko |
|---------|--------|--------|
| CSRF-Schutz | **Nicht vorhanden** | HOCH |
| SQL Injection | **2 Dateien betroffen** | KRITISCH |
| Session/Cookies | Gut (HttpOnly, SameSite) | NIEDRIG |
| Input Validation | Gemischt | MITTEL |
| Prepared Statements | 95% umgesetzt | MITTEL |
| CI/CD Pipeline | **Nicht vorhanden** | HOCH |
| Pre-commit Hooks | **Nicht vorhanden** | MITTEL |

---

## Phase 1: Kritische Sicherheitslücken (SOFORT)

### 1.1 SQL Injection beheben

- [ ] `admin/editmember2.php:54` - SELECT mit String-Konkatenation
- [ ] `admin/editmember2.php:64` - SELECT mit String-Konkatenation
- [ ] `admin/editmember2.php:96` - UPDATE mit String-Konkatenation
- [ ] `admin/editmember2.php:104` - UPDATE mit String-Konkatenation
- [ ] `install.php:326` - INSERT mit String-Konkatenation

**Beispiel des Problems (editmember2.php:96):**
```php
// GEFÄHRLICH - SQL Injection möglich
$update = "UPDATE pc_members SET nick='$nick' WHERE id='$row[id]'";

// LÖSUNG - Prepared Statement
$stmt = $conn->prepare("UPDATE pc_members SET nick=? WHERE id=?");
$stmt->bind_param('si', $nick, $row['id']);
$stmt->execute();
```

### 1.2 CSRF-Token implementieren

- [ ] CSRF-Token Generator erstellen (`functions.inc.php`)
- [ ] Token-Validierung implementieren
- [ ] Alle Formulare mit Hidden Field erweitern:
  - [ ] `admin/addmember.php`
  - [ ] `admin/editmember.php`
  - [ ] `admin/editmember2.php`
  - [ ] `admin/delmember.php`
  - [ ] `admin/addnews.php`
  - [ ] `admin/editnews.php`
  - [ ] `admin/delnews.php`
  - [ ] `admin/addwar.php`
  - [ ] `admin/editwar.php`
  - [ ] `admin/delwar.php`
  - [ ] `admin/editconfig.php`
  - [ ] `admin/profile.php`

**Implementierung:**
```php
// Token generieren (in header.inc.php)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Token in Formular
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

// Token validieren
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    die('CSRF-Token ungültig');
}
```

### 1.3 Passwort-Hashing korrigieren

- [ ] `admin/editmember2.php:103` - base64_encode() durch password_hash() ersetzen

**Problem:**
```php
// UNSICHER - Base64 ist KEINE Verschlüsselung!
$newpassword = base64_encode($password1);
```

**Lösung:**
```php
// SICHER - Bcrypt Hash
$newpassword = password_hash($password1, PASSWORD_DEFAULT);
```

---

## Phase 2: Automatisierte Qualitätssicherung

### 2.1 GitHub Actions CI/CD Pipeline

- [ ] `.github/workflows/ci.yml` erstellen

```yaml
name: CI

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: powerclan_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: mysqli, mbstring
          coverage: xdebug

      - name: Install Dependencies
        run: composer install --prefer-dist --no-progress

      - name: PHP Syntax Check
        run: find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;

      - name: PHPStan
        run: vendor/bin/phpstan analyse --level=5

      - name: PHPUnit
        run: vendor/bin/phpunit --coverage-text
        env:
          DB_HOST: 127.0.0.1
          DB_USER: root
          DB_PASSWORD: root
          DB_DATABASE: powerclan_test

      - name: Security Check
        run: composer audit
```

### 2.2 Pre-commit Hooks

- [ ] `composer.json` Scripts erweitern
- [ ] `.git/hooks/pre-commit` erstellen

**composer.json erweitern:**
```json
{
  "scripts": {
    "check": [
      "@php-lint",
      "@phpstan",
      "@test:unit"
    ],
    "php-lint": "find . -name '*.php' -not -path './vendor/*' -exec php -l {} \\;",
    "phpstan": "phpstan analyse --level=5",
    "test": "phpunit",
    "test:unit": "phpunit --testsuite Unit",
    "test:integration": "phpunit --testsuite Integration",
    "cs-fix": "php-cs-fixer fix",
    "cs-check": "php-cs-fixer fix --dry-run --diff"
  }
}
```

**.git/hooks/pre-commit:**
```bash
#!/bin/bash
echo "Running pre-commit checks..."

# PHP Syntax
echo "Checking PHP syntax..."
for file in $(git diff --cached --name-only --diff-filter=ACM | grep '\.php$'); do
    php -l "$file" > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        echo "Syntax error in $file"
        exit 1
    fi
done

# PHPStan
echo "Running PHPStan..."
composer run phpstan
if [ $? -ne 0 ]; then
    echo "PHPStan found errors"
    exit 1
fi

echo "All checks passed!"
exit 0
```

### 2.3 PHP-CS-Fixer

- [ ] `composer require --dev friendsofphp/php-cs-fixer`
- [ ] `.php-cs-fixer.php` erstellen

```php
<?php
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('.docker');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => true,
        'single_quote' => true,
    ])
    ->setFinder($finder);
```

---

## Phase 3: Erweiterte Tests

### 3.1 Security Tests

- [ ] `tests/Security/CSRFProtectionTest.php`
- [ ] `tests/Security/SQLInjectionTest.php`
- [ ] `tests/Security/XSSProtectionTest.php`
- [ ] `tests/Security/FileUploadTest.php`

**tests/Security/CSRFProtectionTest.php:**
```php
<?php
class CSRFProtectionTest extends TestCase
{
    public function testFormWithoutTokenIsRejected(): void
    {
        // POST ohne CSRF-Token sollte abgelehnt werden
    }

    public function testFormWithInvalidTokenIsRejected(): void
    {
        // POST mit falschem Token sollte abgelehnt werden
    }

    public function testFormWithValidTokenIsAccepted(): void
    {
        // POST mit korrektem Token sollte akzeptiert werden
    }
}
```

### 3.2 E2E Tests (Browser-basiert)

- [ ] Playwright oder Selenium einrichten
- [ ] Login/Logout Tests
- [ ] CRUD-Operationen testen
- [ ] Formular-Validierung testen

### 3.3 Performance Tests

- [ ] Response Time Benchmarks
- [ ] Memory Usage Tests
- [ ] Database Query Analyse

---

## Phase 4: Dokumentation & Standards

### 4.1 Zu erstellende Dateien

- [ ] `CONTRIBUTING.md`
- [ ] `SECURITY.md`
- [ ] `docs/CODING_STANDARDS.md`

### 4.2 CONTRIBUTING.md Inhalt

```markdown
# Contributing to PowerClan

## Voraussetzungen
- PHP 8.4+
- MySQL 8.0+
- Composer

## Setup
1. `git clone ...`
2. `composer install`
3. `docker-compose up -d`

## Coding Standards
- PSR-12
- Alle SQL: Prepared Statements
- Alle Outputs: `e()` oder `htmlspecialchars()`
- Alle Formulare: CSRF-Token

## Vor dem Commit
composer run check

## Pull Request Prozess
1. Branch von `develop` erstellen
2. Änderungen implementieren
3. Tests schreiben/aktualisieren
4. `composer run check` ausführen
5. Pull Request erstellen
```

### 4.3 SECURITY.md Inhalt

```markdown
# Security Policy

## Unterstützte Versionen
| Version | Unterstützt |
|---------|-------------|
| 2.x     | Ja          |
| 1.x     | Nein        |

## Sicherheitslücke melden
E-Mail: security@powerscripts.org

## Sicherheitsrichtlinien
1. SQL Injection: Nur Prepared Statements
2. XSS: Alle Ausgaben escapen mit `e()`
3. CSRF: Token auf allen Formularen
4. Passwörter: Nur `password_hash()`
5. Uploads: MIME-Type Validierung
```

---

## Phase 5: Monitoring & Wartung

### 5.1 Error Logging

- [ ] Zentrales Error-Handling implementieren
- [ ] Strukturierte Logs (JSON)
- [ ] Log-Rotation einrichten

### 5.2 Dependency Management

```bash
# Wöchentlich ausführen:
composer audit          # Security vulnerabilities
composer outdated       # Veraltete Packages
```

### 5.3 Automatische Benachrichtigungen

- [ ] GitHub Actions: E-Mail bei Failed Tests
- [ ] Dependabot aktivieren für Security Updates

---

## Priorisierte Aufgabenliste

| Prio | Aufgabe | Aufwand | Status |
|------|---------|---------|--------|
| 1 | SQL Injection editmember2.php | 1h | [ ] Offen |
| 2 | CSRF-Token implementieren | 2-3h | [ ] Offen |
| 3 | Passwort-Hashing korrigieren | 30min | [ ] Offen |
| 4 | GitHub Actions CI/CD | 1h | [ ] Offen |
| 5 | Pre-commit Hooks | 30min | [ ] Offen |
| 6 | PHP-CS-Fixer | 30min | [ ] Offen |
| 7 | Security Tests | 2h | [ ] Offen |
| 8 | Dokumentation | 1h | [ ] Offen |

---

## Checkliste vor Release

- [ ] Alle Tests bestehen (`composer run test`)
- [ ] PHPStan ohne Fehler (`composer run phpstan`)
- [ ] Keine bekannten Vulnerabilities (`composer audit`)
- [ ] CSRF-Token auf allen Formularen
- [ ] Keine SQL-String-Konkatenation
- [ ] Alle Passwörter mit password_hash()
- [ ] Error-Log leer
- [ ] Dokumentation aktuell
