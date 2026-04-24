# Userbereichs-Audit – Bugs

**Projekt:** PowerClan (PHP/MySQL Clan-Portal, Version 2.00)
**Datum:** 2026-04-23
**Auditor:** Senior QA / Dev-Auditor
**Zielsystem:** http://localhost:8086/ (Docker, Apache 2.4.65 / PHP 8.4.16)
**Geltungsbereich:** Userbereich = Admin-/Member-Login-Bereich unter `/admin/` inklusive der öffentlich sichtbaren Member-/War-/News-Seiten, die direkt vom eingeloggten Bereich befüllt werden.

> **Allgemeiner Hinweis:** PowerClan besitzt keinen öffentlichen Registrierungs-Prozess. Neue Mitglieder werden entweder per `install.php` (Superadmin-Erstanlage) oder per `admin/addmember.php` durch einen bestehenden Superadmin angelegt. Dieser Audit behandelt den gesamten eingeloggten Bereich als "Userbereich".

> **Status aller Einträge: BEHOBEN am 2026-04-23**
>
> Fix-Plan: `docs/superpowers/plans/2026-04-23-userbereichs-bugs-fixen.md`
> Regressionsprüfung: PHPUnit 237 Tests / 460 Assertions – alle grün.
>
> Ausnahmen:
> - **BUG-022** (Self-Delete des Superadmin): Fehldiagnose im Audit – `admin/delmember.php` hatte bereits Self-Delete- und Superadmin-Schutz.
> - **BUG-023** (Mailpit): am 2026-04-24 nachgezogen – Mailpit-Container `powerclan_mailpit` (SMTP 1034, UI 8034), `msmtp` im Web-Container, `sendmail_path` auf `/usr/bin/msmtp -t`, UI-Warnung bei `addmember.php`/`editmember.php` bei fehlgeschlagenem Mailversand.

---

## Bug-Index

| ID | Bereich | Schwere | Titel |
|---|---|---|---|
| BUG-001 | Installer | Blocker | `install.php` nach Installation weiterhin öffentlich erreichbar |
| BUG-002 | Installer | Blocker | Installer speichert Passwort per `base64_encode` statt Hashing |
| BUG-003 | Installer | Blocker | SQL-Injection in `install.php` Page 4 (INSERT INTO pc_members) |
| BUG-004 | Installer | Hoch | `mail()` ohne Validierung an fest codierte externe Adresse + Mail-Header-Injection |
| BUG-005 | Installer | Mittel | Installer-Mail enthält leere URL (`$SCRIPT_NAME`/`$SERVER_NAME` = "") |
| BUG-006 | Installer | Mittel | `generate_password()` liefert nur 8-stellige Zeichenkette |
| BUG-007 | Installer | Mittel | Passwort im Klartext auf Ergebnisseite sichtbar |
| BUG-008 | Installer | Niedrig | Veraltetes Copyright "2001-20023" im Installer-Footer |
| BUG-009 | Installer | Mittel | Kein CSRF-Schutz im Installer-Flow |
| BUG-010 | Auth | Hoch | Login-Formular ohne CSRF-Token |
| BUG-011 | Auth | Hoch | Cookie-Session verwendet bcrypt-Hash als Session-Token |
| BUG-012 | Auth | Mittel | Keine Rate-Limitierung / Brute-Force-Schutz am Login |
| BUG-013 | Auth | Niedrig | Kein Feedback bei fehlgeschlagenem Login (stummer Re-Render) |
| BUG-014 | Auth | Mittel | Ungültiges E-Mail-Format am Login wird nicht serverseitig validiert |
| BUG-015 | Auth | Mittel | Fehlende HTTP-Security-Header (X-Frame-Options, CSP, X-Content-Type-Options, Referrer-Policy) |
| BUG-016 | Auth | Niedrig | Session-Cookie ohne `Secure`-Flag |
| BUG-017 | editmember | **Blocker** | **Kein CSRF-Schutz** – Rechte-Eskalation per Cross-Site-Request möglich |
| BUG-018 | editmember | Hoch | Fatal Error bei leerem `icq`/`age` (strict-mode `INT`-Spalte, `'s'`-Bindung) |
| BUG-019 | editmember | Hoch | Fehler-Output landet nur im PHP-Log; Seite rendert stumm nur `<center>` |
| BUG-020 | editmember / profile | Mittel | Session/Cookie nicht rotiert nach Passwortwechsel |
| BUG-021 | choosenews / choosewar / choosemember | Mittel | Edit-/Löschen-Links werden auch Usern ohne entsprechende Rechte angezeigt |
| ~~BUG-022~~ | delmember | ~~Mittel~~ | ~~Selbstlöschung des eingeloggten (einzigen) Superadmin möglich~~ – **Fehldiagnose** (Schutz existiert bereits) |
| BUG-023 | addmember / editmember | Mittel | Erfolgsmeldung "per E-Mail benachrichtigt" obwohl kein Mailserver konfiguriert |
| BUG-024 | addwar | Hoch | Keine Datums-/Zeit-Validierung (`mktime(25,99,…31.02.2026)` wird stillschweigend normalisiert) |
| BUG-025 | addnews / editnews | Mittel | `strip_tags` im Titel verschluckt legitime Zeichen (Titel mit `<` wird entstellt) |
| BUG-026 | Profile | Niedrig | `maxlength=25` begrenzt Passwort auf 25 Zeichen (Schwachstelle für lange Passphrasen) |
| BUG-027 | admin/index.php | Niedrig | `$i++` fehlt nach `news_del=YES` – Rechte-Zähler kann falsch werden |
| BUG-028 | editmember2.php | Niedrig | Dead-Code: nicht verlinkt, lädt aber Admin-Header und führt DB-Abfragen aus |
| BUG-029 | CSRF-Mechanik | Mittel | CSRF-Token wird nach erfolgreichem POST nicht rotiert (`csrf_regenerate` nirgends aufgerufen) |
| BUG-030 | CSRF-Mechanik | Niedrig | CSRF-Fehler liefert HTTP 200 statt 403 (Output vor `http_response_code`) |
| BUG-031 | Grammatik | Niedrig | "Du hast **keine** Zugang zu dieser Funktion!" (4× identisch) – sollte "keinen Zugang" |
| BUG-032 | Profile | Niedrig | "Daten zur**ü**cksetzten" (Reset-Button) – Rechtschreibfehler |
| BUG-033 | header (öffentlich) | Mittel | Link "Admin" steht in der öffentlichen Navigation und führt ohne Login-Prompt auf `/admin/` |

