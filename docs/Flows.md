# Request Parsing
Client Request
     │
     ▼
Parse HTTP Method (GET/POST/PUT/DELETE)
     │
     ▼
Extract Base Path (e.g., /api/v1 from URL)
     │
     ▼
Derive Endpoint Path (e.g., /users/{id})
     │
     ▼
Read Request Body (for non-GET/DELETE):
   ┌───────────────┐
   │php://input    │
   └───────────────┘
           │
           ▼
     JSON Decode → Store in $requestData
           │
           ▼
    Validate JSON Syntax
           │
           ▼
      Log raw input



# Endpoint Matching

Iterate APIs in config:
   │
   ▼
Check: Active? → Method Match? → Path Pattern Match?
   │
   ▼
On Match:
   Extract Path Parameters (e.g., {id} → 123)
   │
   ▼
Start Execution Timer


# Request Processing

Branch Based on API Type:
├─ Database Query API (has 'query' config)
│  │
│  ▼
│  handleDatabaseOperation()
│  │
│  ▼
│  Build SQL from template (replace :placeholders)
│  │
│  ▼
│  Execute with parameters from:
│  - Path params (/{id})
│  - Request body (POST/PUT data)
│
└─ Function API (has 'function' config)
   │
   ▼
   Parameter Mapping via param_map:
   ┌───────────────┐
   │ path.id       │→ From URL /users/123
   │ body.email    │→ From JSON {email: "test@test.com"}
   │ query.page    │→ From ?page=2
   └───────────────┘
           │
           ▼
   Validate function exists
           │
           ▼
   Execute function with mapped params

# Response Generation:

If Success:
   ▼
Update API Stats (success count, response time)
   ▼
Set HTTP Status Code:
   - 201 Created for POST
   - 200 OK otherwise
   ▼
Return JSON response

If Error:
   ▼
Update API Stats (error count)
   ▼
Set Error Code (400/500/404)
   ▼
Return JSON error message



# Data Sources Diagram

┌───────────────┐       ┌───────────────┐
│  URL Path     │       │  Request Body │
│ /users/{id}   │       │  JSON/Form    │
└───────┬───────┘       └───────┬───────┘
        │                       │
        ├─────────┐     ┌───────┘
        ▼         ▼     ▼
┌───────────────────────────────┐
│  Parameter Mapping System     │
│  path.id → 123               │
│  body.email → test@test.com  │
│  query.page → 2              │
└───────────────────────────────┘
        │
        ▼
┌─────────────────┐
│ Handler Function│
│ or SQL Query    │
└─────────────────┘