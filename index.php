<?php

/**
 * 0. Main 
 * UI for moderating and testing API endpoints.
 * Allows embedding request parameters in SQL queries or functions,
 * and supports static JSON responses.
 * 
 * Dependencies:
 * db_helpers.php -> Postgres helper functions
 * handler.php    -> Serves the APIs created in the UI
 */

// Imports
require_once 'db_helpers.php';
require_once 'logs/singletonLog.php';

// --- Configuration ---
$configFile = 'configs/api_config.json';
$apis = file_exists($configFile) ? (json_decode(file_get_contents($configFile), true) ?: []) : [];

// Group APIs
$groupedApis = [];
foreach ($apis as $index => $api) {
    $group = $api['group'] ?? 'Default';
    $groupedApis[$group][] = array_merge($api, ['index' => $index]);
}

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete API
    if (isset($_POST['delete'])) {
        unset($apis[$_POST['index']]);
        $apis = array_values($apis);
        file_put_contents($configFile, json_encode($apis));
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
    // Add/Update API
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

        // Validate function if provided
        if (!empty($apiData['function'])) {
            $allowedFunctions = get_defined_functions()['user'];
            if (!in_array($apiData['function'], $allowedFunctions)) {
                die("Invalid function name!");
            }
        }

        // Toggle active state
        if (isset($_POST['index'], $_POST['active'])) {
            $index = $_POST['index'];
            $active = $_POST['active'] === '1';
            if (isset($apis[$index])) {
                $apis[$index]['active'] = $active;
            }
        }
        // Edit or add new API
        elseif (isset($_POST['edit_index']) && $_POST['edit_index'] !== '') {
            $apis[$_POST['edit_index']] = $apiData;
        } else {
            $apis[] = $apiData;
        }
        file_put_contents($configFile, json_encode($apis));
    }
}

// --- Helper Functions ---
function displayData($data)
{
    return htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT));
}