---

## BUG-001: install.php nach Installation weiterhin öffentlich erreichbar

- **Bereich:** Installer
- **URL / Route:** `GET http://localhost:8086/install.php`
- **Reproduktionsschritte:**
  1. Browser öffnen, `http://localhost:8086/install.php` aufrufen.
- **Erwartet:** Bei bereits abgeschlossener Installation sollte die Seite den Zugriff verweigern (Redirect, HTTP 403, oder eine Entfernung-Empfehlung durchsetzen/prüfen).
- **Tatsächlich:** Seite "PowerClan Installation" wird angezeigt. Links "Installation" und "Update von 1.0" sind klickbar und können die komplette Datenbank zurücksetzen.
- **Fehlerart:** Sicherheit / Funktional
- **Schweregrad:** Blocker
- **Konsole / Stacktrace:** keine
- **Netzwerkhinweise:** HTTP 200 OK auf `GET /install.php`
- **Status:** Offen – nicht beheben

## BUG-002: Installer speichert Passwort per `base64_encode` statt Hashing

- **Bereich:** Installer
- **URL / Route:** `install.php?type=install&page=4` (POST)
- **Reproduktionsschritte:** Code-Review `install.php:330-331`.
- **Erwartet:** Passwörter werden mit `password_hash(..., PASSWORD_DEFAULT)` gespeichert.
- **Tatsächlich:** `$password_coded = base64_encode((string) $password);` gefolgt von `INSERT INTO pc_members (..., password, ...) VALUES (..., '$password_coded', ...)`. Das ist reversible Kodierung, kein Hash.
- **Fehlerart:** Sicherheit (Kryptografie)
- **Schweregrad:** Blocker
- **Status:** Offen – nicht beheben

## BUG-003: SQL-Injection in `install.php` Page 4

- **Bereich:** Installer
- **URL / Route:** `install.php?type=install&page=4` (POST-Parameter `nickname`, `email`)
- **Reproduktionsschritte:** Code-Review `install.php:331`. String-Konkatenation ohne Prepared Statement oder `mysqli_real_escape_string`.
- **Erwartet:** Prepared Statement mit `bind_param`.
- **Tatsächlich:** `$insert = "INSERT INTO pc_members (nick, email, password, ...) VALUES ('" . $nickname . "', '" . $email . "', ...)";` – klassische SQL-Injection via `nickname`/`email`.
- **Fehlerart:** Sicherheit (SQL-Injection)
- **Schweregrad:** Blocker
- **Status:** Offen – nicht beheben

