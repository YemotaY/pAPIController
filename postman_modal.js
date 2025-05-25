// postman_modal.js
// Provides a Postman-like modal for manual API testing

$(function() {
    // Append modal HTML to body
    const postmanModalHtml = `
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
    </div>`;
    $('body').append(postmanModalHtml);

    // Helper to add header/query row
    function addPmRow(container, key = '', value = '') {
        const $row = $(`
            <div class="row mb-2 pm-row">
                <div class="col-5"><input type="text" class="form-control pm-key" placeholder="Key" value="${key}"></div>
                <div class="col-5"><input type="text" class="form-control pm-value" placeholder="Value" value="${value}"></div>
                <div class="col-2"><button type="button" class="btn btn-danger btn-sm pm-remove"><i class="fas fa-times"></i></button></div>
            </div>
        `);
        $(container).append($row);
    }
    // Add initial row
    addPmRow('#pmHeadersContainer');
    addPmRow('#pmQueryContainer');
    // Add row handlers
    $('#pmAddHeader').click(() => addPmRow('#pmHeadersContainer'));
    $('#pmAddQuery').click(() => addPmRow('#pmQueryContainer'));
    // Remove row handler
    $('#pmHeadersContainer, #pmQueryContainer').on('click', '.pm-remove', function() {
        $(this).closest('.pm-row').remove();
    });

    // Open modal and prefill
    window.openPostmanModal = function(endpoint, method) {
        $('#pmEndpoint').val(endpoint || '');
        $('#pmMethod').val(method || 'GET');
        $('#pmBodyInput').val('');
        $('#pmHeadersContainer').empty();
        $('#pmQueryContainer').empty();
        addPmRow('#pmHeadersContainer');
        addPmRow('#pmQueryContainer');
        $('#pmResponse').empty();
        const modal = new bootstrap.Modal(document.getElementById('postmanModal'));
        modal.show();
    };

    // Form submit handler
    $('#postmanForm').submit(async function(e) {
        e.preventDefault();
        const method = $('#pmMethod').val();
        let endpoint = $('#pmEndpoint').val();
        const headers = {};
        $('#pmHeadersContainer .pm-row').each(function() {
            const k = $(this).find('.pm-key').val();
            const v = $(this).find('.pm-value').val();
            if (k) headers[k] = v;
        });
        // Query params
        const url = new URL(endpoint, window.location.origin);
        $('#pmQueryContainer .pm-row').each(function() {
            const k = $(this).find('.pm-key').val();
            const v = $(this).find('.pm-value').val();
            if (k) url.searchParams.append(k, v);
        });
        // Body
        let body = $('#pmBodyInput').val();
        let fetchOpts = { method, headers };
        if (["POST","PUT","PATCH"].includes(method)) {
            if (body) {
                try {
                    fetchOpts.body = JSON.stringify(JSON.parse(body));
                    fetchOpts.headers['Content-Type'] = 'application/json';
                } catch {
                    fetchOpts.body = body;
                }
            }
        }
        try {
            const resp = await fetch(url, fetchOpts);
            const text = await resp.text();
            let display;
            try { display = JSON.stringify(JSON.parse(text), null, 2); } catch { display = text; }
            $('#pmResponse').text(display);
        } catch (err) {
            $('#pmResponse').text('Error: ' + err.message);
        }
    });
});