// --- HTML UI ---
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Builder</title>
    <link href="./src/bootstrap.min.css" rel="stylesheet">
    <script src="src/jquery-3.6.0.min.js"></script>
    <script src="src/bootstrap.bundle.min.js"></script>
    <link href="src/prism/prism.css" rel="stylesheet"><!--Syntax Higlighting-->
    <script src="src/prism/prism.js"></script><!--Syntax Higlighting-->
    <script src="src/chart.js"></script>
    <!-- Add Font Awesome for icons -->
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
        <!-- Add API Button -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal">
                <i class="fas fa-plus"></i> Schnittstelle
            </button>
            <div>
                <button class="btn btn-secondary me-2 settings-btn" data-bs-toggle="modal" data-bs-target="#generalSettingsModal">
                    <i class="fas fa-cog"></i> General Settings
                </button>
                <button class="btn btn-info ssl-btn" data-bs-toggle="modal" data-bs-target="#sslSettingsModal">
                    <i class="fas fa-shield-alt"></i> SSL Settings
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
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </button>
                                                </form>
                                                <button class="btn btn-info btn-sm stats-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#statsModal"
                                                    data-endpoint="<?= htmlspecialchars($api['endpoint']) ?>"
                                                    data-method="<?= htmlspecialchars($api['method']) ?>">
                                                    <i class="fas fa-chart-pie"></i> Stats
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
                                                    <i class="fas fa-paper-plane"></i> Test
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
                                                    <i class="fas fa-pen"></i> Edit
                                                </button>


                                            </div>
                                        </div>
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input toggle-handle" type="checkbox"
                                                <?= $api['active'] ? 'checked' : '' ?>
                                                data-index="<?= $api['index'] ?>">
                                            <label class="form-check-label">Active</label>
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
    <!-- Code Preview Modal -->
    <div class="modal fade" id="codeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="codeModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre class="p-3 bg-light rounded" id="codeContent" style="max-height: 70vh;"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button type="button" class="btn btn-primary" onclick="copyCode()">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Statistics Modal -->
    <div class="modal fade" id="statsModal" tabindex="-1"
        aria-labelledby="statsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">API Statistics</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <select id="timePeriodSelect">
                            <option value="hour">Last Hour</option>
                            <option value="day">Last Day</option>
                            <option value="week">Last Week</option>
                            <option value="all">All Time</option>
                        </select>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-6">Total Requests</dt>
                                <dd class="col-6" id="statTotalRequests">0</dd>

                                <dt class="col-6">Success Rate</dt>
                                <dd class="col-6" id="statSuccessRate">0%</dd>

                                <dt class="col-6">Avg Response</dt>
                                <dd class="col-6" id="statAvgResponse">0ms</dd>

                                <dt class="col-6">Last Called</dt>
                                <dd class="col-6" id="statLastCalled">Never</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6>Request Distribution</h6>
                                    <canvas id="requestPieChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Response Times</h6>
                                    <canvas id="responseTimeChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Hourly Traffic</h6>
                                    <canvas id="trafficChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- General Settings Modal -->
    <div class="modal fade" id="generalSettingsModal" tabindex="-1" aria-labelledby="generalSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="generalSettingsModalLabel"><i class="fas fa-cog"></i> General Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="generalSettingsForm">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="darkModeSwitch">
                            <label class="form-check-label" for="darkModeSwitch">Enable Dark Mode</label>
                        </div>
                        <div class="mb-3">
                            <label for="authType" class="form-label">Authentication Type</label>
                            <select class="form-select" id="authType">
                                <option value="none">None</option>
                                <option value="basic">Basic Auth</option>
                                <option value="token">Token</option>
                            </select>
                        </div>
                        <div class="mb-3" id="authDetails" style="display:none;">
                            <label for="authDetailsInput" class="form-label">Auth Details</label>
                            <input type="text" class="form-control" id="authDetailsInput" placeholder="Token or credentials">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Close</button>
                    <button type="button" class="btn btn-primary" id="saveGeneralSettings"><i class="fas fa-save"></i> Save</button>
                </div>
            </div>
        </div>
    </div>
    <!-- SSL Settings Modal -->
    <div class="modal fade" id="sslSettingsModal" tabindex="-1" aria-labelledby="sslSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sslSettingsModalLabel"><i class="fas fa-shield-alt"></i> SSL Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="sslSettingsForm">
                        <div class="mb-3">
                            <label for="sslCertPath" class="form-label">Certificate Path</label>
                            <input type="text" class="form-control" id="sslCertPath" placeholder="/path/to/cert.pem">
                        </div>
                        <div class="mb-3">
                            <label for="sslKeyPath" class="form-label">Private Key Path</label>
                            <input type="text" class="form-control" id="sslKeyPath" placeholder="/path/to/key.pem">
                        </div>
                        <div class="mb-3">
                            <label for="sslCAPath" class="form-label">CA Bundle Path (optional)</label>
                            <input type="text" class="form-control" id="sslCAPath" placeholder="/path/to/ca-bundle.crt">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="sslForce">
                            <label class="form-check-label" for="sslForce">Force HTTPS</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Close</button>
                    <button type="button" class="btn btn-primary" id="saveSSLSettings"><i class="fas fa-save"></i> Save</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Postman Modal (Manual API Tester) -->
    <div class="modal fade" id="postmanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-flask"></i> API Manual Tester</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="postmanForm">
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <select class="form-select" id="pmMethod">
                                    <option>GET</option>
                                    <option>POST</option>
                                    <option>PUT</option>
                                    <option>PATCH</option>
                                    <option>DELETE</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <input type="text" class="form-control" id="pmEndpoint" placeholder="/api/endpoint">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-success w-100"><i class="fas fa-play"></i> Send</button>
                            </div>
                        </div>
                        <ul class="nav nav-tabs mb-3" id="pmTabs">
                            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#pmHeaders">Headers</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#pmQuery">Query</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#pmBody">Body</a></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="pmHeaders">
                                <div id="pmHeadersContainer"></div>
                                <button type="button" class="btn btn-secondary btn-sm mt-2" id="pmAddHeader"><i class="fas fa-plus"></i> Add Header</button>
                            </div>
                            <div class="tab-pane fade" id="pmQuery">
                                <div id="pmQueryContainer"></div>
                                <button type="button" class="btn btn-secondary btn-sm mt-2" id="pmAddQuery"><i class="fas fa-plus"></i> Add Query Param</button>
                            </div>
                            <div class="tab-pane fade" id="pmBody">
                                <textarea class="form-control" id="pmBodyInput" rows="5" placeholder="JSON body (for POST/PUT/PATCH)"></textarea>
                            </div>
                        </div>
                    </form>
                    <hr>
                    <h6>Response</h6>
                    <pre id="pmResponse" class="bg-light p-3 rounded" style="max-height: 40vh;"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- JQUERY UI MANAGEMENT -->
    <script src="src/ui_logic.js"></script>
    <script src="postman_modal.js"></script>

</body>

</html>