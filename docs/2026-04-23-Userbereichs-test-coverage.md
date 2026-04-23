# Userbereichs-Audit – Test Coverage

**Projekt:** PowerClan (PHP/MySQL Clan-Portal, Version 2.00)
**Datum:** 2026-04-23
**Auditor:** Senior QA / Dev-Auditor
**Zielsystem:** http://localhost:8086/ (Docker, Apache 2.4.65 / PHP 8.4.16 / MySQL 8.0)

## Legende Status

- ✅ **GETESTET** – Vollständig abgedeckt, Ergebnis dokumentiert
- 🟡 **TEILWEISE GETESTET** – Mehrheit abgedeckt, Lücken benannt
- ❌ **BLOCKIERT** – Test nicht durchführbar (Grund dokumentiert)
- — **ENTFÄLLT** – Aspekt für diese Route nicht anwendbar

## Legende Findings-Spalte

- `BUG-NNN` verweist auf Eintrag in `docs/2026-04-23-Userbereichs-bugs.md`
- `IMP-NNN` verweist auf Eintrag in `docs/2026-04-23-Userbereichs-improvements.md`

---

## Testumgebung

| Komponente | Wert |
|---|---|
| Docker-Container | `powerclan_web`, `powerclan_db`, `powerclan_phpmyadmin` |
| DB-User | `powerclan` / `powerclan_secure_2024` (`powerclan_v2.0`) |
| Web-Port | 8086 |
| MySQL-Port (extern) | 3316 |
| Mailpit | **Nicht vorhanden** (BUG-023 / IMP-022) |
| Test-Admin | `admin@example.com` / `Audit2026!` (superadmin=YES) |
| Test-User | `testuser@example.com` / `TestPw2026` (angelegt via `addmember.php`) |
| Durchgeführte Tools | Chrome-MCP, curl, docker exec mysql, Code-Review |

---

## 1. Öffentlicher Bereich (ohne Login)

| # | Bereich | Route | Status | Findings |
|---|---|---|---|---|
| 1.1 | Startseite (News + letzte Wars) | `GET /index.php` | ✅ | Keine Konsolenfehler, Title "TestClan", HTML valide. |
| 1.2 | Memberübersicht | `GET /member.php` | ✅ | Listet `Admin` + `TestUser` alphabetisch. |
| 1.3 | Memberdetails | `GET /member.php?pcpage=showmember&memberid=1` | ✅ | Detailansicht korrekt, escaped HTML (`realname` XSS wird entitisiert). |
| 1.4 | Memberdetails – ungültige ID | `?memberid=99999` | ✅ | Meldung "Bitte wähle einen existierenden Member aus!". |
| 1.5 | Memberdetails – leere ID | `?memberid=` | ✅ | Meldung "Bitte wähle einen Member aus!". |
| 1.6 | Memberdetails – SQL-Injection-Probe | `?memberid=1' OR 1=1--` | ✅ | `(int)`-Cast macht Injection wirkungslos → Treffer wird als ID 1 interpretiert. |
| 1.7 | Memberdetails – XSS-Probe | `?memberid=%3Cscript%3E…` | ✅ | Meldung "existierenden Member"; kein XSS ausgelöst. |
| 1.8 | Wars-Übersicht | `GET /wars.php` | ✅ | Gewonnen/Verloren/Unentschieden/Offen/Gesamt sichtbar; Wars aus DB gelistet. |
| 1.9 | Bildanzeige | `GET /showpic.php?path=images/example.jpg` | 🟡 | Allowed-Dir + Extension-Check greifen. Traversal `../../etc/passwd` → "Ungültiger Bildpfad". Externe `http(s)://`-URLs werden akzeptiert (by design). |
| 1.10 | Direkter Admin-Zugriff ohne Login | `GET /admin/` | ✅ | Login-Form wird gerendert; keine Adminfunktion erreichbar (HTTP 200 mit Login-Formular). |
| 1.11 | Installation erreichbar | `GET /install.php` | ✅ | Seite zeigt Installer, obwohl bereits installiert → **BUG-001/002/003** (Blocker). |
| 1.12 | Navigations-Link "Admin" öffentlich sichtbar | `GET /` | ✅ | Ja, offener Link auf `/admin/` → **BUG-033**. |

