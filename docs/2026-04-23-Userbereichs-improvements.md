# Userbereichs-Audit – Improvements

**Projekt:** PowerClan (PHP/MySQL Clan-Portal, Version 2.00)
**Datum:** 2026-04-23
**Auditor:** Senior QA / Dev-Auditor
**Zielsystem:** http://localhost:8086/
**Geltungsbereich:** Userbereich = Admin-/Member-Login-Bereich unter `/admin/`

> Enthält ausschliesslich Workflow- und UX-Vorschläge – keine Bugs.

---

## Improvement-Index

| ID | Bereich | Priorität | Titel |
|---|---|---|---|
| IMP-001 | Onboarding | Hoch | Kein öffentlicher Selfservice / Passwort-Reset |
| IMP-002 | Installer | Hoch | `install.php` nach Abschluss selbst deaktivieren (Lockfile) |
| IMP-003 | Auth | Hoch | Fehlermeldung bei Login-Fehlschlag |
| IMP-004 | Auth | Hoch | "Angemeldet bleiben"-Checkbox und Zwei-Faktor-Optionen |
| IMP-005 | Dashboard | Mittel | Rechte-Übersicht als Tabelle mit Symbolen statt freitextlicher Aufzählung |
| IMP-006 | Dashboard | Mittel | Sidebar: Aktive Seite hervorheben |
| IMP-007 | Profile | Hoch | Passwort-Stärke-Indikator + Min-Länge-Prüfung |
| IMP-008 | Profile | Mittel | "Profil speichern"-Button ohne Reset-Button, oder klar getrennt |
| IMP-009 | Profile | Mittel | ICQ-Feld entfernen oder als "veraltet" markieren |
| IMP-010 | Profile | Niedrig | `age`-Feld akzeptiert 0, Default sollte leer sein |
| IMP-011 | Member-Verwaltung | Hoch | Bestätigungsseiten statt `javascript:history.back()` |
| IMP-012 | Member-Verwaltung | Hoch | Rollen statt 11 Einzel-Checkboxen (z. B. "News-Redakteur", "War-Manager") |
| IMP-013 | Member-Verwaltung | Mittel | Auditlog / History für Mitgliederaktionen |
| IMP-014 | News | Mittel | BBCode-Editor sichtbar statt nur einfache Buttons; Vorschau-Funktion |
| IMP-015 | News | Mittel | News-Kategorisierung, Entwürfe, Veröffentlichungs-Datum |
| IMP-016 | Wars | Hoch | Datums-/Zeit-Picker statt 5 `<select>`-Drop-Downs |
| IMP-017 | Wars | Hoch | Ergebnis-Eingabe mit klarer UX (Link/Home/Away statt "res1:res2") |
| IMP-018 | Wars | Mittel | Screenshot-Upload statt URL-Eingabe |
| IMP-019 | Wars | Mittel | Liga-Liste konfigurierbar (derzeit hart codiert: Friendly/Training/ESPL/Clanbase) |
| IMP-020 | Konfiguration | Hoch | Farb-Picker mit Live-Vorschau statt Rohtext-Hex-Eingabe |
| IMP-021 | Konfiguration | Mittel | Getrennte Abschnitte (Allgemein / Design / Limits) |
| IMP-022 | Mail | Hoch | Mailpit/SMTP-Container in `.docker/docker-compose.yml` aufnehmen |
| IMP-023 | Mail | Mittel | Fallback-Hinweis im UI, falls Mailversand fehlschlägt |
| IMP-024 | Responsiveness | Hoch | Layout verwendet `<table>`-Design aus 2001 – nicht mobilfähig |
| IMP-025 | Accessibility | Hoch | Fehlende `<label for>`-Bindings in Formularen |
| IMP-026 | Accessibility | Mittel | Farbauswahl prüft keinen Kontrast, `<center>`-Tag verwendet |
| IMP-027 | i18n | Mittel | Deutsch hartkodiert; i18n-Layer fehlt |
| IMP-028 | Logging | Hoch | PHP-Fehler sichtbar machen (UI / Admin-Bereich) statt nur in `logs/php-error.log` |
| IMP-029 | Session | Mittel | Session-Timeout und "Letzte Aktivität"-Feedback |
| IMP-030 | UI Feedback | Hoch | Success-/Error-Meldungen als Toasts/Banner statt einzelne `<a href=...>` |
| IMP-031 | Navigation | Mittel | Breadcrumbs im Admin-Bereich |
| IMP-032 | Output | Niedrig | Veraltete HTML-Attribute (`bgcolor`, `border`, `cellpadding`) durch CSS ersetzen |

