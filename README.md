# pAPIController

A web-based UI for moderating, testing, and managing API endpoints with support for SQL queries, static JSON responses, and custom PHP functions. Built with PHP, Bootstrap, jQuery, and Chart.js.

## Features
- **API Management UI**: Create, edit, group, and delete API endpoints.
- **Database Integration**: Bind endpoints to SQL queries for dynamic data.
- **Static Responses**: Serve static JSON for mock/testing endpoints.
- **Custom Functions**: Call PHP functions for advanced logic.
- **Live Testing**: Test endpoints directly from the UI with dynamic parameter input.
- **Statistics & Analytics**: View real-time request stats and traffic charts (Chart.js).
- **Code Generation**: Generate ready-to-use JS/PHP client code for each endpoint.
- **Config Persistence**: All API definitions are stored in `configs/api_config.json`.

## Project Structure
```
├── index.php                # Main UI and entry point
├── db_helpers.php           # Database helper functions
├── functions.php            # Custom PHP functions for endpoints
├── handler.php              # API request handler
├── internal_helper.php      # Internal utilities
├── output.log               # Log output file
├── configs/
│   ├── api_config.json      # Main API configuration
│   ├── db_config.json       # Database table config
│   ├── api_config_backup*.json # Backup config files
│   ├── api_metadata.json    # API metadata
│   ├── api_history.log      # API usage history
│   └── ...                 # Additional backups, logs, metadata
├── src/
│   ├── bootstrap.min.css    # Bootstrap CSS
│   ├── bootstrap.bundle.min.js # Bootstrap JS
│   ├── jquery-3.6.0.min.js  # jQuery
│   ├── chart.js             # Chart.js library
│   ├── chart_logic.js       # Chart logic for stats
│   ├── ui_logic.js          # UI logic and interactivity
│   ├── ui_styles.css        # Custom styles
│   ├── partials/            # Modular UI PHP components (modals, etc.)
│   └── prism/               # Syntax highlighting
├── code-generator/          # Code templates for client code
│   ├── js_example.txt       # JS client code template
│   └── php_example.txt      # PHP client code template
├── logs/                    # Log files and PHP log classes
│   ├── Log.php
│   └── singletonLog.php
├── tests/                   # Test scripts (e.g. pseudo-traffic)
│   └── PseudoTraffic/
│       ├── main.py          # Python pseudo-traffic generator
│       └── venvPseudoTraffic/ # Python virtual environment
├── docs/                    # Documentation
│   └── Flows.md             # API flow documentation
└── README.md                # Project readme
```

## Setup
1. **Requirements**:
   - PHP 7.4+
   - Web server (e.g. Apache/XAMPP)
   - (Optional) PostgreSQL/MySQL for DB-backed endpoints
   - (Optional) Python 3.x for running test scripts in `tests/`

2. **Installation**:
   - Clone or copy the project to your web server root (e.g. `htdocs` for XAMPP).
   - Ensure `configs/` and `logs/` are writable by the web server.
   - Configure your database in `configs/db_config.json` if using DB endpoints.

3. **Usage**:
   - Open `http://localhost/pAPIController/index.php` in your browser.
   - Use the UI to add/edit endpoints, test them, and view stats.
   - Use the code generation feature to get ready-to-use client code.

4. **Testing**:
   - Use the built-in test modal to send requests to your endpoints.
   - Use scripts in `tests/` (e.g. Python pseudo-traffic generator) for automated or pseudo-traffic testing.

## Customization
- **Add PHP Functions**: Define custom logic in `functions.php` and reference them in the UI.
- **UI Styles**: Modify `src/ui_styles.css` for custom look and feel.
- **UI Logic**: Update `src/ui_logic.js` for custom UI interactivity.
- **Code Templates**: Edit files in `code-generator/` to change generated client code.
- **UI Components**: Modify or add PHP modal files in `src/partials/` for custom dialogs.

## Security Notes
- Always validate and sanitize user input in custom functions and SQL queries.
- Restrict access to the UI in production environments.
- Regularly backup your `configs/api_config.json` and related config files.

## License
MIT License

---
For more details, see the `docs/` folder, especially `docs/Flows.md` for API flow documentation.
