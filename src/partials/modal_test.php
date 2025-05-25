<!-- Test Modal (angepasst) -->
<div class="modal fade" id="testModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="paramTabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="#pathParams" data-bs-toggle="tab">Path Parameters</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#requestData" data-bs-toggle="tab">Request Data</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <!-- Path Parameters -->
                    <div class="tab-pane fade show active" id="pathParams">
                        <div id="pathParametersContainer">
                            <!-- Dynamisch generierte Path-Parameter -->
                        </div>
                    </div>
                    <!-- Request Data -->
                    <div class="tab-pane fade" id="requestData">
                        <div id="bodyParametersContainer">
                            <div class="row mb-2 parameter-row">
                                <div class="col-5">
                                    <input type="text" class="form-control key" placeholder="Key">
                                </div>
                                <div class="col-5">
                                    <input type="text" class="form-control value" placeholder="Value">
                                </div>
                                <div class="col-2">
                                    <button type="button" class="btn btn-danger btn-sm remove-param"> <i class="fas fa-times"></i> X</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" id="addBodyParam">
                            <i class="fas fa-plus"></i> Add Parameter
                        </button>
                    </div>
                </div>
                <pre id="testResponse" class="mt-3"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
                <button type="button" class="btn btn-primary" id="sendTestRequest">
                    <i class="fas fa-paper-plane"></i> Send Request
                </button>
            </div>
        </div>
    </div>
</div>
