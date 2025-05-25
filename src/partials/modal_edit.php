<!-- Edit/Add Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bearbeiten/Neu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="accordion" id="configAccordion">
                        <!-- Grundeinstellungen -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#apiSection"
                                    aria-expanded="true"
                                    aria-controls="apiSection">
                                    Grundkonfiguration
                                </button>
                            </h2>
                            <div id="apiSection" class="accordion-collapse collapse show"
                                data-bs-parent="#configAccordion">
                                <div class="accordion-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            Endpoint ID
                                            <input type="text" class="form-control" name="name"
                                                placeholder="API Name" required>
                                        </div>
                                        <div class="col-md-6">
                                            Pfad
                                            <input class="form-control" name="endpoint"
                                                placeholder="/endpoint-path"
                                                pattern="^\/[a-zA-Z0-9_/-]+"
                                                required>
                                            <small class="form-text text-muted">Muss mit einem / starten (z.B. /doob)</small>
                                        </div>
                                        <div class="col-md-4">
                                            <select class="form-select" name="method" required>
                                                <option>GET</option>
                                                <option>POST</option>
                                                <option>PUT</option>
                                                <option>DELETE</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="active" id="activeSwitch">
                                            <label class="form-check-label" for="activeSwitch">Active</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Datenbank anbindung -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#dbSection"
                                    aria-expanded="false"
                                    aria-controls="dbSection">
                                    Datenbankanbindung
                                </button>
                            </h2>
                            <div id="dbSection" class="accordion-collapse collapse"
                                data-bs-parent="#configAccordion">
                                <div class="accordion-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <select class="form-select" name="table_name">
                                                <option value="">Select Database Table</option>
                                                <?php
                                                $dbConfig = json_decode(file_get_contents('configs/db_config.json'), true);
                                                foreach ($dbConfig['tables'] as $tableName => $config): ?>
                                                    <option value="<?= htmlspecialchars($tableName) ?>">
                                                        <?= htmlspecialchars($tableName) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <textarea class="form-control" name="query"
                                                placeholder="SQL Query Template (use :param_name for parameters)"
                                                rows="3"></textarea>
                                            <small class="form-text text-muted">Beispiel: SELECT * FROM users WHERE id = :id</small>
                                        </div>
                                        <div class="col-12">
                                            <textarea class="form-control" name="param_map"
                                                placeholder="Parameter mapping (JSON)"
                                                rows="2"></textarea>
                                            <small class="form-text text-muted">Beispiel: {"id": "path.userId"}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Funktions anbindung -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#functionSection"
                                    aria-expanded="false"
                                    aria-controls="functionSection">
                                    Funktionseinstellung
                                </button>
                            </h2>
                            <div id="functionSection" class="accordion-collapse collapse"
                                data-bs-parent="#configAccordion">
                                <div class="accordion-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <input type="text" class="form-control" name="function"
                                                placeholder="Function name">
                                            <small class="form-text text-muted">
                                                Available functions:
                                                <?= implode(', ', get_defined_functions()['user']) ?>
                                            </small>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-text">
                                                Create functions in <code>functions.php</code>.<br>
                                                Function signature: <code>function myFunc($params) { ... }</code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Statische Antwort -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#StaticSection"
                                    aria-expanded="false"
                                    aria-controls="StaticSection">
                                    Statische Daten
                                </button>
                            </h2>
                            <div id="StaticSection" class="accordion-collapse collapse"
                                data-bs-parent="#configAccordion">
                                <div class="accordion-body">
                                    <div class="col-12">
                                        <textarea class="form-control" name="data"
                                            placeholder="Response Data (JSON)"
                                            rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="group" placeholder="Group Name">
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save API
                    </button>
                </div>
                <input type="hidden" name="edit_index" id="editIndex">
            </form>
        </div>
    </div>
</div>