---

## IMP-001: Kein öffentlicher Selfservice / Passwort-Reset

- **Bereich:** Onboarding
- **URL / Route:** `/admin/`, kein public-Pendant
- **Beobachtung:** Mitglieder werden ausschliesslich von Admins angelegt. Verliert ein Member sein Passwort, muss er einen Admin kontaktieren; es gibt keinen "Passwort vergessen"-Flow.
- **Problem im Workflow:** Admin-Flaschenhals; User-Abhängigkeit; risikoreich in kleinen Clans mit nur einem Superadmin.
- **Auswirkung:** Mitglieder bleiben ausgesperrt; Admins sind überlastet.
- **Verbesserungsvorschlag:** "Passwort vergessen?"-Link mit Token-Mail (z. B. 15 min Gültigkeit, Rate-limited). Optional Self-Registration mit Admin-Freigabe.
- **Priorität:** Hoch

## IMP-002: Installer soll sich nach Erstinstallation selbst deaktivieren

- **Bereich:** Installer
- **URL / Route:** `/install.php`
- **Beobachtung:** `install.php` liegt nach erfolgreicher Installation weiter im Webroot.
- **Problem im Workflow:** Admin muss Datei manuell löschen (und daran denken).
- **Auswirkung:** Einladung zu Datenverlust (siehe BUG-001/BUG-003).
- **Verbesserungsvorschlag:** Lockfile `install.lock` oder Eintrag in `pc_config` (z. B. `install_completed=1`). `install.php` prüft zuerst diesen Marker und zeigt Installation als abgeschlossen an.
- **Priorität:** Hoch

## IMP-003: Fehlermeldung bei Login-Fehlschlag

- **Bereich:** Authentifizierung
- **URL / Route:** `POST /admin/?login=YES`
- **Beobachtung:** Falsches Passwort / unbekannte E-Mail → stummer Re-Render.
- **Problem im Workflow:** User weiß nicht, ob er getippt hat, der Server erreichbar ist, oder das Passwort falsch war.
- **Auswirkung:** Support-Tickets, Frust.
- **Verbesserungsvorschlag:** Flash-Meldung "Login fehlgeschlagen" oberhalb des Formulars. Generisch halten (keine Enumeration).
- **Priorität:** Hoch

## IMP-004: "Angemeldet bleiben"-Option und 2FA

- **Bereich:** Authentifizierung
- **URL / Route:** `/admin/`
- **Beobachtung:** Standard-Cookie-Lebensdauer = 30 Tage, ohne Opt-in.
- **Problem im Workflow:** Kein Unterschied zwischen Kiosk-Login und persönlichem PC.
- **Auswirkung:** Sicherheits-Abwägung liegt beim User.
- **Verbesserungsvorschlag:** Checkbox "Angemeldet bleiben" (sonst Session-Only-Cookie). Optional TOTP/2FA für Superadmin.
- **Priorität:** Hoch

## IMP-005: Rechte-Übersicht als Tabelle

- **Bereich:** Dashboard
- **URL / Route:** `/admin/index.php`
- **Beobachtung:** `<ul>`-Liste "Member hinzufügen", "News editieren", … (bis zu 10 Einträge).
- **Problem im Workflow:** Schwer zu erfassen, welche Berechtigungskombinationen vorhanden sind.
- **Auswirkung:** Rollen-Nachvollziehbarkeit erschwert.
- **Verbesserungsvorschlag:** Tabelle mit Ressource × Aktion (News/Wars/Member × add/edit/del) und ✔/✘-Symbolen.
- **Priorität:** Mittel

## IMP-006: Sidebar hebt aktive Seite nicht hervor

- **Bereich:** Dashboard
- **URL / Route:** `/admin/header.inc.php`
- **Beobachtung:** Sidebar-Links sind alle gleich gestylt; keine Markierung der aktuellen Seite.
- **Problem im Workflow:** Admin verliert Orientierung in Mehrschritt-Flows.
- **Verbesserungsvorschlag:** CSS-Klasse für aktuellen `$_SERVER['PHP_SELF']`-Match.
- **Priorität:** Mittel