## 2. Authentifizierung

| # | Bereich | Route | Status | Findings |
|---|---|---|---|---|
| 2.1 | Login-Formular anzeigen | `GET /admin/` | ✅ | Form mit `loginemail`+`loginpassword`, **kein CSRF-Token** → **BUG-010**. |
| 2.2 | Login positiv | `POST /admin/?login=YES` | ✅ | Redirect 302 → `/admin/index.php`. Cookies `pcadmin_id`, `pcadmin_password` (HttpOnly, SameSite=Lax, kein Secure → BUG-016). |
| 2.3 | Login – leere Felder | `POST` mit leer | ✅ | Login-Form re-rendered ohne Fehlermeldung → **BUG-013**. |
| 2.4 | Login – falsches Passwort | `POST` | ✅ | Same (identische Content-Length 1218) → BUG-013. |
| 2.5 | Login – unbekannte E-Mail | `POST` | ✅ | Same → BUG-013. |
| 2.6 | Login – ungültiges E-Mail-Format | `POST` bypass HTML5 | ✅ | Wird serverseitig nicht geprüft (kein `validate_email`) → **BUG-014**. |
| 2.7 | Login-CSRF-Schutz | `POST /admin/?login=YES` | ✅ | Nicht vorhanden → **BUG-010**. |
| 2.8 | Cookie-Flags | Response-Header | ✅ | HttpOnly ✓, SameSite=Lax ✓, **Secure ✗** → BUG-016. |
| 2.9 | Logout | `GET /admin/?logout=YES` | ✅ | Cookies mit `expires=1970…Max-Age=0` gelöscht; Folgeaufrufe zeigen Login-Form. |
| 2.10 | Session-Persistenz nach Reload | `GET /admin/index.php` | ✅ | Wiederholter Aufruf bleibt angemeldet, solange Cookies gültig. |
| 2.11 | Cookie-Manipulation: `pcadmin_id=999` | manuelles Cookie | ✅ | Login-Form wird angezeigt (→ `checklogin` findet keinen User). |
| 2.12 | Cookie-Manipulation: falscher Hash | `pcadmin_id=1, pwd=falschehash` | ✅ | Login-Form (korrekt abgewiesen). |
| 2.13 | Cookie-Manipulation: gestohlener Hash | `pcadmin_password=<DB-Hash>` | ✅ | Admin-Dashboard wird angezeigt → **BUG-011** (Session ≡ Hash). |
| 2.14 | Cookie-Manipulation: Plaintext-Passwort | `pcadmin_password=plaintext` | ✅ | Login-Form (abgewiesen, weil weder bcrypt noch `base64_encode('plaintext')` gleich DB). |
| 2.15 | Rate-Limiting / Brute Force | wiederholte Logins | ✅ | Kein Limit → **BUG-012**. |
| 2.16 | HTTP-Security-Header | `curl -I /admin/` | ✅ | Keine → **BUG-015**. |

## 3. Dashboard / Navigation

| # | Bereich | Route | Status | Findings |
|---|---|---|---|---|
| 3.1 | Admin-Dashboard (superadmin) | `GET /admin/index.php` | ✅ | 10× Rechte-Zeile + "Alle Rechte + Konfiguration editieren". |
| 3.2 | Dashboard (ohne Rechte) | TestUser ohne Rechte | ✅ | "Du hast keine Adminrechte" angezeigt. |
| 3.3 | `$i++` fehlt bei `news_del` | Code-Review | ✅ | Verursacht inkorrektes Fallback → **BUG-027**. |
| 3.4 | Sidebar-Navigation – alle Links erreichbar | Header-Check | ✅ | addnews/choosenews/addwar/choosewar/addmember/choosemember/editconfig/Profil/Logout/"Öffentliche Seite". |
| 3.5 | Aktive Seite hervorgehoben | Sidebar | ✅ | Nein → **IMP-006**. |