## BUG-004: `mail()` im Installer ohne Validierung, mit potenzieller Header-Injection

- **Bereich:** Installer
- **URL / Route:** `install.php?type=install&page=4`
- **Reproduktionsschritte:** Code-Review `install.php:328,333,342`.
- **Erwartet:** Mail-Adresse und -Header werden validiert; keine automatische Benachrichtigung externer Dritter.
- **Tatsächlich:**
  - Mail wird per `mail('register@powerscripts.org', ...)` an fest codierte Drittadresse gesendet.
  - Der `FROM`-Header ist ein String-Literal (`'FROM: PowerScript Autoregister <register@powerscripts.org'`) ohne abschliessendes `>`.
  - `$nickname`/`$email` fließen ungefiltert in Body und Subject – potenzielle Mail-Header-Injection durch CRLF in Eingaben.
- **Fehlerart:** Sicherheit (Mail-Injection / Daten-Leak)
- **Schweregrad:** Hoch
- **Status:** Offen – nicht beheben

## BUG-005: Installer-Mail enthält leere URL

- **Bereich:** Installer
- **URL / Route:** `install.php?type=install&page=4`
- **Reproduktionsschritte:** Code-Review `install.php:324-328`. Lokale Variablen `$SCRIPT_NAME = ''; $SERVER_NAME = '';` werden nicht mit `$_SERVER` befüllt.
- **Erwartet:** Korrekte URL-Berechnung aus `$_SERVER['SERVER_NAME']` / `$_SERVER['SCRIPT_NAME']`.
- **Tatsächlich:** `$url = '' . ''` → Mail meldet "… wurde auf von nickname (email) installiert!" ohne Domain.
- **Fehlerart:** Funktional
- **Schweregrad:** Mittel
- **Status:** Offen – nicht beheben

## BUG-006: Installer-Passwort nur 8 Zeichen

- **Bereich:** Installer
- **URL / Route:** `install.php?type=install&page=4`
- **Reproduktionsschritte:** Code-Review `install.php:63-79` (`generate_password`). Die Schleifenbedingung `$letter = random_int(...) and $i !== 8` erzeugt genau 8 Zeichen.
- **Erwartet:** Konsistente, starke Passwortgenerierung (vgl. `admin/addmember.php` verwendet `bin2hex(random_bytes(8))` = 16 Zeichen).
- **Tatsächlich:** Nur 8 Zeichen, dazu die `base64_encode`-Kodierung (BUG-002).
- **Fehlerart:** Sicherheit
- **Schweregrad:** Mittel
- **Status:** Offen – nicht beheben

## BUG-007: Passwort im Klartext auf Installationsergebnisseite

- **Bereich:** Installer
- **URL / Route:** `install.php?type=install&page=4`
- **Reproduktionsschritte:** Code-Review `install.php:343`.
- **Erwartet:** Passwort nur per E-Mail / nicht im Browser anzeigen.
- **Tatsächlich:** `echo "… Dein Passwort ($password) wurde Dir per E-Mail zugesandt!…"` – Klartextpasswort steht im HTML, Browserverlauf, Proxy-Logs, Screenshots.
- **Fehlerart:** Sicherheit (Daten-Leak)
- **Schweregrad:** Mittel
- **Status:** Offen – nicht beheben

## BUG-008: Veraltete Copyright-Jahreszahl im Installer

- **Bereich:** Installer
- **URL / Route:** `install.php` Footer
- **Reproduktionsschritte:** `GET /install.php` → Quelltext enthält "Copyright 2001-20023".
- **Erwartet:** "2001-2025" (konsistent mit restlichem Projekt).
- **Tatsächlich:** "Copyright 2001-**20023**".
- **Fehlerart:** UI / Text
- **Schweregrad:** Niedrig
- **Status:** Offen – nicht beheben

## BUG-009: Kein CSRF-Schutz im Installer-Flow

- **Bereich:** Installer
- **URL / Route:** Alle `install.php?type=install&page=*` POSTs
- **Reproduktionsschritte:** Code-Review. Der Installer lädt `functions.inc.php` nicht und ruft weder `csrf_check()` noch `csrf_field()` auf.
- **Erwartet:** CSRF-Schutz, um einen "Drive-by-Reinstall" zu verhindern.
- **Tatsächlich:** Ein Angreifer kann einen eingeloggten Admin per automatisch abgesendetem Formular eine Neuinstallation auslösen lassen.
- **Fehlerart:** Sicherheit (CSRF)
- **Schweregrad:** Mittel
- **Status:** Offen – nicht beheben

