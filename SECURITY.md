# Security Policy

## Unterstützte Versionen

| Version | Unterstützt        |
|---------|--------------------|
| 2.x     | :white_check_mark: |
| 1.x     | :x:                |

## Sicherheitslücke melden

Wenn Du eine Sicherheitslücke in PowerClan gefunden hast, melde sie bitte **nicht** öffentlich in den GitHub Issues.

### Kontakt

- **E-Mail**: security@powerscripts.org
- **PGP Key**: Auf Anfrage verfügbar

### Was wir erwarten

- Beschreibung der Schwachstelle
- Schritte zur Reproduktion
- Betroffene Versionen
- Mögliche Auswirkungen

### Was Du erwarten kannst

- Bestätigung innerhalb von 48 Stunden
- Regelmäßige Updates zum Status
- Anerkennung nach Behebung (falls gewünscht)

## Sicherheitsrichtlinien

PowerClan implementiert folgende Sicherheitsmaßnahmen:

### 1. SQL Injection Prävention

Alle Datenbankabfragen verwenden **Prepared Statements**:

```php
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
```

### 2. Cross-Site Scripting (XSS) Schutz

Alle Benutzereingaben werden vor der Ausgabe escaped:

```php
echo e($userInput); // Verwendet htmlspecialchars()
```

### 3. Cross-Site Request Forgery (CSRF) Schutz

Alle Formulare enthalten CSRF-Tokens:

```php
<form method="post">
    <?= csrf_field() ?>
    <!-- Formularfelder -->
</form>

// Server-seitige Validierung
csrf_check();
```

### 4. Sichere Passwort-Speicherung

Passwörter werden mit **bcrypt** gehasht:

```php
$hash = password_hash($password, PASSWORD_DEFAULT);
$valid = password_verify($password, $hash);
```

### 5. Session-Sicherheit

- `HttpOnly` Cookies (JavaScript kann Sessions nicht lesen)
- `SameSite=Strict` (CSRF-Schutz auf Cookie-Ebene)
- `Secure` Flag in Produktion (nur HTTPS)

### 6. File Upload Validierung

- MIME-Type Überprüfung
- Dateiendung Whitelist
- Maximale Dateigröße

## Sicherheits-Checkliste für Entwickler

Vor jedem Release:

- [ ] Alle SQL-Queries verwenden Prepared Statements
- [ ] Alle Ausgaben sind escaped
- [ ] Alle POST-Formulare haben CSRF-Token
- [ ] Passwörter nur mit `password_hash()`
- [ ] `composer audit` zeigt keine Schwachstellen
- [ ] PHPStan Level 5 ohne Fehler

## Bekannte Einschränkungen

- `install.php` sollte nach der Installation gelöscht werden
- Admin-Bereich sollte zusätzlich durch `.htaccess` geschützt werden
- HTTPS wird dringend empfohlen

## Changelog (Sicherheitsrelevant)

### Version 2.0 (2025)

- CSRF-Schutz für alle Admin-Formulare implementiert
- SQL Injection in `editmember2.php` behoben
- Passwort-Hashing von Base64 auf bcrypt umgestellt
- Session-Cookies mit HttpOnly und SameSite konfiguriert
