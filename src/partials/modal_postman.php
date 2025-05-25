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