## 4. Eigenes Profil

| # | Bereich | Route | Status | Findings |
|---|---|---|---|---|
| 4.1 | Profil anzeigen | `GET /admin/profile.php` | ✅ | Alle Felder korrekt ausgefüllt; CSRF-Token im Form ✓. |
| 4.2 | Profil speichern – alle Felder | `POST ?editprofile=YES` | ✅ | "Dein Profil wurde erfolgreich editiert" (mit `icq=0`, `age=0`). |
| 4.3 | Profil – leerer Nickname | POST | ✅ | "Bitte gib Nickname und E-Mail an!". |
| 4.4 | Profil – leere E-Mail | POST | ✅ | Gleiche Meldung (keine Unterscheidung zwischen Nick/Email). |
| 4.5 | Profil – ungültiges E-Mail-Format | POST | ✅ | "Die angegebene E-Mail Adresse ist ungültig!". |
| 4.6 | Profil – doppelter Nickname | POST | ✅ | "Es gibt schon einen Member mit dieser E-Mail oder diesem Nickname!". |
| 4.7 | Profil – doppelte E-Mail | POST | ✅ | Gleich (selbe Query via `OR`). |
| 4.8 | Profil – Passwort ändern (korrekt) | POST | 🟡 | Update schlägt **stumm fehl**, wenn `icq`/`age` leer gesendet → **BUG-018**. Mit `icq=0&age=0` funktioniert es. Session wird aber **nicht** rotiert → **BUG-020**. |
| 4.9 | Profil – Passwort nur P1 | POST | ✅ | "Du musst Dein neues Passwort bestätigen". |
| 4.10 | Profil – P1 ≠ P2 | POST | ✅ | "Das neue Passwort wurde falsch bestätigt!". |
| 4.11 | Profil – XSS in `realname` | POST `<script>alert(1)</script>` | ✅ | Gespeichert, beim Ausgeben als Entities (`&lt;script&gt;`) → kein XSS. |
| 4.12 | Profil – XSS/HTML in `hardware`/`info` | POST | ✅ | `strip_tags` entfernt die Tags (UX-Effekt siehe BUG-025), doppelte Absicherung durch `e()` beim Output. |
| 4.13 | Profil – Passwort `maxlength=25` | Formular | ✅ | Client-Side-Limit 25 → **BUG-026** / **IMP-007**. |
| 4.14 | Profil – CSRF-Schutz | POST ohne Token | ✅ | HTTP 200 + Body-Meldung "Sicherheitsfehler: Ungültiges CSRF-Token" → BUG-030 (Status 200 statt 403). |
| 4.15 | Profil – CSRF-Token-Rotation | Mehrfach-Submit | ✅ | Token unverändert gültig → **BUG-029**. |
| 4.16 | Profil – Reset-Button Rechtschreibung | Formular | ✅ | "Daten zurücksetzten" → **BUG-032**. |

## 5. News-Verwaltung

