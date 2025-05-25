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
├── configs/
│   ├── api_config.json      # Main API configuration
│   ├── db_config.json       # Database table config
│   └── ...                 # Backups, logs, metadata
├── src/
│   ├── bootstrap.min.css    # Bootstrap CSS
│   ├── bootstrap.bundle.min.js # Bootstrap JS
│   ├── jquery-3.6.0.min.js  # jQuery
│   ├── chart.js             # Chart.js library
│   ├── chart_logic.js       # Chart logic for stats
│   ├── ui_styles.css        # Custom styles
│   └── prism/               # Syntax highlighting
├── code-generator/          # Code templates for client code
├── logs/                    # Log files
├── tests/                   # Test scripts (e.g. pseudo-traffic)
└── docs/                    # Documentation
```

## Setup
1. **Requirements**:
   - PHP 7.4+
   - Web server (e.g. Apache/XAMPP)
   - (Optional) PostgreSQL/MySQL for DB-backed endpoints

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
   - Use scripts in `tests/` for automated or pseudo-traffic testing.

## Customization
- **Add PHP Functions**: Define custom logic in `functions.php` and reference them in the UI.
- **UI Styles**: Modify `src/ui_styles.css` for custom look and feel.
- **Code Templates**: Edit files in `code-generator/` to change generated client code.

## Security Notes
- Always validate and sanitize user input in custom functions and SQL queries.
- Restrict access to the UI in production environments.
- Regularly backup your `configs/api_config.json`.

## License
MIT License

---
For more details, see the `docs/` folder.
