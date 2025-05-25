<?php

/**
 * 0. Hauptdatei
 * UI zur Moderation und zum Testen von API-Endpunkten.
 * Ermöglicht das Einbetten von Anfrageparametern in SQL-Abfragen oder Funktionen
 * und unterstützt statische JSON-Antworten.
 * 
 * Abhängigkeiten:
 * db_helpers.php -> Postgres-Hilfsfunktionen
 * handler.php    -> Bedient die im UI erstellten APIs
 */

// Importe
require_once 'db_helpers.php';
require_once 'logs/singletonLog.php';

// --- Konfiguration ---
$configFile = 'configs/api_config.json';
$apis = file_exists($configFile) ? (json_decode(file_get_contents($configFile), true) ?: []) : [];

// APIs gruppieren
$groupedApis = [];
foreach ($apis as $index => $api) {
    $group = $api['group'] ?? 'Default';
    $groupedApis[$group][] = array_merge($api, ['index' => $index]);
}

// --- Formularverarbeitung ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // API löschen
    if (isset($_POST['delete'])) {
        unset($apis[$_POST['index']]);
        $apis = array_values($apis);
        file_put_contents($configFile, json_encode($apis));
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
    // API hinzufügen/aktualisieren
    else {
        $apiData = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'endpoint' => $_POST['endpoint'] ?? '',
            'method' => isset($_POST['method']) ? strtoupper($_POST['method']) : '',
            'data' => isset($_POST['data']) ? json_decode($_POST['data'], true) : null,
            'active' => isset($_POST['active']),
            'group' => $_POST['group'] ?? '',
            'query' => $_POST['query'] ?? '',
            'function' => $_POST['function'] ?? '',
            'param_map' => isset($_POST['param_map']) ? json_decode($_POST['param_map'], true) : [],
            'table' => $_POST['table_name'] ?? null
        ];

        // Funktion validieren, falls angegeben
        if (!empty($apiData['function'])) {
            $allowedFunctions = get_defined_functions()['user'];
            if (!in_array($apiData['function'], $allowedFunctions)) {
                die("Ungültiger Funktionsname!");
            }
        }

        // Aktiv-Status umschalten
        if (isset($_POST['index'], $_POST['active'])) {
            $index = $_POST['index'];
            $active = $_POST['active'] === '1';
            if (isset($apis[$index])) {
                $apis[$index]['active'] = $active;
            }
        }
        // API bearbeiten oder neue hinzufügen
        elseif (isset($_POST['edit_index']) && $_POST['edit_index'] !== '') {
            $apis[$_POST['edit_index']] = $apiData;
        } else {
            $apis[] = $apiData;
        }
        file_put_contents($configFile, json_encode($apis));
    }
}

// --- Hilfsfunktionen ---
function displayData($data)
{
    return htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT));
}

// --- HTML UI ---
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Builder</title>
    <link href="./src/bootstrap.min.css" rel="stylesheet">
    <script src="src/jquery-3.6.0.min.js"></script>
    <script src="src/bootstrap.bundle.min.js"></script>
    <link href="src/prism/prism.css" rel="stylesheet"><!--Syntax Highlighting-->
    <script src="src/prism/prism.js"></script><!--Syntax Highlighting-->

    <script src="src/chart.js"></script>
    <!-- Font Awesome für Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    </link>
    <link href="./src/ui_styles.css" rel="stylesheet">
    <script>
        if (localStorage.getItem('darkMode') === '1') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
</head>

