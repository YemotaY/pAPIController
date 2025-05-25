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
                        <select class="form-select" id="authType" name="authType">
                            <option value="none">None</option>
                            <option value="basic">Basic Auth</option>
                            <option value="token">Token</option>
                        </select>
                    </div>
                    <div class="mb-3" id="authDetails" style="display:none;">
                        <label for="authDetailsInput" class="form-label" id="authDetailsLabel">Auth Details</label>
                        <input type="text" class="form-control" id="authDetailsInput" name="authDetailsInput" placeholder="Token or credentials">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Finish</button>
                <button type="button" class="btn btn-primary" id="saveGeneralSettings"><i class="fas fa-save"></i> Speichern</button>
            </div>
        </div>
    </div>
</div>

<script>
// Dynamisch Auth-Details anzeigen
$(function() {
    $('#authType').on('change', function() {
        var type = $(this).val();
        if (type === 'basic') {
            $('#authDetails').show();
            $('#authDetailsLabel').text('Benutzer:Passwort');
            $('#authDetailsInput').attr('placeholder', 'user:password');
        } else if (type === 'token') {
            $('#authDetails').show();
            $('#authDetailsLabel').text('Token');
            $('#authDetailsInput').attr('placeholder', 'Token');
        } else {
            $('#authDetails').hide();
        }
    });
    // Einstellungen speichern
    $('#saveGeneralSettings').on('click', function() {
        var authType = $('#authType').val();
        var authDetails = $('#authDetailsInput').val();
        localStorage.setItem('authType', authType);
        localStorage.setItem('authDetails', authDetails);
        // Speichere auch in server.json via PHP-Endpoint
        $.ajax({
            url: './internal_api.php',
            type: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
                url: '', // Optional: weitere Felder aus server.json übernehmen
                port: '',
                timeout: '',
                retryAttempts: '',
                security: {
                    type: authType,
                    details: authDetails
                }
            }),
            success: function() {
                $('#generalSettingsModal').modal('hide');
            }
        });
    });
    // Modal initialisieren
    var savedType = localStorage.getItem('authType') || 'none';
    var savedDetails = localStorage.getItem('authDetails') || '';
    $('#authType').val(savedType).trigger('change');
    $('#authDetailsInput').val(savedDetails);
});
</script>
