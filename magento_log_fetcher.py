#!/usr/bin/env python3
"""
Magento Log Fetcher — STAGING
Reads logs via SSH from Magento Cloud staging environment.
Serves on http://localhost:7755 for the dashboard.

Usage:
    python3 magento_log_fetcher.py [--days 2] [--interval 60] [--debug]
"""
import argparse, sys, threading
from pathlib import Path
sys.path.insert(0, str(Path(__file__).parent))
from magento_ssh_core import make_state, run_server

SSH_HOST   = "1.ent-pwibh56ncnoes-staging2-5zxmgzy@ssh.ap-3.magento.cloud"
SSH_PORT   = 22
LOG_DIR    = "/app/pwibh56ncnoes_stg2/var/log"
HTTP_PORT  = 7755
LABEL      = "Staging"
# Local codebase path — used to read source files for Claude analysis
CODEBASE   = "/Applications/MAMP/htdocs/staging2/asics_ot_magento"

def main():
    p = argparse.ArgumentParser(description=f"Magento log fetcher [{LABEL}]")
    p.add_argument("--host",     default=SSH_HOST)
    p.add_argument("--root",     default="/app/pwibh56ncnoes_stg2")
    p.add_argument("--key",      default="~/.ssh/id_rsa")
    p.add_argument("--ssh-port", type=int, default=SSH_PORT, dest="ssh_port")
    p.add_argument("--days",     type=int, default=2)
    p.add_argument("--interval", type=int, default=60)
    p.add_argument("--http-port",type=int, default=HTTP_PORT, dest="http_port")
    p.add_argument("--debug",    action="store_true")
    a = p.parse_args()

    log_dir = a.root.rstrip("/") + "/var/log"
    state   = make_state()
    lock    = __import__("threading").Lock()

    run_server(LABEL, a.http_port, state, lock,
               a.host, a.ssh_port, a.key, log_dir, a.days, a.debug, a.interval,
               codebase=CODEBASE)

if __name__ == "__main__":
    main()
