import requests
import time
import random
import string
import os
import json
from urllib.parse import urljoin, urlencode

# Suppress SSL warnings
requests.packages.urllib3.disable_warnings()

def load_endpoints():
    config_path = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(__file__))), 'configs','api_config.json')
    try:
        with open(config_path, 'r') as f:
            endpoints = json.load(f)
            # Convert param_map lists to dictionaries
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

endpoints = load_endpoints()

def generate_path_param(param_name):
    if param_name == 'id':
        return random.randint(1, 1000)
    else:
        return ''.join(random.choices(string.ascii_letters, k=5))

def generate_body_field(field_name):
    if field_name == 'email':
        return f"{''.join(random.choices(string.ascii_letters, k=8))}@example.com"
    elif field_name == 'name':
        return ''.join(random.choices(string.ascii_letters, k=10))
    elif field_name == 'age':
        return random.randint(18, 99)
    else:
        return ''.join(random.choices(string.ascii_letters, k=5))

def generate_query_param(param_name):
    return ''.join(random.choices(string.ascii_letters + string.digits, k=5))

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

def main(base_url):
    while True:
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
        # Generate the URL 
        url = base_url.rstrip('/') + '/' + endpoint['endpoint'].lstrip('/')
        # Replace path parameters in the URL
        for param, value in path_params.items():
            url = url.replace(f'{{{param}}}', str(value))
        # Add query parameters if present
        if query_params:
            url += '?' + urlencode(query_params)
        
        make_error = random.random() < 0.6
        if make_error:
            error_applied = apply_error(endpoint, path_params, body_params, query_params)
            if not error_applied:
                make_error = False
        
        method = endpoint['method']
        headers = {'Content-Type': 'application/json'} if method in ['POST', 'PUT', 'PATCH'] else {}
        json_data = body_params if method in ['POST', 'PUT', 'PATCH'] else None
        
        try:
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
        
        time.sleep(random.uniform(0.1, 0.2))

if __name__ == "__main__":
    import sys
    base_url = "https://127.0.0.1/papicontroller/"
    main(base_url)