## IMP-007: Passwort-Stärke-Indikator + Min-Länge

- **Bereich:** Profile / editmember
- **URL / Route:** `/admin/profile.php`
- **Beobachtung:** Keine Min-Länge, keine Komplexitätsprüfung, `maxlength=25` (BUG-026).
- **Problem im Workflow:** User können "123" als Passwort setzen.
- **Auswirkung:** Brute-Force-anfällig.
- **Verbesserungsvorschlag:** Mindestlänge 12, Zxcvbn-ähnlicher Stärke-Indikator clientseitig, server-side `strlen >= 12` Prüfung.
- **Priorität:** Hoch

## IMP-008: Reset-Button überschreibt Profil-Daten ohne Warnung

- **Bereich:** Profile
- **URL / Route:** `/admin/profile.php`
- **Beobachtung:** Form hat `<input type="reset" value="Daten zurücksetzten">` (BUG-032) direkt neben Submit.
- **Problem im Workflow:** Fehlklick löscht alle bereits getippten Änderungen.
- **Verbesserungsvorschlag:** Reset-Button entfernen; oder separat mit Bestätigung.
- **Priorität:** Mittel

## IMP-009: ICQ-Feld veraltet

- **Bereich:** Profile
- **URL / Route:** `/admin/profile.php`
- **Beobachtung:** ICQ wurde 2024 abgeschaltet.
- **Problem im Workflow:** Feld fragt nach längst toter Identität.
- **Verbesserungsvorschlag:** Feld entfernen; durch Discord-Handle / Matrix-ID / Mastodon ersetzen.
- **Priorität:** Mittel

## IMP-010: `age=0` Default irritiert

- **Bereich:** Profile
- **URL / Route:** `/admin/profile.php`
- **Beobachtung:** Default-Alter = 0, wird als "N/A" in der öffentlichen Member-Ansicht angezeigt (member.php:109).
- **Problem im Workflow:** User muss aktiv 0 belassen ODER echtes Alter eintippen; Feld ist 2-stellig → Alter >99 nicht möglich.
- **Verbesserungsvorschlag:** Feld optional (NULL), Input-Range 13–99 oder Geburtsjahr.
- **Priorität:** Niedrig

## IMP-011: Bestätigungsseiten statt `javascript:history.back()`

- **Bereich:** Validierung
- **URL / Route:** fast alle POST-Handler (`profile.php`, `addmember.php`, `editmember.php`, `addnews.php`, `addwar.php`)
- **Beobachtung:** Fehlerfall rendert `<a href="javascript:history.back()">…</a>`. Nicht barrierefrei, JS-Abhängig, Meldung verliert sich optisch.
- **Verbesserungsvorschlag:** Fehler inline am Feld anzeigen oder POST/Redirect/GET-Pattern mit Flash-Message.
- **Priorität:** Hoch

## IMP-012: Rollen statt 11 Einzel-Checkboxen

- **Bereich:** Member-Verwaltung
- **URL / Route:** `/admin/addmember.php`, `/admin/editmember.php`
- **Beobachtung:** 9 Einzel-Rechte-Checkboxen (member_add/edit/del, news_add/edit/del, wars_add/edit/del) plus superadmin.
- **Problem im Workflow:** Fehleranfällig; keine Vorschaurolle.
- **Verbesserungsvorschlag:** Vordefinierte Rollen ("Fighter", "Reporter", "Warlord", "Admin", "Superadmin") mit Rechte-Matrix. Einzel-Overrides optional.
- **Priorität:** Hoch

## IMP-013: Auditlog für Mitgliederaktionen

- **Bereich:** Member-Verwaltung
- **URL / Route:** —
- **Beobachtung:** Kein Log, wer wen wann geändert hat.
- **Auswirkung:** Bei Vorfällen ist die Ursache nicht nachvollziehbar.
- **Verbesserungsvorschlag:** Tabelle `pc_audit (actor_id, action, target_id, changes, timestamp)`.
- **Priorität:** Mittel

## IMP-014: BBCode-Editor schwer entdeckbar