## BUG-010: Login-Formular ohne CSRF-Token

- **Bereich:** Authentifizierung
- **URL / Route:** `POST /admin/?login=YES`
- **Reproduktionsschritte:** Code-Review `admin/header.inc.php:165-191`. Formular enthält kein `csrf_field()`. Bestätigt per Browser-JS:
  ```
  (() => ({ hasCsrf: !!document.querySelector('form[action*="login=YES"] input[name="csrf_token"]') }))()
  // → { hasCsrf: false }
  ```
- **Erwartet:** CSRF-Token auch im Login-Formular.
- **Tatsächlich:** Kein Token. Login-CSRF ermöglicht Session-Fixation-ähnliche Angriffe.
- **Fehlerart:** Sicherheit (CSRF)
- **Schweregrad:** Hoch
- **Status:** Offen – nicht beheben

## BUG-011: Cookie-Session verwendet bcrypt-Hash als Session-Token

- **Bereich:** Authentifizierung
- **URL / Route:** `/admin/*`
- **Reproduktionsschritte:**
  1. Korrekter Login.
  2. `Set-Cookie: pcadmin_password=%242y%2412%24V3kFoY5Db…` (URL-kodierter DB-Hash).
  3. Reproduktion per curl:
     ```
     HASH=$(docker exec powerclan_db mysql ... -N -e "SELECT password FROM pc_members WHERE id=1")
     curl -b "pcadmin_id=1; pcadmin_password=$HASH" http://localhost:8086/admin/index.php
     ```
     → Admin-Dashboard wird angezeigt.
  4. Code-Review `admin/functions.inc.php:44-46`: `if ($storedPassword === $password) { $loggedin = 'YES'; }`.
- **Erwartet:** Session-Token ist ein zufälliger, serverseitig verwalteter Wert, der Passwort-Hash verlässt niemals den Server.
- **Tatsächlich:** Der bcrypt-Hash dient gleichzeitig als Authentifikator. Jeder Lesezugriff auf die Datenbank liefert automatisch Gültigkeitszeichen für permanente Impersonation.
- **Fehlerart:** Sicherheit (Session-Management)
- **Schweregrad:** Hoch
- **Status:** Offen – nicht beheben

## BUG-012: Keine Rate-Limitierung / Brute-Force-Schutz am Login

- **Bereich:** Authentifizierung
- **URL / Route:** `POST /admin/?login=YES`
- **Reproduktionsschritte:** Code-Review. Weder Account-Lockout noch IP-Throttling.
- **Erwartet:** Schutz gegen Credential-Stuffing / Brute Force.
- **Tatsächlich:** Unbegrenzte Login-Versuche möglich, limitiert nur durch die bcrypt-Kosten (12).
- **Schweregrad:** Mittel
- **Status:** Offen – nicht beheben

## BUG-013: Kein Feedback bei fehlgeschlagenem Login

- **Bereich:** Authentifizierung
- **URL / Route:** `POST /admin/?login=YES`
- **Reproduktionsschritte:**
  1. POST mit falschem Passwort / unbekannter E-Mail / ungültigem E-Mail-Format.
  2. Antwort: HTTP 200 mit leerem Login-Formular (Content-Length identisch für alle Fehlerfälle: 1218 Byte).
- **Erwartet:** Generische Fehlermeldung "Login fehlgeschlagen" (ohne Account-Enumeration).
- **Tatsächlich:** Stummer Re-Render ohne jeglichen Hinweis.
- **Fehlerart:** UX / Funktional
- **Schweregrad:** Niedrig
- **Status:** Offen – nicht beheben

## BUG-014: Ungültiges E-Mail-Format am Login wird nicht validiert

- **Bereich:** Authentifizierung
- **URL / Route:** `POST /admin/?login=YES`
- **Reproduktionsschritte:**
  1. Browser-Validierung mit curl umgehen: `loginemail=NICHT-EMAIL&loginpassword=xxx`.
  2. Server antwortet HTTP 200 mit Login-Form (derselben Länge wie anderer Fehlerpfad).
- **Erwartet:** Serverseitige Formatprüfung mit `validate_email` (existiert bereits in `functions.inc.php`).
- **Tatsächlich:** `admin/header.inc.php:60` prüft nur `!empty($loginpassword) && !empty($loginemail)`.
- **Schweregrad:** Mittel
- **Status:** Offen – nicht beheben

## BUG-015: Fehlende HTTP-Security-Header

