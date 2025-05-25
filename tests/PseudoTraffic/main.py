# Dieses Skript simuliert zufälligen API-Traffic für Testzwecke.
# Es lädt Endpunkt-Konfigurationen, generiert zufällige Parameter und sendet Anfragen an die API.

import requests
import time
import random
import string
import os
import json
from urllib.parse import urljoin, urlencode

# Unterdrückt SSL-Warnungen (z.B. bei selbstsignierten Zertifikaten)
requests.packages.urllib3.disable_warnings()

# Lädt die Endpunkt-Konfigurationen aus einer JSON-Datei
# Gibt ein Dictionary mit Endpunkt-Definitionen zurück
# Wandelt ggf. param_map-Listen in Dictionaries um
# Beendet das Programm bei Fehlern

def load_endpoints():
    config_path = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(__file__))), 'configs','api_config.json')
    try:
        with open(config_path, 'r') as f:
            endpoints = json.load(f)
            # param_map-Listen in Dictionaries umwandeln
            for ep in endpoints.values():
                if isinstance(ep.get('param_map'), list):
                    ep['param_map'] = {item['param']: item['source'] for item in ep['param_map']}
            return endpoints
    except FileNotFoundError:
        print(f"Error: Configuration file not found at {config_path}")
        exit(1)
    except json.JSONDecodeError:
        print(f"Error: Invalid JSON in configuration file {config_path}")
        exit(1)

# Endpunkte werden beim Start geladen
endpoints = load_endpoints()

# Generiert einen zufälligen Wert für einen Path-Parameter
# Für 'id' wird eine Zufallszahl, sonst ein Zufallsstring erzeugt
def generate_path_param(param_name):
    if param_name == 'id':
        return random.randint(1, 1000)
    else:
        return ''.join(random.choices(string.ascii_letters, k=5))

# Generiert einen zufälligen Wert für ein Body-Feld
# Je nach Feldname werden unterschiedliche Werte erzeugt (z.B. E-Mail, Name, Alter)
def generate_body_field(field_name):
    if field_name == 'email':
        return f"{''.join(random.choices(string.ascii_letters, k=8))}@example.com"
    elif field_name == 'name':
        return ''.join(random.choices(string.ascii_letters, k=10))
    elif field_name == 'age':
        return random.randint(18, 99)
    else:
        return ''.join(random.choices(string.ascii_letters, k=5))

# Generiert einen zufälligen Wert für einen Query-Parameter
def generate_query_param(param_name):
    return ''.join(random.choices(string.ascii_letters + string.digits, k=5))

# Wendet mit einer gewissen Wahrscheinlichkeit Fehler auf die Parameter an
# Simuliert ungültige oder fehlende Werte für Path, Body oder Query
# Gibt True zurück, wenn ein Fehler angewendet wurde
def apply_error(endpoint, path_params, body_params, query_params):
    possible_errors = []
    if path_params:
        possible_errors.extend(['invalid_path'] * 2)
    if body_params:
        possible_errors.extend(['invalid_body', 'missing_body'])
    if query_params:
        possible_errors.append('invalid_query')
    
    if not possible_errors:
        return False
    
    error_type = random.choice(possible_errors)
    
    if error_type == 'invalid_path':
        param = random.choice(list(path_params.keys()))
        path_params[param] = 'invalid'
    elif error_type == 'invalid_body':
        param = random.choice(list(body_params.keys()))
        original = body_params[param]
        if isinstance(original, int):
            body_params[param] = 'invalid'
        else:
            body_params[param] = random.randint(1000, 2000)
    elif error_type == 'missing_body':
        param = random.choice(list(body_params.keys()))
        del body_params[param]
    elif error_type == 'invalid_query':
        param = random.choice(list(query_params.keys()))
        query_params[param] = 'invalid'
    
    return True

# Hauptfunktion: Endlosschleife, die zufällige Requests an die API sendet
def main(base_url):
    while True:
        # Zufälligen Endpunkt auswählen
        ep_id = random.choice(list(endpoints.keys()))
        endpoint = endpoints[ep_id]
        if not endpoint.get('active', True):
            continue
        
        param_map = endpoint.get('param_map', {})
        if isinstance(param_map, list):
            param_map = {}
        
        path_params = {}
        body_params = {}
        query_params = {}
        
        # Parameter für Path, Body und Query generieren
        for param, source in param_map.items():
            if source.startswith('path.'):
                param_name = source.split('.')[1]
                path_params[param_name] = generate_path_param(param_name)
            elif source.startswith('body.'):
                field_name = source.split('.')[1]
                body_params[field_name] = generate_body_field(field_name)
            elif source.startswith('query.'):
                field_name = source.split('.')[1]
                query_params[field_name] = generate_query_param(field_name)
        # URL zusammensetzen
        url = base_url.rstrip('/') + '/' + endpoint['endpoint'].lstrip('/')
        # Path-Parameter in der URL ersetzen
        for param, value in path_params.items():
            url = url.replace(f'{{{param}}}', str(value))
        # Query-Parameter anhängen, falls vorhanden
        if query_params:
            url += '?' + urlencode(query_params)
        
        # Mit 20% Wahrscheinlichkeit Fehler einbauen
        make_error = random.random() < 0.2
        if make_error:
            error_applied = apply_error(endpoint, path_params, body_params, query_params)
            if not error_applied:
                make_error = False
        
        method = endpoint['method']
        headers = {'Content-Type': 'application/json'} if method in ['POST', 'PUT', 'PATCH'] else {}
        json_data = body_params if method in ['POST', 'PUT', 'PATCH'] else None
        
        try:
            # Anfrage an die API senden
            response = requests.request(
                method=method,
                url=url,
                json=json_data,
                headers=headers,
                params=query_params if method in ['GET', 'DELETE'] else None,
                verify=False
            )
            print(f"Request to {url} ({method}) returned {response.status_code}")
            if make_error:
                print("^ Intentional error introduced")
        except Exception as e:
            print(f"Error requesting {url}: {e}")
        
        # Kurze Pause zwischen den Requests
        time.sleep(random.uniform(0.1, 0.2))

# Einstiegspunkt: Startet das Skript mit einer Basis-URL, wenn es direkt ausgeführt wird
if __name__ == "__main__":
    import sys
    base_url = "https://127.0.0.1/papicontroller/"
    main(base_url)