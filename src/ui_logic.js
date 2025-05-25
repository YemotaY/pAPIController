// --- Konstanten ---
// Dynamically determine baseUrl based on script location and window.location
let baseUrl = '';
(function() {
    // Try to get the script tag for ui_logic.js
    const scripts = document.getElementsByTagName('script');
    let scriptSrc = '';
    for (let i = 0; i < scripts.length; i++) {
        if (scripts[i].src && scripts[i].src.includes('ui_logic.js')) {
            scriptSrc = scripts[i].src;
            break;
        }
    }
    // If scriptSrc is found, get the path up to the project root
    if (scriptSrc) {
        // Remove everything after /src/
        const srcIndex = scriptSrc.lastIndexOf('/src/');
        if (srcIndex !== -1) {
            baseUrl = scriptSrc.substring(0, srcIndex);
        } else {
            // fallback: use window.location.pathname up to last /
            baseUrl = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
        }
    } else {
        // fallback: use window.location.pathname up to last /
        baseUrl = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
    }
    // Ensure trailing slash is removed
    baseUrl = baseUrl.replace(/\/$/, '');
})();

// --- Edit-Modal-Handler ---
$('.edit-btn').click(function() {
    const $modal = $('#editModal');
    const $btn = $(this);
    $modal.find('form')[0].reset();
    $modal.find('[name="name"]').val($btn.data('name'));
    $modal.find('[name="endpoint"]').val($btn.data('endpoint'));
    $modal.find('[name="method"]').val($btn.data('method'));
    $modal.find('[name="group"]').val($btn.data('group'));
    $modal.find('#activeSwitch').prop('checked', $btn.data('active') === 1);
    $modal.find('[name="table_name"]').val($btn.data('table'));
    $modal.find('[name="query"]').val($btn.data('query'));
    const paramMap = $btn.data('param-map') || {};
    $modal.find('[name="param_map"]').val(JSON.stringify(paramMap, null, 2));
    $modal.find('[name="data"]').val(JSON.stringify($btn.data('data'), null, 2));
    $modal.find('[name="function"]').val(JSON.stringify($btn.data('function'), null, 2));
    $modal.find('#editIndex').val($btn.data('index'));
});

// --- UI-Refresh ---
function refreshUI() {
    $('body').fadeTo(200, 0.1, function() {
        location.reload(true);
    });
}

// --- Toggle-Handler ---
$('.toggle-handle').change(function() {
    const index = $(this).data('index');
    const active = $(this).prop('checked');
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `index=${index}&active=${active ? 1 : 0}`
    }).then(refreshUI);
});

// --- Formular-Submit-Handler ---
$('#editModal form').submit(function(e) {
    // ...existing validation code...
    setTimeout(refreshUI, 300);
    return true;
});

// --- Accordion Section Handler ---
$('#configAccordion').on('show.bs.collapse', function(e) {
    $('[name="data"]').prop('required', false);
    $('[name="query"]').prop('required', false);
    if (e.target.id === 'StaticSection') $('[name="data"]').prop('required', true);
    if (e.target.id === 'dbSection') $('[name="query"]').prop('required', true);
});

// --- Additional Validation ---
$('#editModal form').submit(function(e) {
    const method = $('[name="method"]').val();
    const query = $('[name="query"]').val().trim();
    const tableSelected = $('[name="table_name"]').val() !== '';
    if (tableSelected) {
        if (['DELETE', 'PUT', 'PATCH'].includes(method)) {
            if (!query.toLowerCase().includes('where')) {
                alert('Write operations require WHERE clause for safety!');
                return false;
            }
        }
        if (method === 'POST' && !query.toLowerCase().startsWith('insert')) {
            if (!confirm('POST method is typically used for INSERT operations. Continue?')) {
                return false;
            }
        }
    }
    const staticActive = $('#StaticSection').hasClass('show');
    const dataContent = $('[name="data"]').val().trim();
    if (staticActive && !dataContent) {
        alert('Response data is required for static binding!');
        $('#StaticSection').collapse('show');
        return false;
    }
    return true;
});