- **Bereich:** Auth / Querschnitt
- **URL / Route:** Alle Responses von `/admin/*`
- **Reproduktionsschritte:** `curl -I http://localhost:8086/admin/` → keine `X-Frame-Options`, `Content-Security-Policy`, `X-Content-Type-Options`, `Strict-Transport-Security`, `Referrer-Policy`, `Permissions-Policy`.
- **Erwartet:** Mindestens `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`.
- **Tatsächlich:** Keine.
- **Schweregrad:** Mittel
- **Status:** Offen – nicht beheben

## BUG-016: Session-Cookie ohne `Secure`-Flag

- **Bereich:** Auth
- **URL / Route:** `Set-Cookie` nach Login
- **Reproduktionsschritte:** `curl -i -X POST …/admin/?login=YES` → `Set-Cookie: pcadmin_id=1; … HttpOnly; SameSite=Lax` (kein `Secure`).
- **Erwartet:** `Secure`-Flag für Production (ist nur via Config-Option aktivierbar). Lokal tolerabel, Produktion kritisch.
- **Tatsächlich:** `Secure` fehlt im `setcookie`-Array (`admin/header.inc.php:112-118`).
- **Schweregrad:** Niedrig (lokal); Hoch (Produktion)
- **Status:** Offen – nicht beheben

## BUG-017: Kein CSRF-Schutz in `editmember.php` – Privilege-Escalation möglich

- **Bereich:** Member-Verwaltung
- **URL / Route:** `POST /admin/editmember.php?memberid=<id>&editmember=YES`
- **Reproduktionsschritte:**
  1. Als Admin eingeloggt sein.
  2. Per curl OHNE CSRF-Token:
     ```
     curl -b /tmp/admin.cookie -X POST "http://localhost:8086/admin/editmember.php?memberid=2&editmember=YES" \
       --data-urlencode "nick=TestUser" --data-urlencode "email=testuser@example.com" \
       --data-urlencode "icq=0" --data-urlencode "age=0" \
       --data-urlencode "member_add=YES" --data-urlencode "member_del=YES" \
       --data-urlencode "news_add=YES" --data-urlencode "news_del=YES" \
       --data-urlencode "wars_add=YES" --data-urlencode "wars_del=YES"
     ```
  3. Antwort: "Der Member TestUser wurde erfolgreich editiert!".
  4. DB-Check: alle Rechte auf YES gesetzt.
- **Erwartet:** `csrf_check()` und `csrf_field()` wie in `editmember2.php`, `editnews.php`, `editwar.php`, `delmember.php` usw.
- **Tatsächlich:** Keiner der CSRF-Aufrufe in `admin/editmember.php` vorhanden (`grep -n "csrf_check\|csrf_field" admin/editmember.php` liefert 0 Treffer). Jede fremde Website kann per `<img>`- oder `<form>`-CSRF eine Rechte-Eskalation auslösen.
- **Fehlerart:** Sicherheit (CSRF / Privilege Escalation)
- **Schweregrad:** **Blocker**
- **Status:** Offen – nicht beheben

## BUG-018: Fatal Error bei leerem `icq`/`age` im Profil-/Member-Update

- **Bereich:** Profile / editmember
- **URL / Route:** `POST /admin/profile.php?editprofile=YES`, `POST /admin/editmember.php?...&editmember=YES`
- **Reproduktionsschritte:**
  1. POST ohne `icq`/`age` (oder mit leerem String).
  2. Antwort: HTTP 200, Body-Länge 1432 Byte (nur Header + leerer `<center>`).
  3. `logs/php-error.log` zeigt:
     ```
     PHP Fatal error: Uncaught mysqli_sql_exception: Incorrect integer value: '' for column 'icq'
     at row 1 in /var/www/html/admin/editmember.php:154
     ```
- **Erwartet:** Leere Werte werden zu `0` gecastet (vgl. `editmember2.php:89: $icq = (int) ($_POST['icq'] ?? 0);`).
- **Tatsächlich:** `editmember.php:58: $icq = trim($_POST['icq'] ?? '');` und `bind_param('ss…')` – MySQL strict-mode lehnt leere Strings für `INT`-Spalten ab.
- **Fehlerart:** Funktional (Blocker beim Editieren)
- **Schweregrad:** Hoch
- **Status:** Offen – nicht beheben

## BUG-019: Fehler-Output landet nur im PHP-Log; Seite rendert stumm