- **Bereich:** News
- **URL / Route:** `/admin/addnews.php`, `/admin/editnews.php`
- **Beobachtung:** BBCode existiert in `functions.inc.php::news_replace`, aber UI bietet keine Hinweise oder Vorschau.
- **Verbesserungsvorschlag:** Toolbar-Buttons, Markdown-Unterstützung als Alternative, Live-Preview.
- **Priorität:** Mittel

## IMP-015: News-Workflow

- **Bereich:** News
- **Beobachtung:** Keine Entwürfe, kein Veröffentlichungs-Datum (`time = time()` direkt), keine Kategorien.
- **Verbesserungsvorschlag:** Status (draft/published/archived), geplante Veröffentlichung, Kategorien/Tags.
- **Priorität:** Mittel

## IMP-016: Datum-/Zeit-Picker für Wars

- **Bereich:** War-Verwaltung
- **URL / Route:** `/admin/addwar.php`, `/admin/editwar.php`
- **Beobachtung:** 5 `<select>`-Felder für Tag/Monat/Jahr/Stunde/Minute. Validierung fehlt (BUG-024).
- **Verbesserungsvorschlag:** `<input type="datetime-local">` mit Server-Side-`checkdate`.
- **Priorität:** Hoch

## IMP-017: Ergebnis-Eingabe Wars

- **Bereich:** War-Verwaltung
- **URL / Route:** `/admin/editwar.php`
- **Beobachtung:** Ergebnisse als `res1="16:14"`-String abgelegt; Parsen per `explode(':', …)`.
- **Problem im Workflow:** Tippfehler möglich (`16,14`, `16-14`). Keine Plausibilität (negative Zahlen).
- **Verbesserungsvorschlag:** Zwei Input-Felder `home_score`/`away_score` als `INT`, DB-Spaltenänderung.
- **Priorität:** Hoch

## IMP-018: Screenshot-Upload statt URL

- **Bereich:** War-Verwaltung
- **Beobachtung:** `screen1`–`screen3` nehmen URL auf, kein Upload.
- **Problem im Workflow:** Host-Abhängigkeit (Imageshack/Imgur) erhöht Link-Rot.
- **Verbesserungsvorschlag:** File-Upload in `/images/wars/`, Validierung Mime-Type, 5 MB Limit.
- **Priorität:** Mittel

## IMP-019: Liga-Liste konfigurierbar

- **Bereich:** War-Verwaltung
- **URL / Route:** `/admin/addwar.php`
- **Beobachtung:** `$leagues = ['Friendly', 'Training', 'ESPL', 'Clanbase'];` in `admin/header.inc.php`.
- **Problem im Workflow:** Neue Ligen erfordern Code-Edit.
- **Verbesserungsvorschlag:** Eigene Tabelle `pc_leagues` mit CRUD im Konfig-Bereich.
- **Priorität:** Mittel

## IMP-020: Farb-Picker im editconfig

- **Bereich:** Konfiguration
- **URL / Route:** `/admin/editconfig.php`
- **Beobachtung:** `tablebg1`/`tablebg2`/`tablebg3`/`clrwon`/`clrdraw`/`clrlost` als Freitext-Hex.
- **Problem im Workflow:** Typo = zerstörtes Layout. Keine Validierung `/^#[0-9A-Fa-f]{6}$/`.
- **Verbesserungsvorschlag:** `<input type="color">` mit Live-Preview des Layouts.
- **Priorität:** Hoch

## IMP-021: Konfiguration in Sektionen gruppieren

- **Bereich:** Konfiguration
- **Beobachtung:** 14 Felder in flacher Liste.
- **Verbesserungsvorschlag:** Tabs "Allgemein / Darstellung / Limits / Pfade".
- **Priorität:** Mittel

## IMP-022: Mailpit-Container in docker-compose

- **Bereich:** Mail / Dev-Env
- **URL / Route:** `.docker/docker-compose.yml`
- **Beobachtung:** Andere Power*-Projekte haben eigene Mailpit-Container (z. B. `powerphpboard_mailpit` auf Port 8032). PowerClan nicht.
- **Problem im Workflow:** `@mail()` verschluckt Mails; Entwickler kann nicht prüfen, was versandt wird.
- **Auswirkung:** BUG-023.
- **Verbesserungsvorschlag:** Service `mailpit` + `msmtp`/`sendmail`-Konfiguration im PHP-Container.
- **Priorität:** Hoch