// --- Reset Modal ---
$('#editModal').on('hidden.bs.modal', function() {
    $(this).find('form')[0].reset();
    $(this).find('[name="table_name"]').val('');
    $(this).find('#editIndex').val('');
});

// --- Test Modal Handler ---
$('.send-btn').click(function() {
    const endpoint = $(this).data('endpoint');
    const method = $(this).data('method');
    const $modal = $('#testModal');
    $modal.find('.parameter-row').not(':first').remove();
    $modal.find('#pathParametersContainer').empty();
    $modal.find('#testResponse').empty();
    const pathParams = endpoint.match(/{([^}]+)}/g) || [];
    pathParams.forEach(param => {
        const paramName = param.replace(/[{}]/g, '');
        const $row = $(`
            <div class="row mb-2">
                <div class="col-10">
                    <input type="text" class="form-control path-param" data-param="${paramName}" placeholder="${paramName}">
                </div>
            </div>`
        );
        $('#pathParametersContainer').append($row);
    });
    $modal.data({
        endpoint: endpoint,
        method: method,
        pathParams: pathParams.map(p => p.replace(/[{}]/g, ''))
    });
});

// --- Parameter Handling ---
$('#addQueryParam').click(() => addParam('#queryParametersContainer'));
$('#addBodyParam').click(() => addParam('#bodyParametersContainer'));
function addParam(container) {
    const $row = $(container + ' .parameter-row').first().clone();
    $row.find('input').val('');
    $(container).append($row);
}
$('#addParam').click(function() {
    const $row = $('.parameter-row').first().clone();
    $row.find('input').val('');
    $('#parametersContainer').append($row);
});
$('#parametersContainer').on('click', '.remove-param', function() {
    $(this).closest('.parameter-row').remove();
});

// --- Stats Modal Handler ---
let statsInterval = null;
let currentStatsContext = { endpoint: null, method: null, period: null };

// Chart instances
let requestPieChart = null;
let responseTimeChart = null;
let trafficChart = null;

function updateCharts(stats) {
    // Pie Chart
    if (requestPieChart) {
        requestPieChart.data.datasets[0].data = [stats.success_count, stats.error_count];
        requestPieChart.update();
    }
    // Response Time Chart
    if (responseTimeChart) {
        responseTimeChart.data.labels = stats.response_times ? stats.response_times.map((_, i) => `Req ${i+1}`) : [];
        responseTimeChart.data.datasets[0].data = stats.response_times ? stats.response_times.map(t => t * 1000) : [];
        responseTimeChart.update();
    }
    // Traffic Chart
    if (trafficChart) {
        trafficChart.data.labels = ['00-04', '04-08', '08-12', '12-16', '16-20', '20-24'];
        trafficChart.data.datasets[0].data = stats.hourly_traffic || [];
        trafficChart.update();
    }
}

