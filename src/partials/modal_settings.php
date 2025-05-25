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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Finish</button>
            </div>
        </div>
    </div>
</div>
