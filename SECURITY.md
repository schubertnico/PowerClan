# Security Policy

## Unterstuetzte Versionen

| Version | Unterstuetzt | Hinweis |
|---------|--------------|---------|
| 2.1.x | :white_check_mark: | Aktuell, PHP 8.4 |
| 2.0.x | :white_check_mark: | Security Updates |
| 1.x | :x: | Nicht mehr unterstuetzt |

---

## Sicherheitsluecke melden

Wenn Du eine Sicherheitsluecke in PowerClan gefunden hast, melde sie bitte **nicht** oeffentlich in den GitHub Issues.

### Kontakt

| Methode | Adresse |
|---------|---------|
| E-Mail | security@powerscripts.org |
| PGP Key | Auf Anfrage verfuegbar |

### Was wir erwarten

- Beschreibung der Schwachstelle
- Schritte zur Reproduktion
- Betroffene Versionen
- Moegliche Auswirkungen
- Proof of Concept (falls moeglich)

### Was Du erwarten kannst

- Bestaetigung innerhalb von 48 Stunden
- Regelmaessige Updates zum Status
- Fix innerhalb von 14 Tagen (kritisch) / 30 Tagen (normal)
- Anerkennung nach Behebung (falls gewuenscht)
- CVE-Zuweisung bei Bedarf

---

## Implementierte Sicherheitsmassnahmen

### 1. SQL Injection Prevention

**Status:** :white_check_mark: 100% Prepared Statements

Alle Datenbankabfragen verwenden Prepared Statements:

```php
// Korrekte Implementierung
$stmt = $conn->prepare('SELECT * FROM pc_members WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
```

**Tests:** `tests/Security/SQLInjectionTest.php` (5 Tests)

### 2. Cross-Site Scripting (XSS) Schutz

**Status:** :white_check_mark: Alle Ausgaben escaped

Alle Benutzereingaben werden vor der Ausgabe escaped:

```php
// e() Helper Funktion
function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
}

// Verwendung
echo e($userInput);
```

**Tests:** `tests/Security/XSSProtectionTest.php` (14 Tests)

### 3. Cross-Site Request Forgery (CSRF) Schutz

**Status:** :white_check_mark: Alle Formulare geschuetzt

Token-basierter CSRF-Schutz auf allen POST-Formularen:

```php
// Token generieren (in Session)
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// In Formularen
<form method="post">
    <?= csrf_field() ?>
    <!-- Formularfelder -->
</form>

// Server-seitige Validierung
function csrf_check(): void
{
    if (!hash_equals(
        $_SESSION['csrf_token'] ?? '',
        $_POST['csrf_token'] ?? ''
    )) {
        throw new RuntimeException('CSRF token validation failed');
    }
}
```

**Tests:** `tests/Security/CSRFProtectionTest.php` (9 Tests)

### 4. Sichere Passwort-Speicherung

**Status:** :white_check_mark: Bcrypt mit automatischer Migration

```php
// Passwort hashen
$hash = password_hash($password, PASSWORD_DEFAULT);

// Passwort verifizieren
$valid = password_verify($password, $hash);

// Automatische Migration alter base64-Passwoerter
if (base64_decode($storedHash, true) !== false) {
    if ($password === base64_decode($storedHash)) {
        // Automatisch auf bcrypt migrieren
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        // In Datenbank aktualisieren...
    }
}
```

### 5. Session-Sicherheit

**Status:** :white_check_mark: Vollstaendig konfiguriert

```php
// Session-Konfiguration (in php.ini/.docker)
session.cookie_httponly = 1
session.cookie_samesite = "Strict"
session.cookie_secure = 1  // Nur mit HTTPS
session.use_strict_mode = 1
session.use_only_cookies = 1
```

### 6. Input Validierung

**Status:** :white_check_mark: Typ-strenge Validierung

```php
// E-Mail Validierung
function validate_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Integer Validierung
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null) {
    throw new InvalidArgumentException('Invalid ID');
}
```

### 7. File Upload Sicherheit

**Status:** :white_check_mark: Mehrschichtige Validierung

- MIME-Type Ueberpruefung
- Dateiendung Whitelist (jpg, jpeg, gif, png)
- Maximale Dateigroesse
- Sichere Dateinamen (keine Pfad-Traversal)

```php
// Whitelist fuer erlaubte Dateitypen
$allowedTypes = ['image/jpeg', 'image/gif', 'image/png'];
$allowedExtensions = ['jpg', 'jpeg', 'gif', 'png'];

// Validierung
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($uploadedFile);

if (!in_array($mimeType, $allowedTypes, true)) {
    throw new RuntimeException('Invalid file type');
}
```

---

## Statische Analyse

PowerClan verwendet mehrere statische Analyse-Tools:

| Tool | Level | Status |
|------|-------|--------|
| PHPStan | Level 8 | :white_check_mark: 0 Fehler |
| Psalm | Level 4 | :white_check_mark: 0 Fehler |
| PHP-CS-Fixer | PSR-12 | :white_check_mark: Konform |

```bash
# Alle Checks ausfuehren
composer run check

# Einzelne Tools
composer run phpstan    # PHPStan Level 8
composer run psalm      # Psalm Level 4
```

---

## Security Tests

28 automatisierte Security Tests in drei Kategorien:

| Testsuite | Tests | Prueft |
|-----------|-------|--------|
| CSRFProtectionTest | 9 | Token-Generierung, Validierung |
| SQLInjectionTest | 5 | Prepared Statements, Escaping |
| XSSProtectionTest | 14 | HTML-Escaping, Payload-Neutralisierung |

```bash
# Security Tests ausfuehren
composer run test:security
```

---

## Sicherheits-Checkliste fuer Entwickler

### Vor jedem Commit

- [ ] `composer run check` besteht
- [ ] Keine neuen PHPStan/Psalm Fehler
- [ ] Security Tests bestehen
- [ ] Alle SQL-Queries verwenden Prepared Statements
- [ ] Alle Ausgaben sind escaped mit `e()`
- [ ] Alle POST-Formulare haben CSRF-Token

### Vor jedem Release

- [ ] `composer audit` zeigt keine Schwachstellen
- [ ] Alle 67+ Tests bestehen
- [ ] PHPStan Level 8 ohne Fehler
- [ ] Psalm Level 4 ohne Fehler
- [ ] Security Tests (28) bestehen
- [ ] Dokumentation aktualisiert

---

## Bekannte Einschraenkungen

| Einschraenkung | Empfehlung |
|----------------|------------|
| install.php | Nach Installation loeschen |
| Admin-Bereich | Zusaetzlich mit .htaccess schuetzen |
| HTTP | HTTPS dringend empfohlen |
| Alte Browser | Moderne Browser mit SameSite-Support |

---

## Security Changelog

### Version 2.1 (Januar 2026)

- :white_check_mark: PHPStan auf Level 8 erhoeht
- :white_check_mark: Psalm Level 4 hinzugefuegt
- :white_check_mark: 28 Security Tests implementiert
- :white_check_mark: Infection Mutation Testing
- :white_check_mark: GitHub Actions Security Audit

### Version 2.0 (2025)

- :white_check_mark: CSRF-Schutz fuer alle Admin-Formulare
- :white_check_mark: SQL Injection in editmember2.php behoben
- :white_check_mark: Passwort-Hashing von base64 auf bcrypt
- :white_check_mark: Session-Cookies mit HttpOnly und SameSite
- :white_check_mark: Path Traversal in showpic.php behoben

---

## Ressourcen

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
- [CWE/SANS Top 25](https://cwe.mitre.org/top25/)

---

*PowerClan Security Team - security@powerscripts.org*