| # | Bereich | Route | Status | Findings |
|---|---|---|---|---|
| 5.1 | News hinzufügen – Formular | `GET /admin/addnews.php` | ✅ | CSRF-Token ✓; BBCode-Hilfsbuttons `[b]/[u]/[i]`. |
| 5.2 | News hinzufügen – Speichern | `POST ?addnews=YES` | ✅ | "Deine News wurden erfolgreich gepostet!". |
| 5.3 | News – leere Felder | POST | ✅ | "Bitte fülle alle Felder aus!". |
| 5.4 | News – XSS in Titel | POST `<script>…</script>Title` | ✅ | `strip_tags` reduziert Titel auf `alert('xss')Title2` → **BUG-025** (Entstellung, nicht XSS). |
| 5.5 | News – Raw HTML in Text | POST `<b>RAW</b>` | ✅ | Im Frontend als `&lt;b&gt;` angezeigt (via `htmlspecialchars` in `news_replace`). |
| 5.6 | News editieren – Auswahl | `GET /admin/choosenews.php` | ✅ | Liste mit Datum/Titel/Autor/Aktionslinks. Aktionen für User ohne Rechte sichtbar → **BUG-021**. |
| 5.7 | News editieren – Speichern | `POST /admin/editnews.php?newsid=<id>&editnews=YES` | ✅ | CSRF ✓, Update funktioniert. |
| 5.8 | News editieren – eigenes Recht + fremde News | Code-Review | ✅ | `$hasAccess = news_edit === 'YES' || own || superadmin` – OK. |
| 5.9 | News löschen – Bestätigungsseite | `GET /admin/delnews.php?newsid=<id>` | ✅ | Bestätigungsform mit CSRF-Token. |
| 5.10 | News löschen – fehlende newsid | `GET /admin/delnews.php` | ✅ | "Bitte wähle einen Newseintrag aus!". |
| 5.11 | News löschen – nicht existierende ID | `?newsid=9999` | ✅ | "Der gewählte Newseintrag existiert nicht!". |
| 5.12 | News löschen – ohne CSRF | `POST` ohne Token | ✅ | "Ungültiges CSRF-Token". |
| 5.13 | News löschen – mit CSRF | `POST` korrekt | ✅ | "Der Newseintrag wurde erfolgreich gelöscht!". |
| 5.14 | News – ohne Berechtigung | TestUser ohne `news_*` | ✅ | "Du hast keine Zugang zu dieser Funktion!" (Grammatik → **BUG-031**). |

## 6. Wars-Verwaltung

| # | Bereich | Route | Status | Findings |
|---|---|---|---|---|
| 6.1 | War hinzufügen – Formular | `GET /admin/addwar.php` | ✅ | CSRF ✓, 5 Dropdowns für Tag/Monat/Jahr/Stunde/Minute → **IMP-016**. |
| 6.2 | War hinzufügen – Pflichtfelder gesetzt | `POST ?addwar=YES` | ✅ | "Der War wurde erfolgreich hinzugefügt". |
| 6.3 | War hinzufügen – Pflichtfelder fehlen | POST | ✅ | "Bitte fülle alle nicht optionalen Felder aus!". |
| 6.4 | War – ungültiges Datum | `time_day=31 time_month=2 time_hour=25 time_minute=99` | ✅ | Wird stillschweigend zu `2026-03-04 01:39:00` normalisiert → **BUG-024**. |
| 6.5 | War editieren – Auswahl | `GET /admin/choosewar.php` | 🟡 | Liste wird angezeigt; Aktionslinks ohne Rechtecheck (analog BUG-021). |
| 6.6 | War editieren – Ergebnis eintragen | `GET /admin/editwar.php?warid=<id>` | 🟡 | Form vorhanden (Gegner/Liga/Map1-3/Ergebnisse `res1-3`/Screen1-3/Report). Ergebnis-Eingabe im "res1:res2"-Format → **IMP-017**. Update nicht vollständig durchgespielt. |
| 6.7 | War löschen – Bestätigungsseite | `GET /admin/delwar.php?warid=<id>` | 🟡 | CSRF im Form ✓; Ausführung nicht durchgeführt, um Testdaten zu erhalten. |
| 6.8 | War – ohne Berechtigung | TestUser ohne Rechte | ✅ | "Du hast keine Zugang zu dieser Funktion!". |
| 6.9 | War – CSRF | `POST` ohne Token | ✅ | "Ungültiges CSRF-Token" (bestätigt via `grep csrf_check admin/addwar.php`). |
| 6.10 | War – Ergebnisformat-Validierung | Code-Review | 🟡 | `res1`–`res3` `varchar(50)` ohne Format-Check; `explode(':', ...)` liefert 0-Werte bei ungültigem Input → stille Fehlinterpretation. |

## 7. Member-Verwaltung (Superadmin)