- **Bereich:** Fehlerbehandlung / Querschnitt
- **URL / Route:** Jede `admin/*.php`, die eine nicht abgefangene `RuntimeException`/`mysqli_sql_exception` wirft.
- **Reproduktionsschritte:** Siehe BUG-018 – HTTP 200, aber keine Fehlermeldung im HTML.
- **Erwartet:** User sieht nutzbare Meldung ("Deine Änderung konnte nicht gespeichert werden").
- **Tatsächlich:** Nur `<center>` gefolgt vom Footer. Der Admin glaubt, nichts sei passiert, obwohl ein Fatal Error getrieben wurde.
- **Fehlerart:** UX / Observability
- **Schweregrad:** Hoch
- **Status:** Offen – nicht beheben

## BUG-020: Session/Cookie nicht rotiert nach Passwortwechsel

- **Bereich:** Auth (profile, editmember)
- **URL / Route:** `POST /admin/profile.php?editprofile=YES`
- **Reproduktionsschritte:**
  1. Als TestUser einloggen → Cookie `pcadmin_password=<HashA>`.
  2. POST mit neuem gültigen Passwort.
  3. GUI-Message: "Da Du Dein Passwort geändert hast musst Du Dich nun neu einloggen!".
  4. Alter Cookie jedoch weiterhin angezeigt → Server-seitig keine Invalidierung.
  5. Zusätzlich: Der neu erzeugte DB-Hash wird dem Client nicht als neuer Cookie gesetzt, d. h. erster nächster Request scheitert **nur**, weil `<HashA>` ≠ `<HashB>` (DB) – es gibt aber keinen Mechanismus, der alle weiteren Sessions ausloggt.
  6. Combined mit BUG-011: Wer den alten Hash kennt (z. B. aus Backup), bleibt mit `<HashA>` eingeloggt, solange das Passwort nicht wieder geändert wird.
- **Erwartet:** `session_regenerate_id(true)` + Neuausstellung aller Cookies nach Passwortwechsel; optional "alle anderen Sitzungen beenden".
- **Tatsächlich:** Keiner davon vorhanden.
- **Schweregrad:** Mittel
- **Status:** Offen – nicht beheben

## BUG-021: choose*-Seiten zeigen Aktionslinks ohne Rechte-Check

- **Bereich:** choosenews / choosewar / choosemember
- **URL / Route:** `GET /admin/choosenews.php`, `choosewar.php`, `choosemember.php`
- **Reproduktionsschritte:**
  1. TestUser, der **keinerlei** Rechte hat (`news_edit=NO`, `news_del=NO`, …).
  2. Aufruf `choosenews.php` → Links "editieren" / "löschen" werden für alle News angezeigt.
  3. Klick führt zu "Du hast keine Zugang zu dieser Funktion!" – d. h. serverseitig korrekt geblockt, clientseitig aber verwirrend.
- **Erwartet:** Aktionslinks nur rendern, wenn der eingeloggte Nutzer die Rechte besitzt.
- **Tatsächlich:** Code rendert sie pauschal (`admin/choosenews.php:51-67`).
- **Schweregrad:** Mittel
- **Status:** Offen – nicht beheben

## ~~BUG-022: Selbstlöschung des eingeloggten Superadmins möglich~~

> **Fehldiagnose – kein Bug.** Bei der Nachverifikation am 2026-04-23 wurde festgestellt, dass `admin/delmember.php:51-55` bereits einen Self-Delete-Schutz enthält (`if ((int) $row['id'] === (int) ($pcadmin['id'] ?? 0))`) und `admin/delmember.php:57-61` zusätzlich jede Löschung eines Superadmins blockiert. Der ursprüngliche Befund entstand, weil der CSRF-Token-Test (BUG-017) die Ausführung des POST abbrach, bevor der Self-Delete-Check erreicht wurde. Ursprünglicher Eintrag:
>
> - **Bereich:** delmember
> - **URL / Route:** `POST /admin/delmember.php?memberid=<own-id>` (mit gültigem CSRF)
> - **Reproduktionsschritte:** Code-Review `admin/delmember.php` – es gibt keinen Check `$rowId !== $pcadmin['id']` und keinen "letzter-Superadmin"-Schutz.
> - **Erwartet:** Admin darf sich nicht selbst löschen; letzter Superadmin darf nicht gelöscht werden.
> - **Tatsächlich:** Mit korrektem CSRF-Token würde der Admin sich entfernen – System wäre danach nicht mehr administrierbar.
> - **Schweregrad:** Mittel
>
> **Status: FEHLDIAGNOSE / nicht-Bug – keine Code-Änderung notwendig.**

## BUG-023: Erfolgsmeldung "per E-Mail benachrichtigt" ohne Mailserver

