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
                    <!-- Dropdown for selecting time period //TBD Im backend
                    <select id="timePeriodSelect">
                        <option value="hour">Last Hour</option>
                        <option value="day">Last Day</option>
                        <option value="week">Last Week</option>
                        <option value="all">All Time</option>
                    </select>
                    -->	
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
