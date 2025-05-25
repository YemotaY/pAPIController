<?php
header('Content-Type: application/json');
require_once 'db_helpers.php';
require_once 'functions.php';
require_once 'internal_helper.php';
require_once 'logs/singletonLog.php';

// Initialisiert das Logging-System
$logger = SingletonLog::getInstance();
# $logger->log("========== NEW REQUEST STARTED ==========");

try {
    // Liest die API-Konfigurationsdatei ein
    $configFile = __DIR__ . '/configs/api_config.json';
    $configContent = @file_get_contents($configFile);
    
    if ($configContent === false) {
        // Fehler, wenn die Konfigurationsdatei fehlt
        # $logger->log("ERROR: Missing API configuration file at $configFile");
        throw new Exception('Server configuration error');
    }

    // Dekodiert die JSON-Konfiguration
    $apis = json_decode($configContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Fehler bei ungültigem JSON
        # $logger->log("ERROR: Invalid JSON in API config: " . json_last_error_msg());
        throw new Exception('Invalid server configuration');
    }

    // Ermittelt die HTTP-Methode und den angeforderten Pfad
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $endpointPath = substr($requestPath, strlen($basePath)) ?: '/';

    # $logger->log("Request details:", [
    #     'method' => $requestMethod,
    #     'base_path' => $basePath,
    #     'request_path' => $requestPath,
    #     'endpoint_path' => $endpointPath
    # ]);

    // Verarbeitet die Request-Daten (nur für POST, PUT, PATCH)
    $requestData = [];
    if (!in_array($requestMethod, ['GET', 'DELETE'])) {
        $input = file_get_contents('php://input');
        # $logger->log("Raw request input: " . $input);

        $requestData = json_decode($input, true) ?: [];
        # $logger->log("Parsed request data:", $requestData);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Fehler bei ungültigem JSON im Request-Body
            # $logger->log("JSON parse error: " . json_last_error_msg());
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
            exit;
        }
    }

    // Durchläuft alle API-Endpunkte aus der Konfiguration
    foreach ($apis as $index => $api) {
        if (!$api['active']) {
            // Überspringt inaktive Endpunkte
            # $logger->log("Skipping inactive API endpoint: " . $api['endpoint']);
            continue;
        }

        $startTime = microtime(true);
        # $logger->log("Checking API #$index: " . $api['method'] . ' ' . $api['endpoint']);

        // Erstellt ein Regex-Muster für den Endpunkt (z.B. /user/{id})
        $pattern = preg_replace('/\{([^}]+)\}/', '(?<$1>[^/]+)', $api['endpoint']);
        if (!preg_match("#^$pattern$#", $endpointPath, $matches)) {
            // Überspringt, wenn der Pfad nicht passt
            # $logger->log("Path mismatch - Pattern: $pattern, Actual: $endpointPath");
            continue;
        }

        if ($requestMethod !== $api['method']) {
            // Überspringt, wenn die HTTP-Methode nicht passt
            # $logger->log("Method mismatch - Expected: {$api['method']}, Actual: $requestMethod");
            continue;
        }

        # $logger->log("Endpoint matched: " . $api['endpoint']);
        // Extrahiert die Pfadparameter aus dem Request
        $pathParams = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        # $logger->log("Extracted path parameters:", $pathParams);

        try {
            $statusCode = 200;
            $result = [];

            if (isset($api['query']) && !empty($api['query'])) {
                // Führt eine Datenbankoperation aus, wenn ein SQL-Query definiert ist
                # $logger->log("Handling database operation");
                # $logger->log("SQL template: " . $api['query']);
                
                $result = handleDatabaseOperation($api, $requestData, $pathParams);
                $statusCode = $requestMethod === 'POST' ? 201 : 200;
                
                # $logger->log("Database operation successful", [
                #     'affected_rows' => $result['affected_rows'] ?? null,
                #     'returned_rows' => count($result['data'] ?? [])
                # ]);
            } elseif (isset($api['function']) && !empty($api['function'])) {
                // Ruft eine PHP-Funktion auf, wenn diese im API-Config angegeben ist
                # $logger->log("Handling function call: " . $api['function']);
                $params = [];
                
                // Mapped die Parameter aus Pfad, Body oder Query
                foreach ($api['param_map'] as $param => $source) {
                    $parts = explode('.', $source, 2);
                    [$type, $key] = array_pad($parts, 2, $param);

                    $params[$param] = match ($type) {
                        'path' => $pathParams[$key] ?? null,
                        'body' => $requestData[$key] ?? null,
                        'query' => $_GET[$key] ?? null,
                        default => null
                    };
                }

                # $logger->log("Mapped function parameters:", $params);

                if (!function_exists($api['function'])) {
                    // Fehler, wenn die Funktion nicht existiert
                    # $logger->log("ERROR: Missing function implementation - " . $api['function']);
                    throw new Exception("Function {$api['function']} not found");
                }

                # $logger->log("Calling function: " . $api['function']);
                $result = call_user_func($api['function'], $params);
                # $logger->log("Function returned result:", $result);
            } else {
                // Gibt statische Daten zurück, falls definiert
                # $logger->log("Returning static data");
                $result = $api['data'];
            }

            // Misst die Bearbeitungsdauer und aktualisiert die API-Metadaten
            $duration = round(microtime(true) - $startTime, 4); // seconds, with ms precision
            updateApiMetadata($api, $duration, true);
            
            # $logger->log("Request processing time: {$duration}ms");
            # $logger->log("Final response:", [
            #     'status_code' => $statusCode,
            #     'result_size' => is_array($result) ? count($result) : 1
            # ]);

            // Gibt das Ergebnis als JSON zurück
            http_response_code($statusCode);
            echo json_encode($result);
            exit;
        } catch (PDOException $e) {
            // Fehlerbehandlung bei Datenbankfehlern
            $duration = round(microtime(true) - $startTime, 4);
            updateApiMetadata($api, $duration, false);
            
            # $logger->log("DATABASE ERROR: " . $e->getMessage(), [
            #     'code' => $e->getCode(),
            #     'trace' => $e->getTraceAsString()
            # ]);
            
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            exit;
        } catch (Exception $e) {
            // Fehlerbehandlung bei sonstigen Fehlern
            $duration = round(microtime(true) - $startTime, 4);
            updateApiMetadata($api, $duration, false);
            
            # $logger->log("PROCESSING ERROR: " . $e->getMessage(), [
            #     'code' => $e->getCode(),
            #     'trace' => $e->getTraceAsString()
            # ]);
            
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    // Fehler, wenn kein passender Endpunkt gefunden wurde
    # $logger->log("Endpoint not found: $endpointPath");
    # $logger->log("Available endpoints:", array_map(fn($a) => [
    #     'method' => $a['method'],
    #     'path' => $a['endpoint'],
    #     'active' => $a['active']
    # ], $apis));

    http_response_code(404);
    echo json_encode([
        'error' => 'Endpoint not found',
        'requested_path' => $endpointPath,
        'method' => $requestMethod,
        'available_endpoints' => array_map(fn($a) => [
            'method' => $a['method'],
            'path' => $a['endpoint']
        ], $apis)
    ]);
} catch (Throwable $e) {
    // Fehlerbehandlung für unerwartete Fehler
    # $logger->log("UNHANDLED EXCEPTION: " . $e->getMessage(), [
    #     'code' => $e->getCode(),
    #     'trace' => $e->getTraceAsString()
    # ]);
    
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

# $logger->log("========== REQUEST COMPLETED ==========\n");
exit;