- **Bereich:** addmember / editmember / install
- **URL / Route:** `POST /admin/addmember.php?addmember=YES`, `POST /admin/editmember.php?...&editmember=YES` (mit neuem Passwort), `install.php?type=install&page=4`
- **Reproduktionsschritte:**
  1. `docker-compose.yml` enthält keinen SMTP/Mailpit-Service.
  2. `addmember.php` ruft `@mail(...)` auf und zeigt "Der Member wurde erfolgreich hinzugefügt und per E-Mail benachrichtigt!".
  3. Mail wird in Wirklichkeit nicht zugestellt (kein MTA); `@` unterdrückt Warnung.
- **Erwartet:** Meldung erst nach erfolgreichem `mail()`-Rückgabewert; oder SMTP/Mailpit-Setup in `.docker/docker-compose.yml`.
- **Tatsächlich:** Admin vertraut auf erfolgreiche Zustellung, der neue User erhält aber weder Passwort noch Notification.
- **Schweregrad:** Mittel
- **Status:** **BEHOBEN am 2026-04-24** – Mailpit-Container `powerclan_mailpit` (SMTP 1034, UI 8034) in `.docker/docker-compose.yml` ergänzt; `msmtp` im Web-Container installiert mit Route auf `mailpit:1025`; `sendmail_path = "/usr/bin/msmtp -t"` in `.docker/php.ini`; Rückgabewert von `@mail()` wird in `admin/addmember.php` und `admin/editmember.php` ausgewertet und bei Fehler als sichtbarer Warnhinweis angezeigt.

## BUG-024: Keine Datums-/Zeit-Validierung in `addwar.php`

- **Bereich:** War-Verwaltung
- **URL / Route:** `POST /admin/addwar.php?addwar=YES`
- **Reproduktionsschritte:**
  1. `time_day=31 time_month=2 time_year=2026 time_hour=25 time_minute=99`
  2. `mktime(25,99,0,2,31,2026)` → Zeitstempel für `2026-03-04 01:39:00`.
  3. War wird gespeichert ohne Hinweis auf Normalisierung.
- **Erwartet:** `checkdate()`-Prüfung + Validierung `0 ≤ hour ≤ 23`, `0 ≤ minute ≤ 59`, Warnung bei ungültigen Werten.
- **Tatsächlich:** Stillschweigende Normalisierung durch `mktime`.
- **Schweregrad:** Hoch (Datenqualität)
- **Status:** Offen – nicht beheben

## BUG-025: `strip_tags` im Titel von News verschluckt legitime Zeichen

- **Bereich:** addnews / editnews
- **URL / Route:** `POST /admin/addnews.php?addnews=YES`
- **Reproduktionsschritte:**
  1. Title = `<script>alert('xss')</script>Title2`.
  2. Nach Speichern steht in DB `alert('xss')Title2`.
- **Erwartet:** XSS durch Output-Escaping (`e()`) verhindern, statt durch `strip_tags` zu entstellen. Oder mindestens Fehler melden, wenn Content entfernt wird.
- **Tatsächlich:** Titel wird entstellt ohne Rückmeldung; zweite Verteidigungslinie (Output-`e()`) ist aber aktiv, daher kein echtes XSS.
- **Schweregrad:** Mittel (UX/Datenintegrität)
- **Status:** Offen – nicht beheben

## BUG-026: Passwortfelder auf 25 Zeichen begrenzt

- **Bereich:** profile / editmember
- **URL / Route:** `GET /admin/profile.php`
- **Reproduktionsschritte:** `<input name="password1" maxlength="25">` im HTML.
- **Erwartet:** Mindestens 72 Zeichen (bcrypt-Limit) zulassen, besser 128+.
- **Tatsächlich:** Passphrasen >25 Zeichen werden client-seitig abgeschnitten; kein Server-side-Limit.
- **Schweregrad:** Niedrig
- **Status:** Offen – nicht beheben

## BUG-027: `$i++` fehlt nach `news_del=YES` in admin/index.php

- **Bereich:** Dashboard
- **URL / Route:** `/admin/index.php`
- **Reproduktionsschritte:** Code-Review `admin/index.php:55-58`. Nach `echo "<li>News löschen</li>\n";` wird `$i++` nicht aufgerufen, anders als bei den anderen Rechten.
- **Erwartet:** Konsistentes `$i++` in jedem Zweig; Zähler entscheidet über "Du hast keine Adminrechte"-Fallback.
- **Tatsächlich:** Wenn ein Nutzer nur `news_del=YES` hat und sonst kein Recht, wird trotzdem "keine Adminrechte" angezeigt.
- **Schweregrad:** Niedrig
- **Status:** Offen – nicht beheben