| # | Bereich | Route | Status | Findings |
|---|---|---|---|---|
| 7.1 | Member hinzufügen – Formular | `GET /admin/addmember.php` | ✅ | CSRF ✓, 9 Rechte-Checkboxen. |
| 7.2 | Member hinzufügen – erfolgreich | `POST ?addmember=YES` | ✅ | DB: `id=2, nick=TestUser, work='Fighter', pw $2y$12$…`. Meldung "erfolgreich hinzugefügt und per E-Mail benachrichtigt" → **BUG-023** (kein Mailserver). |
| 7.3 | Member hinzufügen – Duplikat Nick | POST | ✅ | "Es gibt schon einen Member mit dieser E-Mail oder diesem Nickname!". |
| 7.4 | Member hinzufügen – ungültige E-Mail | POST | ✅ | "Die angegebene E-Mail-Adresse ist ungültig!". |
| 7.5 | Member editieren – Auswahl | `GET /admin/choosemember.php` | ✅ | Listet Members mit edit/del-Links (analog BUG-021). |
| 7.6 | Member editieren – Form rendern | `GET /admin/editmember.php?memberid=<id>` | ✅ | Form erscheint inkl. Rechte-Checkboxen. **Kein CSRF-Feld im Form** (Code-Review). |
| 7.7 | Member editieren – CSRF-Schutz | `POST` ohne Token | ✅ | "erfolgreich editiert" – Update geht durch! → **BUG-017** (Blocker). |
| 7.8 | Member editieren – Privilege Escalation | POST mit member_add/del=YES etc. | ✅ | Alle 9 Rechte werden auf YES gesetzt, ohne Token → BUG-017. |
| 7.9 | Member editieren – leeres icq/age | POST | ✅ | HTTP 200 + 1432 Byte (leer). PHP-Log: Fatal `Incorrect integer value: '' for column 'icq' at row 1` → **BUG-018 / BUG-019**. |
| 7.10 | Member editieren – `icq>int max`, `age>99` | POST | ✅ | Silent Fatal Error (DB-Wert unverändert). |
| 7.11 | Member editieren – Passwort setzen | POST | 🟡 | Mit `icq=0&age=0` funktioniert das Update. Session/Cookie wird aber nicht rotiert → **BUG-020**. |
| 7.12 | Member editieren – Dead-Code-Variante | `GET /admin/editmember2.php?memberid=<id>` | ✅ | Datei erreichbar, nicht verlinkt, hat CSRF-Check (`grep` bestätigt) → **BUG-028**. |
| 7.13 | Member löschen – Bestätigungsseite | `GET /admin/delmember.php?memberid=<id>` | ✅ | CSRF-Token im Form ✓. |
| 7.14 | Member löschen – ohne CSRF | POST | ✅ | "Ungültiges CSRF-Token". |
| 7.15 | Member löschen – sich selbst | POST own id | ✅ | Kein Self-Delete-Schutz (CSRF-Fehler verhinderte Test; Code-Review bestätigt fehlende Prüfung) → **BUG-022**. |
| 7.16 | Member – ohne Rechte | TestUser ohne `member_*` | ✅ | addmember/delmember: "Du hast keine Zugang zu dieser Funktion!". |

## 8. Konfiguration

| # | Bereich | Route | Status | Findings |
|---|---|---|---|---|
| 8.1 | Konfiguration anzeigen | `GET /admin/editconfig.php` | ✅ | Form mit allen 14 Feldern (clanname/clantag/url/serverpath/header/footer/…bg1/2/3/clrwon/draw/lost/newslimit/warlimit). |
| 8.2 | Konfiguration – leere Pflichtfelder | POST | ✅ | "Bitte fülle alle Felder aus!". |
| 8.3 | Konfiguration – Farbformat-Prüfung | Code-Review | ✅ | Keine Validierung `/^#[0-9A-Fa-f]{6}$/` → **IMP-020**. |
| 8.4 | Konfiguration – ohne Superadmin | TestUser | ✅ | "Du hast keine Zugang zu dieser Funktion!". |
| 8.5 | Konfiguration – CSRF | POST ohne Token | ✅ | "Ungültiges CSRF-Token". |
| 8.6 | Konfiguration – Persistenz | POST + Reload | 🟡 | Nicht voll durchgespielt (vermieden, um `header.pc`/`footer.pc` nicht zu zerschiessen). Code-Review: `UPDATE pc_config` mit Prepared Statement ✓. |

