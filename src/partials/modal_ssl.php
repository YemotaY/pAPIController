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