## BUG-028: editmember2.php ist Dead-Code, aber lädt Admin-Umgebung

- **Bereich:** Admin-Interna
- **URL / Route:** `/admin/editmember2.php`
- **Reproduktionsschritte:**
  1. `grep -rn "editmember2"` → kein Verlinker.
  2. Datei führt `include header.inc.php`, Session-Start, DB-Abfragen aus – erreichbar direkt per URL.
- **Erwartet:** Entfernt oder klar als offizieller Flow verwendet. Derzeit existieren zwei Edit-Flows (`editmember.php` vs. `editmember2.php`), von denen nur der fehlerhafte verlinkt ist.
- **Tatsächlich:** Angriffsfläche + Verwirrung.
- **Schweregrad:** Niedrig
- **Status:** Offen – nicht beheben

## BUG-029: CSRF-Token wird nie rotiert

- **Bereich:** CSRF-Mechanik
- **URL / Route:** Alle POSTs innerhalb `/admin/*`
- **Reproduktionsschritte:**
  1. CSRF-Token einmalig aus Formular auslesen.
  2. Gleichen Token für beliebig viele Submits verwenden – funktioniert über gesamte Session.
  3. Code-Review: `csrf_regenerate()` existiert in `functions.inc.php:302-309`, wird aber nirgends aufgerufen.
- **Erwartet:** Rotation nach jedem erfolgreichen Submit ODER Zeit-Basiert.
- **Tatsächlich:** Token = lebenslang in Session.
- **Schweregrad:** Mittel
- **Status:** Offen – nicht beheben

## BUG-030: CSRF-Fehler liefert HTTP 200 statt 403

- **Bereich:** CSRF
- **URL / Route:** Alle POSTs mit ungültigem Token
- **Reproduktionsschritte:**
  1. POST ohne Token auf `profile.php`, `delmember.php`, etc.
  2. Antwort enthält "Sicherheitsfehler: Ungültiges CSRF-Token." – Status jedoch HTTP **200**.
  3. Grund: `header.inc.php` hat bereits Output geschrieben, bevor `csrf_check()` `http_response_code(403)` aufruft.
- **Erwartet:** HTTP 403 für konsistentes API-/Monitoring-Verhalten.
- **Tatsächlich:** 200 + Body-Meldung.
- **Schweregrad:** Niedrig
- **Status:** Offen – nicht beheben

## BUG-031: Grammatik "keine Zugang" statt "keinen Zugang"

- **Bereich:** addmember, addnews, addwar, editconfig, delnews, (auch weitere)
- **URL / Route:** z. B. `/admin/addmember.php` als User ohne Berechtigung
- **Reproduktionsschritte:** `curl -b /tmp/nouser.cookie /admin/addmember.php` → "Du hast keine Zugang zu dieser Funktion!"
- **Erwartet:** "Du hast **keinen** Zugang zu dieser Funktion!"
- **Tatsächlich:** Formulierung in mindestens 4 Dateien identisch falsch.
- **Schweregrad:** Niedrig
- **Status:** Offen – nicht beheben

## BUG-032: Rechtschreibfehler "Daten zurücksetzten"

- **Bereich:** profile / editmember
- **URL / Route:** `/admin/profile.php`
- **Reproduktionsschritte:** Reset-Button beschriftet mit "Daten zur**ü**cksetzten".
- **Erwartet:** "Daten zurücksetzen".
- **Tatsächlich:** Tippfehler.
- **Schweregrad:** Niedrig
- **Status:** Offen – nicht beheben

## BUG-033: Öffentliche Navigation enthält "Admin"-Link

- **Bereich:** Header (Frontend)
- **URL / Route:** `header.pc` wird via `getsettings()` geladen.
- **Reproduktionsschritte:** Öffentliche Startseite aufrufen → Navigations-Zeile enthält `Home | Members | Wars | Admin`.
- **Erwartet:** "Admin"-Link nur für eingeloggte Members sichtbar bzw. sauberer Hinweistext (z. B. "Mitglieder-Login").
- **Tatsächlich:** Öffentlicher Hinweis auf Admin-Backend. Erhöht Aufmerksamkeit für Angreifer und verwirrt nicht-angemeldete Besucher.
- **Schweregrad:** Mittel
- **Status:** Offen – nicht beheben

---

<!-- Neue Bugs ab hier einfügen -->
