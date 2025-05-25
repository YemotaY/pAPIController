# pAPIController

Das hier ist eine coole Web-Oberfläche, mit der du ganz entspannt deine API-Endpunkte verwalten, testen und moderieren kannst – egal ob mit SQL, statischem JSON oder eigenen PHP-Funktionen. Gebaut mit PHP, Bootstrap, jQuery und Chart.js.

## Was kann das Tool?
- **API-Management-UI**: Endpunkte easy anlegen, bearbeiten, gruppieren oder löschen.
- **Datenbank-Anbindung**: Endpunkte direkt mit SQL-Abfragen verbinden – für dynamische Daten.
- **Statische Antworten**: Mock- oder Test-Endpunkte? Einfach statisches JSON ausgeben lassen.
- **Eigene Funktionen**: Du hast spezielle Logik? Einfach PHP-Funktionen einbinden.
- **Live testen**: Endpunkte direkt in der Oberfläche ausprobieren, Parameter flexibel eingeben.
- **Statistiken & Charts**: Sieh dir in Echtzeit an, was so abgeht – mit hübschen Diagrammen (Chart.js).
- **Code-Generator**: Lass dir fertigen JS- oder PHP-Clientcode für jeden Endpunkt generieren.
- **Alles bleibt gespeichert**: Deine API-Definitionen landen in `configs/api_config.json`.

## So ist das Projekt aufgebaut
```
├── index.php                # Startpunkt & UI
├── db_helpers.php           # Datenbank-Helfer
├── functions.php            # Deine eigenen PHP-Funktionen
├── handler.php              # Regelt die API-Requests
├── internal_helper.php      # Interne Helferlein
├── output.log               # Logfile
├── configs/
│   ├── api_config.json      # Deine API-Konfig
│   ├── db_config.json       # DB-Tabellen-Konfig
│   ├── api_config_backup*.json # Backups
│   ├── api_metadata.json    # Metadaten
│   ├── api_history.log      # Nutzungsverlauf
│   └── ...                 # Noch mehr Backups, Logs, etc.
├── src/
│   ├── bootstrap.min.css    # Bootstrap-Styles
│   ├── bootstrap.bundle.min.js # Bootstrap-JS
│   ├── jquery-3.6.0.min.js  # jQuery
│   ├── chart.js             # Chart.js
│   ├── chart_logic.js       # Logik für die Statistiken
│   ├── ui_logic.js          # Alles rund ums UI
│   ├── ui_styles.css        # Deine Styles
│   ├── partials/            # Modulare UI-Komponenten (PHP)
│   └── prism/               # Syntax-Highlighting
├── code-generator/          # Vorlagen für Client-Code
│   ├── js_example.txt       # JS-Client-Vorlage
│   └── php_example.txt      # PHP-Client-Vorlage
├── logs/                    # Logfiles & Log-Klassen
│   ├── Log.php
│   └── singletonLog.php
├── tests/                   # Testskripte (z.B. Pseudo-Traffic)
│   └── PseudoTraffic/
│       ├── main.py          # Python-Traffic-Generator
│       └── venvPseudoTraffic/ # Python-Umgebung
├── docs/                    # Doku
│   └── Flows.md             # API-Flow-Doku
└── README.md                # Das hier
```

## Schnellstart
1. **Was brauchst du?**
   - PHP 7.4 oder neuer
   - Webserver (z.B. Apache/XAMPP)
   - (Optional) PostgreSQL/MySQL, falls du DB-Endpunkte willst
   - (Optional) Python 3.x, wenn du die Testskripte nutzen willst

2. **Installation**
   - Projekt ins Webserver-Root packen (z.B. `htdocs` bei XAMPP).
   - `configs/` und `logs/` müssen vom Webserver beschreibbar sein.
   - DB in `configs/db_config.json` eintragen, falls du Datenbank-Endpunkte nutzt.

3. **Loslegen**
   - Im Browser `http://localhost/pAPIController/index.php` öffnen.
   - Endpunkte anlegen, testen, Statistiken anschauen – alles über die UI.
   - Code-Generator nutzen, um direkt Clientcode zu bekommen.

4. **Testen**
   - Über das Test-Modal direkt Anfragen an deine Endpunkte schicken.
   - Oder die Skripte in `tests/` nutzen (z.B. den Python-Traffic-Generator), um automatisiert zu testen.

## Mach’s dir passend
- **Eigene PHP-Funktionen**: Schreib deine Logik in `functions.php` und binde sie in der UI ein.
- **Styles anpassen**: In `src/ui_styles.css` kannst du das Aussehen ändern.
- **UI-Logik ändern**: In `src/ui_logic.js` kannst du das Verhalten der Oberfläche anpassen.
- **Code-Vorlagen**: Passe die Dateien in `code-generator/` an, wenn du anderen Clientcode willst.
- **UI-Komponenten**: Modals & Co. findest du in `src/partials/` – einfach anpassen oder neue bauen.

## Sicherheit (kurz & knapp)
- Immer schön Benutzereingaben prüfen und bereinigen – vor allem bei eigenen Funktionen und SQL!
- In der Produktion: Zugriff auf die UI einschränken.
- Mach regelmäßig Backups von `configs/api_config.json` und Co.

## Lizenz
MIT – mach damit, was du willst!

---
Mehr Infos? Schau in den Ordner `docs/`, vor allem in `docs/Flows.md` für Details zu den Abläufen.
