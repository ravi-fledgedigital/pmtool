# Magento Cloud Error Dashboard

## Overview
A real-time log monitoring dashboard for Magento Cloud environments (staging and production). It reads logs via SSH from Magento Cloud and displays them in a prioritized, filterable UI.

## Architecture

Everything runs in a single unified server (`server.py`) on port 5000:

- **`server.py`** — Unified server that:
  - Serves the dashboard HTML at `/`
  - Runs background SSH polling threads for staging and production (every 15 minutes)
  - Exposes REST API at `/api/staging/logs`, `/api/production/logs`, `/api/<env>/status`
- **`magento_log_dashboard.html`** — Single-file React dashboard. Fetches logs from the server's API using relative URLs.
- **`magento_ssh_core.py`** — Legacy standalone core (not used by the unified server).
- **`magento_log_fetcher.py`** — Legacy standalone staging fetcher (not used; kept for reference).
- **`magento_log_fetcher_production.py`** — Legacy standalone production fetcher (not used; kept for reference).

## Ports
- **5000** — Everything: dashboard frontend + API (served by `server.py`)

## How It Works
1. Start `python3 server.py` (configured as the default workflow).
2. The server immediately begins SSH polling both staging and production in background threads.
3. Logs are re-fetched every **15 minutes** automatically.
4. The dashboard HTML polls the server's API every **60 seconds** to refresh its view.
5. Logs are classified into P1 (Critical), P2 (High), P3 (Low) priority levels.

## SSH Setup (Required)
The server SSHs into Magento Cloud using `~/.ssh/id_rsa`. You must add your SSH private key as a secret:
- Secret name: (place private key at `~/.ssh/id_rsa` or configure a path)
- The Magento Cloud SSH hosts are configured in `server.py` (`STAGING_HOST`, `PROD_HOST`).

## Configurable Constants (in server.py)
| Constant | Default | Description |
|---|---|---|
| `FETCH_INTERVAL` | `900` | Seconds between SSH log fetches (15 min) |
| `DAYS_BACK` | `2` | How many days of logs to fetch |
| `SSH_KEY` | `~/.ssh/id_rsa` | Path to SSH private key |
| `HTTP_PORT` | `5000` | Dashboard port |

## Workflow
- **Start application** — Runs `python3 server.py` on port 5000 (webview).