<body>
    <div class="container mt-4">
        <h2 class="mb-4">API Builder</h2>
        <!-- API hinzufügen Button -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal">
                <i class="fas fa-plus"></i> Schnittstelle
            </button>
            <div>
                <button class="btn btn-secondary me-2 settings-btn" data-bs-toggle="modal" data-bs-target="#generalSettingsModal">
                    <i class="fas fa-cog"></i> Allgemeine Einstellungen
                </button>
                <button class="btn btn-info ssl-btn" data-bs-toggle="modal" data-bs-target="#sslSettingsModal">
                    <i class="fas fa-shield-alt"></i> SSL Einstellungen
                </button>
                <button class="btn btn-primary  postman-btn"
                    data-endpoint="<?= htmlspecialchars($api['endpoint']) ?>"
                    data-method="<?= htmlspecialchars($api['method']) ?>">
                    <i class="fas fa-flask"></i> Postman
                </button>
            </div>
        </div>
        <?php
        include __DIR__ . '/src/partials/modal_edit.php';
        include __DIR__ . '/src/partials/modal_test.php';
        include __DIR__ . '/src/partials/modal_code.php';
        include __DIR__ . '/src/partials/modal_stats.php';
        include __DIR__ . '/src/partials/modal_settings.php';
        include __DIR__ . '/src/partials/modal_ssl.php';
        include __DIR__ . '/src/partials/modal_postman.php';
        ?>
        <!-- Schnittstellen Liste -->
        <?php foreach ($groupedApis as $groupName => $groupApis): ?>
            <div class="accordion mb-3">
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapse<?= md5($groupName) ?>">
                            <?= htmlspecialchars($groupName) ?>
                            <span class="badge bg-secondary ms-2"><?= count($groupApis) ?></span>
                        </button>
                    </h3>
                    <div id="collapse<?= md5($groupName) ?>"
                        class="accordion-collapse collapse">
                        <div class="accordion-body p-0">
                            <?php foreach ($groupApis as $api): ?>
                                <div class="card api-card mb-2 border-start border-4 
                                    <?= $api['active'] ? 'border-success' : 'border-warning' ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5><?= htmlspecialchars($api['name']) ?></h5>
                                                <span class="badge bg-primary"><?= htmlspecialchars($api['method']) ?></span>
                                                <code><?= htmlspecialchars($api['endpoint']) ?></code>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="index" value="<?= $api['index'] ?>">
                                                    <button type="submit" name="delete" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash-alt"></i> Löschen
                                                    </button>
                                                </form>
                                                <button class="btn btn-info btn-sm stats-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#statsModal"
                                                    data-endpoint="<?= htmlspecialchars($api['endpoint']) ?>"
                                                    data-method="<?= htmlspecialchars($api['method']) ?>">
                                                    <i class="fas fa-chart-pie"></i> Statistiken
                                                </button>
                                                <button class="btn btn-outline-secondary btn-sm code-gen-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#codeModal"
                                                    data-type="js"
                                                    data-endpoint="<?= htmlspecialchars($api['endpoint']) ?>"
                                                    data-method="<?= htmlspecialchars($api['method']) ?>">
                                                    <i class="fab fa-js"></i> JS Code
                                                </button>

                                                <button class="btn btn-outline-secondary btn-sm code-gen-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#codeModal"
                                                    data-type="php"
                                                    data-endpoint="<?= htmlspecialchars($api['endpoint']) ?>"
                                                    data-method="<?= htmlspecialchars($api['method']) ?>">
                                                    <i class="fab fa-php"></i> PHP Code
                                                </button>

                                                <button class="btn btn-success btn-sm send-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#testModal"
                                                    data-endpoint="<?= htmlspecialchars($api['endpoint']) ?>"
                                                    data-method="<?= htmlspecialchars($api['method']) ?>">
                                                    <i class="fas fa-paper-plane"></i> Testen
                                                </button>

                                                <button class="btn btn-warning btn-sm edit-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModal"
                                                    data-index="<?= $index ?>"
                                                    data-name="<?= htmlspecialchars($api['name']) ?>"
                                                    data-endpoint="<?= htmlspecialchars($api['endpoint']) ?>"
                                                    data-method="<?= htmlspecialchars($api['method']) ?>"
                                                    data-data='<?= displayData($api['data']) ?>'
                                                    data-active="<?= $api['active'] ? '1' : '0' ?>"
                                                    data-table="<?= htmlspecialchars($api['table'] ?? '') ?>"
                                                    data-query="<?= htmlspecialchars($api['query'] ?? '') ?>"
                                                    data-param-map='<?= !empty($api['param_map']) ? json_encode($api['param_map']) : '' ?>'
                                                    data-group="<?= htmlspecialchars($api['group'] ?? '') ?>">
                                                    <i class="fas fa-pen"></i> Bearbeiten
                                                </button>


                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center mt-2 flex-wrap">
                                            <div class="form-check form-switch me-3 mb-1">
                                                <input class="form-check-input toggle-handle" type="checkbox"
                                                    <?= $api['active'] ? 'checked' : '' ?>
                                                    data-index="<?= $api['index'] ?>">
                                                <label class="form-check-label">Aktiv</label>
                                            </div>
                                            <!--Kommentarfeld für API-Eintrag-->

                                            <?php if (!empty($api['comment'])): ?>
                                                <div class="ms-2 text-muted small mb-1 flex-shrink-0">
                                                    <i class="fas fa-comment-dots"></i> <?= htmlspecialchars($api['comment']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>


    <!-- JQUERY UI VERWALTUNG -->
    <script src="src/ui_logic.js"></script>
    <script src="src/partials/postman_modal.js"></script>

</body>

</html>