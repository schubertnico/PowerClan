# Bootstrap-5-Migration & Accessibility-Optimierung

**Datum:** 2026-05-10
**Version:** 2.3
**Geltungsbereich:** Komplette HTML-Ausgabe (öffentlich + Admin + Installer)

---

## 1. Ziel

Die gesamte HTML-Ausgabe von PowerClan auf Bootstrap 5.3.3 umstellen und
gleichzeitig die Lesbarkeit für Nutzer mit Sehschwäche (Farbschwäche,
Low-Vision) auf WCAG-2.1-AA-/AAA-Niveau anheben. Bestehende Funktionalität,
Sicherheitsmechanismen und Kommentare bleiben unverändert.

---

## 2. Übersicht der Änderungen

### 2.1 Layoutebene

| Bereich            | Vorher                              | Nachher                                       |
| ------------------ | ----------------------------------- | --------------------------------------------- |
| Public-Hülle       | `<center><table>` (HTML 4)          | `header.pc`/`footer.pc` mit Bootstrap-Navbar  |
| Admin-Hülle        | Tabellen-Layout, Inline-Login       | Sticky Sidebar + Card-Login                   |
| Installer          | `<table>`-Wizard                    | Bootstrap-Wizard mit Sidebar + Cards          |
| Tabellen           | `bgcolor`, festgelegte Pixelbreite  | `table table-striped table-hover` responsive  |
| Formulare          | Inline-`<input>` ohne Label-Bezug   | `form-label`, `form-control`, `aria-describedby` |
| Fehler/Erfolg      | freitextige Hinweise                | Bootstrap-Alerts mit `role="alert"`           |
| Status-Anzeige     | nur Farbe (z. B. roter Text)        | Pille + Text + `aria-label` (3-fach)          |

### 2.2 Asset-Auslieferung

- Bootstrap 5.3.3 lokal in `assets/bootstrap-5.3.3/`
  (CSS 233 KB, JS Bundle 81 KB).
- **Begründung:** Containerumgebungen ohne Internetzugang werden unterstützt.
  Deterministische Ladezeiten ohne externes CDN, kein SRI-Hash-Pflege-Aufwand.

### 2.3 Header-/Footer-Fallback

`header.inc.php` und `footer.inc.php` greifen auf `header.pc`/`footer.pc`
zurück, falls `pc_config.header`/`footer` leer sind. Damit funktioniert das
Standardlayout auch in frischen Installationen, in denen die Konfiguration
noch nicht vollständig befüllt wurde.

### 2.4 Wiederverwendbare Komponenten

Neue Helfer in `functions.inc.php`:

- `pc_render_war_map_cell()` – einheitliches Bootstrap-Markup für ein
  Map-Ergebnis inkl. Farb-Pille, `aria-label` und Tooltip.

In `admin/header.inc.php`:

- `pc_admin_nav_active()` – markiert den passenden Sidebar-Eintrag als
  `active` anhand des aktuellen Skriptnamens.

---

## 3. Accessibility (Sehschwäche)

### 3.1 Hintergrund

Bootstrap-Default-Farben sind für Standard-Sehkraft konzipiert:

| Element                 | Bootstrap-Default           | Problem für Sehschwäche      |
| ----------------------- | --------------------------- | ---------------------------- |
| Standard-Link           | `#0d6efd` (Blau)            | Kontrast 4.5 : 1, grenzwertig |
| `text-body-secondary`   | `rgba(33,37,41,0.75)` Grau  | blass, schwer lesbar         |
| `link-secondary`        | blass-grau                  | wirkt "ausgegraut"           |
| `text-bg-warning`       | `#ffc107` Gelb              | sehr hell, blendet           |
| `text-white-50`         | 50 % Weiß auf Schwarz       | grenzwertig (Login-Topbar)   |
| `btn-outline-warning`   | gelber Text auf Schwarz     | für Sehschwäche schwer       |

### 3.2 Override in `powerclan.css`

Alle Anpassungen werden als CSS-Override in `powerclan.css` gepflegt –
Bootstrap-Quellen werden nicht modifiziert.

#### Sekundärtexte

- `text-body-secondary`, `text-secondary` → `#495057`
- `.small`-Variante → `#343a40`
- `.form-text` → `#343a40`, Schriftgröße 0.9 rem
- `link-secondary` → `#343a40`, immer mit Underline

#### Standardlinks

- Dunkleres Blau `#0a4ea4` (Kontrast ≥ 7 : 1 auf Weiß)
- Automatisches `text-decoration: underline` mit `text-underline-offset`
  für alle `<a>` außer Buttons / Nav-Links / Brand / Dropdown / Page-Link
- Hover-Farbe `#052c65`

#### Status-Pillen (War-Ergebnisse)

- `pc-result-won` → weiß auf `#0f5132` (dunkles Grün)
- `pc-result-lost` → weiß auf `#842029` (dunkles Rot)
- `pc-result-draw` → weiß auf `#997404` (sattes Gelb-Braun)
- Bedeutung wird zusätzlich über Text und `aria-label` transportiert.

#### Stat-Cards (`wars.php`-Übersicht)

- Override der Bootstrap-Default-Farben (`text-bg-success` etc.) auf die
  gleichen dunkleren Farben wie die Pillen.
- `font-weight: 800` für die Zahl, dicker Border (2 px).

#### Buttons

- `btn-primary`/`btn-danger` mit dunkleren Bootstrap-Variablen
  (`#0a4ea4`, `#842029`), `font-weight: 600`.
- `btn-outline-primary`/`-danger`/`-secondary` mit 2 px Border und
  fettem dunklen Text statt blasser Outline-Variante.
- `.navbar .btn-outline-warning` (Logout): vollgelber Hintergrund mit
  schwarzem fetten Text statt blass-gelbem Outline.