function renderCharts(stats) {
    if (!requestPieChart) {
        requestPieChart = new Chart(document.getElementById('requestPieChart'), {
            type: 'pie',
            data: {
                labels: ['Success', 'Errors'],
                datasets: [{
                    data: [stats.success_count, stats.error_count],
                    backgroundColor: ['#4CAF50', '#F44336'],
                    borderWidth: 2
                }]
            },
            options: {
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
    if (!responseTimeChart) {
        responseTimeChart = new Chart(document.getElementById('responseTimeChart'), {
            type: 'line',
            data: {
                labels: stats.response_times ? stats.response_times.map((_, i) => `Req ${i+1}`) : [],
                datasets: [{
                    label: 'Response Time (s)',
                    data: stats.response_times ? stats.response_times.map(t => t ) : [],
                    borderColor: '#2196F3',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                scales: { y: { beginAtZero: true, title: { display: true, text: 'ms' } } }
            }
        });
    }
    if (!trafficChart) {
        trafficChart = new Chart(document.getElementById('trafficChart'), {
            type: 'bar',
            data: {
                labels: ['00-04', '04-08', '08-12', '12-16', '16-20', '20-24'],
                datasets: [{
                    label: 'Requests',
                    data: stats.hourly_traffic || [],
                    backgroundColor: '#FF9800'
                }]
            },
            options: {
                scales: { y: { beginAtZero: true } }
            }
        });
    }
    updateCharts(stats);
}

function fetchAndRenderStats(endpoint, method, period) {
    $.ajax({
        url: './stats',
        method: 'GET',
        data: { endpoint, method, period },
        success: function(stats) {
            const successRate = stats.request_count > 0 ?
                (stats.success_count / stats.request_count * 100).toFixed(1) : 0;
            $('#statTotalRequests').text(stats.request_count);
            $('#statSuccessRate').text(`${successRate}%`);
            $('#statAvgResponse').text(`${(stats.average_response_time * 1000).toFixed(2)}ms`);
            $('#statLastCalled').text(
                stats.last_called ? new Date(stats.last_called).toLocaleString() : 'Never'
            );
            renderCharts(stats);
        }
    });
}

// Use existing statsInterval and currentStatsContext
$('.stats-btn').click(function() {
    const endpoint = $(this).data('endpoint');
    const method = $(this).data('method');
    const period = $('#timePeriodSelect').val();
    currentStatsContext = { endpoint, method, period };
    $('#statsModal').modal('show');
});
$('#timePeriodSelect').change(function() {
    if ($('#statsModal').hasClass('show') && currentStatsContext.endpoint) {
        currentStatsContext.period = $(this).val();
        fetchAndRenderStats(currentStatsContext.endpoint, currentStatsContext.method, currentStatsContext.period);
    }
});
$('#statsModal').on('shown.bs.modal', function() {
    if (currentStatsContext.endpoint) {
        fetchAndRenderStats(currentStatsContext.endpoint, currentStatsContext.method, currentStatsContext.period);
        statsInterval = setInterval(function() {
            fetchAndRenderStats(currentStatsContext.endpoint, currentStatsContext.method, currentStatsContext.period);
        }, 5000);
    }
});
$('#statsModal').on('hidden.bs.modal', function() {
    clearInterval(statsInterval);
    statsInterval = null;
    currentStatsContext = { endpoint: null, method: null, period: null };
    if (requestPieChart) { requestPieChart.destroy(); requestPieChart = null; }
    if (responseTimeChart) { responseTimeChart.destroy(); responseTimeChart = null; }
    if (trafficChart) { trafficChart.destroy(); trafficChart = null; }
});

// --- Test Request Handler ---
$('#sendTestRequest').click(async function() {
    const $modal = $('#testModal');
    const endpoint = $modal.data('endpoint');
    const method = $modal.data('method');
    const pathParams = $modal.data('pathParams') || [];

    // Collect parameters
    const parameters = {
        path: {},
        body: {}
    };

    // Path parameters
    $modal.find('.path-param').each(function() {
        const param = $(this).data('param');
        parameters.path[param] = $(this).val();
    });

    // Body/Query parameters
    $('#bodyParametersContainer .parameter-row').each(function() {
        const key = $(this).find('.key').val();
        const value = $(this).find('.value').val();
        if (key) parameters.body[key] = value;
    });

    // Replace path parameters in endpoint
    let finalEndpoint = endpoint;
    pathParams.forEach(param => {
        finalEndpoint = finalEndpoint.replace(
            `{${param}}`,
            encodeURIComponent(parameters.path[param] || '')
        );
    });

    // Build URL
    const url = new URL(baseUrl + finalEndpoint, window.location.origin);

    // Prepare request options
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };

    // Handle parameters based on method
    if (['GET', 'DELETE'].includes(method)) {
        // Add as query parameters
        Object.entries(parameters.body).forEach(([key, value]) => {
            url.searchParams.append(key, value);
        });
    } else {
        // Add as JSON body
        options.body = JSON.stringify(parameters.body);
    }

    try {
        const response = await fetch(url, options);
        const data = await response.json();
        $('#testResponse').html(JSON.stringify(data, null, 2));
    } catch (error) {
        $('#testResponse').html('Error: ' + error.message);
    }
});
$('#testModal').on('click', '.remove-param', function() {
    $(this).closest('.parameter-row').remove();
});

// --- Accordion State Persistence ---
$(document).ready(function() {
    $('.accordion-button').each(function() {
        const target = $(this).data('bs-target');
        const storedState = localStorage.getItem(target);
        if (storedState === 'true') {
            $(target).addClass('show');
            $(this).removeClass('collapsed');
        }
    });
    $('.accordion').on('shown.bs.collapse hidden.bs.collapse', function(e) {
        const target = `#${e.target.id}`;
        localStorage.setItem(target, e.type === 'shown');
    });
});

// --- Code Preview Generation ---
$('.code-gen-btn').click(async function() {
    const type = $(this).data('type');
    const endpoint = $(this).data('endpoint');
    const method = $(this).data('method').toLowerCase();
    try {
        const response = await fetch(`./code-generator/${type}_example.txt`);
        let code = await response.text();
        // Replace placeholders with actual values
        code = code.replace(/{{ENDPOINT}}/g, endpoint)
                   .replace(/{{METHOD}}/g, method)
                   .replace(/{{BASE_URL}}/g, baseUrl);
        // Set code content and language for Prism highlighting
        const lang = type === 'js' ? 'javascript' : 'php';
        $('#codeContent').text(code).attr('class', `language-${lang}`);
        Prism.highlightElement($('#codeContent')[0]);
        $('#codeModalTitle').text(`${type.toUpperCase()} Client Code`);
    } catch (error) {
        $('#codeContent').text('Error loading code template: ' + error.message);
    }
});

function copyCode() {
    const code = $('#codeContent').text();
    navigator.clipboard.writeText(code);
}

// General Settings Modal logic
$('#authType').change(function() {
    if ($(this).val() !== 'none') {
        $('#authDetails').show();
    } else {
        $('#authDetails').hide();
    }
});
// --- Dark Mode Toggle: wendet den Dark Mode sofort an, ohne Reload oder Schließen des Modals ---
$('#darkModeSwitch').change(function() {
    // Prüft, ob der Schalter aktiviert ist
    const darkMode = $(this).prop('checked');
    if (darkMode) {
        // Speichert Einstellung und aktiviert Dark Mode
        localStorage.setItem('darkMode', '1');
        document.documentElement.classList.add('dark-mode');
    } else {
        // Speichert Einstellung und deaktiviert Dark Mode
        localStorage.setItem('darkMode', '0');
        document.documentElement.classList.remove('dark-mode');
    }
});
// Speichert die allgemeinen Einstellungen (Dark Mode & Authentifizierungstyp), schließt das Modal aber nicht
$('#saveGeneralSettings').click(function() {
    // Speichert Dark Mode Einstellung
    const darkMode = $('#darkModeSwitch').prop('checked');
    if (darkMode) {
        localStorage.setItem('darkMode', '1');
        document.documentElement.classList.add('dark-mode');
    } else {
        localStorage.setItem('darkMode', '0');
        document.documentElement.classList.remove('dark-mode');
    }
    // Speichert den Authentifizierungstyp
    const authType = $('#authType').val();
    localStorage.setItem('authType', authType);
    alert('Allgemeine Einstellungen gespeichert (Demo).');
    // Das Modal bleibt offen
});
// Wendet beim Laden der Seite den Dark Mode sofort an (ganz oben im Skript für sofortigen Effekt)
if (localStorage.getItem('darkMode') === '1') {
    document.documentElement.classList.add('dark-mode');
} else {
    document.documentElement.classList.remove('dark-mode');
}
$(function() {
    // Synchronisiert den Schalter-Zustand nach dem Laden des DOMs
    if (localStorage.getItem('darkMode') === '1') {
        $('#darkModeSwitch').prop('checked', true);
    } else {
        $('#darkModeSwitch').prop('checked', false);
    }
    // Stellt den gespeicherten Authentifizierungstyp wieder her
    const savedAuthType = localStorage.getItem('authType');
    if (savedAuthType) {
        $('#authType').val(savedAuthType).trigger('change');
    }
});
// --- Postman Modal Trigger ---
$('.postman-btn').click(function() {
    const endpoint = $(this).data('endpoint');
    const method = $(this).data('method');
    window.openPostmanModal(endpoint, method);
});