## IMP-023: UI-Feedback bei fehlgeschlagenem Mailversand

- **Bereich:** addmember / editmember / install
- **Beobachtung:** Success-Text behauptet "per E-Mail benachrichtigt" unabhängig vom `mail()`-Rückgabewert.
- **Verbesserungsvorschlag:** Warnbanner "Mail-Server nicht erreichbar – bitte Passwort manuell übermitteln".
- **Priorität:** Mittel

## IMP-024: Mobilfähiges Layout

- **Bereich:** Frontend / Admin
- **Beobachtung:** Pures `<table>`-Layout mit fixer Breite 95%/750px; `bgcolor`-Attribute.
- **Problem im Workflow:** Smartphone-Darstellung unbrauchbar.
- **Verbesserungsvorschlag:** Bootstrap/Tailwind/Flex-Layout; `viewport`-Meta-Tag existiert im öffentlichen Header, Admin-Header hat keinen.
- **Priorität:** Hoch

## IMP-025: Accessibility – Label-Bindings

- **Bereich:** Formulare
- **Beobachtung:** Alle Eingabefelder ohne `<label for="…">`; Labels liegen als `<b>` in der Zelle daneben.
- **Problem im Workflow:** Screenreader können Feldtyp nicht ansagen.
- **Verbesserungsvorschlag:** `<label>` + `id` an jedem Input.
- **Priorität:** Hoch

## IMP-026: Accessibility – weitere Grundlagen

- **Bereich:** Frontend / Admin
- **Beobachtung:** `<center>`-Tag (HTML5 obsolet), kein Fokus-Stil, keine ARIA-Rollen, kein Kontrast-Check für Farbauswahl.
- **Verbesserungsvorschlag:** CSS-Refactor, Lighthouse-Audit, WCAG-AA Kontrastprüfung bei Farbwahl.
- **Priorität:** Mittel

## IMP-027: i18n-Layer

- **Bereich:** Strings
- **Beobachtung:** Alle Texte deutsch hartkodiert (auch Fehlermeldungen, Copyright).
- **Verbesserungsvorschlag:** Separates `lang/de.php` + Helper `t('key')`.
- **Priorität:** Mittel

## IMP-028: PHP-Fehler sichtbar im Admin

- **Bereich:** Observability
- **Beobachtung:** Fatal Errors landen nur in `logs/php-error.log`; UI rendert nur leeres `<center>` (BUG-019).
- **Verbesserungsvorschlag:** `try/catch` in Handlern mit `echo default_error(...)`; im Admin ein "System Status"-Panel, das letzte Logzeilen zeigt.
- **Priorität:** Hoch

## IMP-029: Session-Timeout / Last-Active

- **Bereich:** Authentifizierung
- **Beobachtung:** Cookie-Lebensdauer 30 Tage, keine Server-Session-Prüfung (PHP-Session wird nur für CSRF-Token genutzt).
- **Verbesserungsvorschlag:** Eigene Session-Tabelle `pc_sessions (token, user_id, last_active, expires)`; sliding-expiration + "Online seit"-Anzeige im Profil.
- **Priorität:** Mittel

## IMP-030: Success-/Error-Meldungen als Toast/Banner

- **Bereich:** UI Feedback
- **Beobachtung:** Meldungen als einsamer `<center><a href="xyz.php">…</a></center>` – leicht zu übersehen.
- **Verbesserungsvorschlag:** Flash-Messages oben auf der Zielseite, farblich codiert (grün/gelb/rot).
- **Priorität:** Hoch

## IMP-031: Breadcrumbs

- **Bereich:** Navigation
- **Beobachtung:** Admin-Bereich bietet keine Orientierung in mehrstufigen Prozessen (choosenews → editnews → choosenews).
- **Verbesserungsvorschlag:** Einfache Breadcrumbs `Admin / News / editieren / "Testnews 1"`.
- **Priorität:** Mittel

## IMP-032: Veraltete HTML-Attribute durch CSS ersetzen

- **Bereich:** Frontend / Admin
- **Beobachtung:** `bgcolor`, `border`, `cellpadding`, `valign` direkt im HTML. HTML5 markiert diese als deprecated.
- **Verbesserungsvorschlag:** Migration in `admin/powerclan.css` / `powerclan.css` mit klaren Klassennamen.
- **Priorität:** Niedrig

---