#### Topbar

- Eigene Klasse `pc-on-dark` für hellen Text auf dunklem Grund (`#f1f3f5`)
  ersetzt `text-white-50` in der Admin-Topbar.
- Brand-Badge in dunkler Navbar: heller Hintergrund mit dunklem Text.

#### Card-Header

- `bg-body-secondary` → dunkleres Grau `#ced4da`, Trennlinie zum Card-Body.
- `.card.border-danger` → 2 px dunkles Rot `#842029`, Card-Header analog.

#### Form-Labels

- `font-weight: 600`, Farbe `#212529` (statt Bootstrap-Default).

#### Sidebar (Admin)

- Aktiver/Hover-Zustand: weißer Text auf `#0a4ea4`.
- Sektionsüberschriften: `font-weight: 700`, `letter-spacing: 0.05em`.

#### Tabellen

- Header-Hintergrund `#ced4da`, dicke Trennlinie zum Body.
- Striped-Rows mit verstärkter Differenz (5 % statt 2 %).

#### Fokus-Indikator

- 3 px gelber Outline (`#ffc107`) statt blass-blauer Bootstrap-Default-Schatten.
- Gilt für Links, Buttons, Form-Controls, Form-Selects, Form-Checks.
- Skip-Link sichtbar bei Fokus mit gelbem Outline.

### 3.3 Bedeutung nicht nur über Farbe

Wo Farbe Bedeutung trägt, wird sie immer zusätzlich über Text und/oder
`aria-label` transportiert:

- War-Ergebnisse: Pille mit Farbe + Wert + `aria-label="Gewonnen"`/
  `"Verloren"`/`"Unentschieden"` + `title=...`
- Stat-Cards: Farbe + Beschriftung in Caps (`GEWONNEN`, `VERLOREN` …) + Zahl
- Lösch-Bestätigungen: rote `border-danger`-Card + Warn-Alert + expliziter
  Text "Dieser Vorgang kann nicht rückgängig gemacht werden"
- Map-Status in `choosewar.php`: Badge `text-bg-success` mit Häkchen ✓
  bei eingetragenem Ergebnis

---

## 4. Sicherheit

Keine Funktionsänderung. Alle bestehenden Schutzmechanismen unverändert:

- CSRF-Token (Login-CSRF + normales CSRF mit Rotation)
- Prepared Statements (`db_prepare()`)
- Brute-Force-Drossel (10 Versuche/Minute/Session)
- Server-Sessions (`pc_session_*`)
- Output-Escaping via `e()`/`htmlspecialchars()`
- HTTP-Security-Header (`X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy`)
- Path-Traversal-Schutz in `showpic.php`

---

## 5. Geänderte Dateien

### Public

- `header.pc` – HTML5-Doctype, Bootstrap-Navbar, container, Skip-Link
- `footer.pc` – container schließen, Footer, Bootstrap-Bundle-JS
- `header.inc.php` – Fallback auf `header.pc`
- `footer.inc.php` – Fallback auf `footer.pc`
- `index.php` – Cards, list-group, Alerts
- `member.php` – responsive Bootstrap-Tabelle, Detail-Card mit `<dl>`
- `wars.php` – responsive Tabelle, Status-Pillen, Showreport-Card
- `showpic.php` – Card mit `img-fluid`
- `functions.inc.php` – `default_error()` als Alert, `getwarstats()` als
  Stat-Cards, neue `pc_render_war_map_cell()`
- `install.php` – Wizard mit Sidebar, Cards, Form-Validation, Alerts
- `powerclan.css` – Accessibility-Overrides

### Admin

- `admin/header.inc.php` – Bootstrap-Navbar, Sidebar, Login-Card
- `admin/footer.inc.php` – Container schließen, Footer, JS
- `admin/index.php`, `addnews.php`, `addmember.php`, `addwar.php`,
  `choosenews.php`, `choosewar.php`, `choosemember.php`,
  `delnews.php`, `delwar.php`, `delmember.php`,
  `editnews.php`, `editwar.php`, `editmember.php`,
  `editconfig.php`, `profile.php`

### Neu

- `assets/bootstrap-5.3.3/css/bootstrap.min.css`
- `assets/bootstrap-5.3.3/js/bootstrap.bundle.min.js`

---

## 6. Verifikation

| Prüfung                       | Ergebnis                                |
| ----------------------------- | --------------------------------------- |
| `php -l` (alle 24 Dateien)    | sauber                                  |
| `composer phpstan` (Level 8)  | OK, keine Fehler                        |
| `composer test:unit`          | 67 Tests, 128 Assertions, OK            |
| `composer test:security`      | unverändert (Suite läuft länger)        |
| HTTP-Erreichbarkeit           | `/`, `/member.php`, `/wars.php`,
                                  `/admin/`, `/showpic.php` → 200          |
| `install.php`                 | 403 (korrekt, `install.lock` vorhanden) |
| Headless-Chrome-Screenshots   | Desktop & Mobile für alle Hauptseiten   |
| Docker-Build                  | nicht durchgeführt (Vorgabe)            |

---

## 7. Bekannte Folgearbeiten

- Optional: weitere Übersetzung der Admin-Texte ins Englische
- Optional: Dark-Mode (Bootstrap 5.3 unterstützt `data-bs-theme="dark"`,
  müsste noch mit den eigenen Override-Farben abgeglichen werden)
- Optional: Tabellen-Sortierung clientseitig (z. B. via DataTables)
- Optional: Inline-Bestätigung statt Bestätigungsseite für gefährliche
  Aktionen (über `data-bs-confirm`)

---

*PowerClan – PHP/MySQL Clan Portal*
*© 2001–2026 PowerScripts*