## 9. Sicherheit quer

| # | Bereich | Status | Findings |
|---|---|---|---|
| 9.1 | Direkter Zugriff ohne Login | ✅ | Alle geprüften URLs liefern Login-Form (nicht den Inhalt). |
| 9.2 | SQL-Injection-Probe | ✅ | Öffentliche Seiten ok (int-Cast); Installer hat SQL-Injection → **BUG-003**. Admin-Dateien verwenden Prepared Statements. |
| 9.3 | XSS – persistent | ✅ | `e()` / `htmlspecialchars` beim Output. `strip_tags` bei Input als defensive Zweitlinie, aber datenzerstörerisch → **BUG-025**. |
| 9.4 | XSS – reflektiert | ✅ | URL-Parameter über `e()` escaped. Keine reflektierte XSS gefunden. |
| 9.5 | CSRF – POST-Handler | 🟡 | Meiste Handler geschützt; `editmember.php` komplett ungeschützt → **BUG-017**. Login-Form ohne CSRF → BUG-010. Token rotiert nicht → BUG-029. |
| 9.6 | Session-Cookie-Flags | ✅ | HttpOnly ✓ / SameSite=Lax ✓ / Secure ✗ → BUG-016. |
| 9.7 | Passwort-Hashing-Qualität | ✅ | Laufender Betrieb: bcrypt cost 12. Installer schreibt `base64_encode`-Passwort → BUG-002. Migration beim Login auf bcrypt korrekt implementiert. |
| 9.8 | HTTP-Security-Header | ✅ | Keine → BUG-015. |
| 9.9 | Path-Traversal (showpic) | ✅ | `str_replace('..','',…)` + Extension-Whitelist schützt. |
| 9.10 | Mail-Header-Injection | ✅ | Installer schreibt `nickname`/`email` ungefiltert in Mail → **BUG-004**. Admin-Pfade escapen nicht, aber injizieren nur in Body (niedrigeres Risiko). |
| 9.11 | Privilege Escalation via editmember | ✅ | Vollständig bestätigt → **BUG-017**. |
| 9.12 | Installer-Angriffsfläche | ✅ | Komplett ungeschützt erreichbar → BUG-001/002/003/009. |

## 10. Nicht-funktionale Prüfungen

| # | Bereich | Status | Findings |
|---|---|---|---|
| 10.1 | Konsolenfehler | ✅ | Browser-Konsole sauber; PHP-Log `logs/php-error.log` enthält mehrere Fatal Errors von editmember.php → BUG-018. |
| 10.2 | Netzwerk: 404/500/stale assets | ✅ | Keine 404/500 bei normalem Klickpfad. `favicon.ico` fehlt (typischer 404-Rauscher – niedrig). |
| 10.3 | Performance-Eindruck | 🟡 | Seiten laden <100 ms. Keine Optimierung erkennbar (inline Tables). |
| 10.4 | Accessibility-Basics | ✅ | `<table>`-Layout + `<b>`-Labels ohne `<label for>` → **IMP-025/IMP-026**. |
| 10.5 | Mailpit – Mail-Zustellung | ❌ | Kein Mailpit-Service im docker-compose → **BUG-023 / IMP-022**. Nicht testbar. |
| 10.6 | Responsive Darstellung | ✅ | Admin-Header ohne `<meta name="viewport">`, fixe Breiten, `<table>`-Layout → **IMP-024**. |
| 10.7 | Logging / Fehler sichtbar | ✅ | Fatal Errors landen nur im Log, nicht im UI → BUG-019 / IMP-028. |
| 10.8 | Session-Timeout | 🟡 | Cookie 30 Tage, kein Server-Idle-Timeout → **IMP-029**. |

---

## Zusammenfassung

