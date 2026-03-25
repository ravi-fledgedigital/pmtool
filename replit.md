# Magento Cloud Error Dashboard

## Overview
A real-time log monitoring dashboard for Magento Cloud environments (staging and production). It reads logs via SSH from Magento Cloud and displays them in a prioritized, filterable UI.

## Architecture

- **`server.py`** — Simple Python HTTP server that serves the dashboard HTML on port 5000.
- **`magento_log_dashboard.html`** — Single-file React dashboard (CDN React, no build step). Connects to the backend log fetcher API endpoints from the browser.
- **`magento_ssh_core.py`** — Shared SSH + log-reading core. Handles SSH connections, remote Python script injection (base64-encoded), log parsing, and HTTP API serving.
- **`magento_log_fetcher.py`** — Staging environment log fetcher. Runs an HTTP API on port 7755.
- **`magento_log_fetcher_production.py`** — Production environment log fetcher. Runs an HTTP API on port 7756.

## Ports
- **5000** — Dashboard frontend (served by `server.py`)
- **7755** — Staging log API (served by `magento_log_fetcher.py`)
- **7756** — Production log API (served by `magento_log_fetcher_production.py`)

## How It Works
1. The dashboard is served as a static HTML page on port 5000.
2. The user runs one or both Python log fetcher scripts separately (they SSH into Magento Cloud, parse logs, and serve JSON on their respective ports).
3. The dashboard JavaScript polls `localhost:7755` and `localhost:7756` every 15 seconds for log data.
4. Logs are classified into P1 (Critical), P2 (High), P3 (Low) priority levels.

## Running the Backend Fetchers
```bash
# Staging (requires SSH key access to Magento Cloud staging)
python3 magento_log_fetcher.py --days 2 --interval 60

# Production (requires SSH key access to Magento Cloud production)
python3 magento_log_fetcher_production.py --days 2 --interval 60
```

## SSH Configuration
The fetchers connect to Magento Cloud via SSH. The SSH host, port, log directory, and key path are configurable via command-line arguments. Default SSH key: `~/.ssh/id_rsa`.

## Workflow
- **Start application** — Runs `python3 server.py` on port 5000 (webview).
