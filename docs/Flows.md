# Anfrage-Parsing

```
Client-Anfrage
   │
   ▼
HTTP-Methode ermitteln (GET/POST/PUT/DELETE)
   │
   ▼
Basis-Pfad extrahieren (z.B. /api/v1 aus URL)
   │
   ▼
Endpoint-Pfad ableiten (z.B. /users/{id})
   │
   ▼
Request-Body einlesen (bei nicht-GET/DELETE):
   ┌───────────────┐
   │php://input    │
   └───────────────┘
       │
       ▼
JSON dekodieren → Speichern in $requestData
       │
       ▼
JSON-Syntax validieren
       │
       ▼
Rohdaten loggen
```

# Endpoint-Abgleich

```
APIs aus Konfiguration durchlaufen
   │
   ▼
Prüfen: Aktiv? → Methode passt? → Pfad-Muster passt?
   │
   ▼
Bei Treffer:
   Pfad-Parameter extrahieren (z.B. {id} → 123)
   │
   ▼
Ausführungs-Timer starten
```

# Anfrageverarbeitung

```
Verzweigung je nach API-Typ:

┌─────────────────────────────────────────────┐
│ Datenbank-API (mit 'query'-Konfiguration)   │
└─────────────────────────────────────────────┘
   │
   ▼
handleDatabaseOperation()
   │
   ▼
SQL aus Template bauen (ersetze :Platzhalter)
   │
   ▼
Ausführen mit Parametern aus:
- Pfad-Parametern (/{id})
- Request-Body (POST/PUT-Daten)

ODER

┌─────────────────────────────────────────────┐
│ Funktions-API (mit 'function'-Konfiguration)│
└─────────────────────────────────────────────┘
   │
   ▼
Parameter-Mapping via param_map:
   path.id    → aus URL /users/123
   body.email → aus JSON {email: "test@test.com"}
   query.page → aus ?page=2
   │
   ▼
Funktion validieren
   │
   ▼
Funktion mit gemappten Parametern ausführen
```

# Antwortgenerierung

```
Bei Erfolg:
   ▼
API-Statistiken aktualisieren (Erfolgszähler, Antwortzeit)
   ▼
HTTP-Status setzen:
   - 201 Created bei POST
   - 200 OK sonst
   ▼
JSON-Antwort zurückgeben

Bei Fehler:
   ▼
API-Statistiken aktualisieren (Fehlerzähler)
   ▼
Fehlercode setzen (400/500/404)
   ▼
JSON-Fehlermeldung zurückgeben
```

# Datenquellen-Diagramm

```
┌───────────────┐       ┌───────────────┐
│  URL-Pfad     │       │  Request-Body │
│ /users/{id}   │       │  JSON/Form    │
└───────┬───────┘       └───────┬───────┘
        │                       │
        ├─────────┐     ┌───────┘
        ▼         ▼     ▼
┌───────────────────────────────┐
│  Parameter-Mapping-System     │
│  path.id → 123                │
│  body.email → test@test.com   │
│  query.page → 2               │
└───────────────────────────────┘
        │
        ▼
┌─────────────────┐
│ Handler-Funktion│
│ oder SQL-Query  │
└─────────────────┘
```