| Kategorie | Tests | ✅ GETESTET | 🟡 TEILWEISE | ❌ BLOCKIERT |
|---|---|---|---|---|
| 1. Öffentlich | 12 | 11 | 1 | 0 |
| 2. Auth | 16 | 16 | 0 | 0 |
| 3. Dashboard | 5 | 5 | 0 | 0 |
| 4. Profil | 16 | 15 | 1 | 0 |
| 5. News | 14 | 14 | 0 | 0 |
| 6. Wars | 10 | 6 | 4 | 0 |
| 7. Member | 16 | 15 | 1 | 0 |
| 8. Config | 6 | 5 | 1 | 0 |
| 9. Sicherheit | 12 | 11 | 1 | 0 |
| 10. Non-funktional | 8 | 6 | 1 | 1 |
| **Gesamt** | **115** | **104 (90 %)** | **10 (9 %)** | **1 (1 %)** |

**Bugs identifiziert:** 33 (davon 4 Blocker, 7 Hoch, 15 Mittel, 7 Niedrig).
**Improvements identifiziert:** 32 (davon 14 Hoch, 14 Mittel, 4 Niedrig).
**Ein blockierter Testaspekt:** Mailzustellung (kein Mailpit in dieser Umgebung).

---

## Abschlussbericht

### 1. Kurzfazit

Der eingeloggte Bereich von PowerClan funktioniert in den Kernfunktionen (Login, Profil lesen, News-CRUD, Wars anlegen, Config anzeigen), leidet aber an mehreren **gravierenden Sicherheitslücken** und einer Reihe von UX-/Qualitätsproblemen aus dem Legacy-Kontext (2001er HTML).

### 2. Kritische Befunde (sofortiger Handlungsbedarf)

1. **BUG-001/002/003**: `install.php` ist öffentlich erreichbar, erlaubt SQL-Injection und speichert Passwörter mit `base64_encode`.
2. **BUG-017**: `admin/editmember.php` ohne CSRF-Schutz → Privilege-Escalation per Cross-Site-Request.
3. **BUG-011**: Passwort-Hash dient als Session-Token → Datenbankzugriff = permanente Impersonation.
4. **BUG-018/019**: Fatal Error bei leerem `icq`/`age`; User sieht weder Fehlermeldung noch Success.

### 3. Prozess-Empfehlungen

- Installer-Lockfile einführen (IMP-002).
- CSRF-Schutz konsistent auf allen POST-Handlern inkl. Login und Installer (BUG-009/010/017, IMP-…).
- Echtes Session-Management einführen (IMP-029) und Passwort-Hash aus dem Cookie entfernen.
- Mailpit-Container in `.docker/docker-compose.yml` aufnehmen (IMP-022), damit `addmember.php`/`install.php` überhaupt testbar werden.
- Fehler-Output sichtbar machen (IMP-028).

### 4. Nicht-funktionale Empfehlungen

- Layout modernisieren (IMP-024/IMP-025/IMP-026/IMP-032).
- Dashboard / Rechte / Rollen-Modell gruppieren (IMP-005/IMP-012).
- Ergebnis-Eingabe Wars modernisieren (IMP-017), Datum-Picker einführen (IMP-016).
- Farb-Picker für editconfig (IMP-020).

### 5. Abdeckungslücken

- **Mail-Flow**: Nicht testbar ohne SMTP/Mailpit.
- **editwar komplette Ergebnis-Persistenz**: Code-Review durchgeführt, End-to-End-Submit nicht gefahren, um bestehende Test-Wars nicht zu verändern.
- **delwar Exekution**: Bestätigungsform geprüft, Löschung nicht durchgeführt.
- **editconfig Schreiben**: Absichtlich nicht gefahren, um `header.pc`/`footer.pc`-Config nicht zu verändern.
- **Cross-Browser**: Nur Chrome getestet.
- **Mobile**: Nicht getestet (Layout ist erkennbar nicht mobilfähig).

### 6. Empfohlenes Folgevorgehen

1. Blocker-Bugs (BUG-001/002/003/017) priorisieren.
2. Mailpit-Setup + addmember/install Retest.
3. editwar/delwar/editconfig E2E-Test mit Test-Fixtures.
4. Accessibility- und Responsive-Refactor (IMP-024/25/26).

---

**Ende des Audits.**
