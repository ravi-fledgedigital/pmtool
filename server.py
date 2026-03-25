#!/usr/bin/env python3
"""
Unified Magento Cloud Log Dashboard Server
- Serves the dashboard HTML on port 5000
- Background threads SSH into Magento Cloud every 15 minutes for both
  staging and production environments
- Exposes /api/staging/logs and /api/production/logs for the dashboard
"""

import base64
import json
import os
import stat
import subprocess
import sys
import textwrap
import threading
import time
from http.server import BaseHTTPRequestHandler, HTTPServer
from pathlib import Path


# ---------------------------------------------------------------------------
# SSH key setup — reads SSH_PRIVATE_KEY env var and writes to ~/.ssh/id_rsa
# ---------------------------------------------------------------------------

def setup_ssh_key():
    key = os.environ.get("SSH_PRIVATE_KEY", "").strip()
    if not key:
        print("[server] WARNING: SSH_PRIVATE_KEY not set — SSH connections will fail.", flush=True)
        return

    # Replit secrets strip newlines, storing the key as one long space-separated line.
    # Reconstruct the proper PEM format.
    if "\n" not in key:
        header = "-----BEGIN OPENSSH PRIVATE KEY-----"
        footer = "-----END OPENSSH PRIVATE KEY-----"
        # Remove header/footer and collapse all whitespace to get raw base64
        body = key.replace(header, "").replace(footer, "").replace(" ", "")
        # Re-wrap base64 content at 70 chars per line (OpenSSH standard)
        wrapped = "\n".join(body[i:i+70] for i in range(0, len(body), 70))
        key = f"{header}\n{wrapped}\n{footer}"

    ssh_dir = Path.home() / ".ssh"
    ssh_dir.mkdir(mode=0o700, exist_ok=True)
    key_path = ssh_dir / "id_rsa"
    key_path.write_text(key + "\n")
    key_path.chmod(0o600)
    print(f"[server] SSH key written to {key_path}", flush=True)


# ---------------------------------------------------------------------------
# Config
# ---------------------------------------------------------------------------

STAGING_HOST    = "1.ent-pwibh56ncnoes-staging2-5zxmgzy@ssh.ap-3.magento.cloud"
STAGING_PORT    = 22
STAGING_LOG_DIR = "/app/pwibh56ncnoes_stg2/var/log"

PROD_HOST    = "1.ent-pwibh56ncnoes-production-vohbr3y@ssh.ap-3.magento.cloud"
PROD_PORT    = 22
PROD_LOG_DIR = "/app/pwibh56ncnoes/var/log"

SSH_KEY      = "~/.ssh/id_rsa"
DAYS_BACK    = 2
FETCH_INTERVAL = 900   # 15 minutes
HTTP_PORT    = 5000

DASHBOARD_FILE = Path(__file__).parent / "magento_log_dashboard.html"

# ---------------------------------------------------------------------------
# Remote Python script (base64-injected)
# ---------------------------------------------------------------------------

REMOTE_PYTHON = textwrap.dedent(r"""
import os, sys, glob, gzip, json, re, signal
from datetime import datetime, timedelta

LOG_DIR = os.environ.get("M_LOG_DIR", "/app/var/log")
DAYS    = int(os.environ.get("M_DAYS", "2"))
DEBUG   = os.environ.get("M_DEBUG", "0") == "1"

def dbg(msg):
    if DEBUG:
        print("[DBG] " + str(msg), file=sys.stderr, flush=True)

def _on_alarm(sig, frame):
    dbg("TIMEOUT hit — flushing partial results")
try:
    signal.signal(signal.SIGALRM, _on_alarm)
    signal.alarm(80)
except Exception:
    pass

dbg("START  LOG_DIR=" + LOG_DIR + "  DAYS=" + str(DAYS))

if not os.path.isdir(LOG_DIR):
    dbg("Dir not found, searching /app ...")
    for root, dirs, files in os.walk("/app", topdown=True):
        dirs[:] = [d for d in dirs if d not in
                   ["vendor","pub","node_modules",".git","generated","setup"]]
        if any(f in files for f in ["system.log","exception.log"]):
            LOG_DIR = root
            dbg("Found: " + LOG_DIR)
            break
    else:
        dbg("No log dir found!")
        print("[]"); sys.exit(0)

dbg("Using: " + LOG_DIR)
try:
    dbg("Files: " + str(sorted(os.listdir(LOG_DIR))[:50]))
except Exception as e:
    dbg("listdir err: " + str(e))

LOG_FILES   = ["system.log", "exception.log", "support_report.log", "cloud.log", "cron.log"]
KEEP_LEVELS = {"CRITICAL","ALERT","EMERGENCY","ERROR","WARNING","NOTICE"}
LEVEL_MAP   = {100:"DEBUG",200:"INFO",250:"NOTICE",300:"WARNING",400:"ERROR",500:"CRITICAL",550:"ALERT",600:"EMERGENCY"}
cutoff      = datetime.now() - timedelta(days=DAYS)
entries     = []

TS_RE = re.compile(
    r"^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[Z+\-][^\]]*)?)\]"
    r"\s+([^.:\s]+)\.([A-Z]+):\s*(.*)"
)

def parse_ts(raw):
    s = re.sub(r"[Z]$", "", raw.strip())
    s = re.sub(r"[+\-]\d{2}:?\d{2}$", "", s).replace("T", " ").strip()
    try:
        return datetime.fromisoformat(s)
    except Exception:
        return None

def parse_ts_iso(raw):
    s = re.sub(r"[+\-]\d{2}:\d{2}$", "", raw.strip())
    s = re.sub(r"Z$", "", s)
    try:
        return datetime.fromisoformat(s)
    except Exception:
        return None

def parse_text(text, source):
    lines = text.splitlines()
    i = 0; kept = 0; old = 0; skip = 0

    while i < len(lines):
        line = lines[i].strip()
        i += 1
        if not line:
            continue

        if line.startswith("{"):
            try:
                obj = json.loads(line)
                msg       = obj.get("message", "")
                level_num = obj.get("level", 0)
                level_str = obj.get("level_name", LEVEL_MAP.get(level_num, "")).upper()
                channel   = obj.get("channel", "app")
                dt_raw    = obj.get("datetime", "")
                ctx       = obj.get("context", {})

                if level_str not in KEEP_LEVELS:
                    skip += 1; continue

                ts = parse_ts_iso(dt_raw) if dt_raw else None
                if ts is None:
                    ts = datetime.now()
                if ts < cutoff:
                    old += 1; continue

                if isinstance(ctx, dict) and ctx.get("file"):
                    ctx_info = str(ctx["file"])
                    if ctx.get("line"):
                        ctx_info += ":" + str(ctx["line"])
                    msg = msg + "\n  Context: " + ctx_info

                entries.append({
                    "timestamp": ts.isoformat(),
                    "source":    source,
                    "channel":   channel,
                    "level":     level_str,
                    "message":   msg[:4000],
                })
                kept += 1
                continue
            except (ValueError, KeyError):
                pass

        m = TS_RE.match(line)
        if not m:
            continue
        ts_raw, channel, level, first_msg = m.groups()
        ts = parse_ts(ts_raw)

        body_lines = []
        while i < len(lines):
            nxt = lines[i]
            if TS_RE.match(nxt.strip()):
                break
            body_lines.append(nxt)
            i += 1

        if ts is None or ts < cutoff:
            old += 1; continue
        if level not in KEEP_LEVELS:
            skip += 1; continue

        first_msg = re.sub(r"\s*\{[^}]{0,300}\}\s*\[\]\s*$", "", first_msg.strip()).strip()
        body = "\n".join(body_lines).strip()
        body = re.sub(r"\n?\[\]\s*$", "", body).strip()
        full_msg = (first_msg + "\n" + body).strip() if body else first_msg

        entries.append({
            "timestamp": ts.isoformat(),
            "source":    source,
            "channel":   channel.strip(),
            "level":     level,
            "message":   full_msg[:4000],
        })
        kept += 1

    dbg("  " + source + ": kept=" + str(kept) + " old=" + str(old) + " skip=" + str(skip))


def read_plain(path, source):
    size = os.path.getsize(path)
    if size == 0: dbg("  Empty: " + source); return
    dbg("Reading " + source + " (" + str(size) + " bytes)")
    try:
        with open(path, "r", errors="replace") as f:
            parse_text(f.read(), source)
    except Exception as e:
        dbg("  Error: " + str(e))

def read_gz(path, source):
    dbg("Reading gz: " + source)
    try:
        with gzip.open(path, "rt", errors="replace") as f:
            parse_text(f.read(), source)
    except Exception as e:
        dbg("  gz error: " + str(e))

def rot_num(p):
    import re as _re
    m = _re.search(r"[\.\-](\d+)\.gz$", os.path.basename(p))
    return int(m.group(1)) if m else 9999

for log_name in LOG_FILES:
    plain = os.path.join(LOG_DIR, log_name)
    if os.path.exists(plain):
        read_plain(plain, log_name)
    else:
        dbg("Not found: " + plain)

    gz_candidates = set()
    stem = log_name.split(".")[0]
    for pat in [
        os.path.join(LOG_DIR, log_name + "-*.gz"),
        os.path.join(LOG_DIR, log_name + ".*.gz"),
        os.path.join(LOG_DIR, log_name + "*.gz"),
    ]:
        gz_candidates.update(glob.glob(pat))
    for g in glob.glob(os.path.join(LOG_DIR, "*.gz")):
        if stem in os.path.basename(g):
            gz_candidates.add(g)

    for gz_path in sorted(gz_candidates, key=rot_num)[:3]:
        read_gz(gz_path, log_name + "[" + os.path.basename(gz_path) + "]")

seen = set(); unique = []
for e in sorted(entries, key=lambda x: x["timestamp"], reverse=True):
    key = (e["level"], e["source"].split("[")[0], e["message"][:100])
    if key in seen: continue
    seen.add(key); unique.append(e)

dbg("Done: unique=" + str(len(unique)) + " raw=" + str(len(entries)))
print(json.dumps(unique[:3000]))
""").strip()


# ---------------------------------------------------------------------------
# SSH helpers
# ---------------------------------------------------------------------------

def _ssh_args(host, port, key_path):
    args = [
        "ssh", "-p", str(port),
        "-o", "StrictHostKeyChecking=no",
        "-o", "BatchMode=yes",
        "-o", "ConnectTimeout=30",
        "-o", "ServerAliveInterval=60",
        "-o", "LogLevel=ERROR",
    ]
    key = Path(key_path).expanduser()
    if key.exists():
        args += ["-i", str(key)]
    return args + [host]


def ssh_exec(host, port, key_path, remote_cmd, timeout=110):
    cmd = _ssh_args(host, port, key_path) + [remote_cmd]
    r = subprocess.run(cmd, capture_output=True, text=True, timeout=timeout)
    return r.stdout, r.stderr, r.returncode


def fetch_once(host, ssh_port, key_path, log_dir, days, debug=False):
    b64 = base64.b64encode(REMOTE_PYTHON.encode()).decode()
    env = f'M_LOG_DIR="{log_dir}" M_DAYS="{days}" M_DEBUG="{"1" if debug else "0"}"'
    remote_cmd = f'{env} bash -c "echo {b64} | base64 -d | python3"'
    stdout, stderr, code = ssh_exec(host, ssh_port, key_path, remote_cmd)
    if code != 0:
        raise RuntimeError(f"SSH exit {code}: {stderr[:300]}")
    for line in reversed(stdout.strip().splitlines()):
        line = line.strip()
        if line.startswith("["):
            try:
                return json.loads(line)
            except json.JSONDecodeError:
                pass
    raise RuntimeError(f"No JSON in SSH output: {repr(stdout[:200])}")


# ---------------------------------------------------------------------------
# Shared state for both environments
# ---------------------------------------------------------------------------

def make_env_state():
    return {
        "entries": [],
        "last_fetch": None,
        "status": "starting",
        "error": "",
        "fetching": False,
        "fetch_count": 0,
    }


STATE = {
    "staging":    make_env_state(),
    "production": make_env_state(),
}
LOCKS = {
    "staging":    threading.Lock(),
    "production": threading.Lock(),
}

ENV_CONFIG = {
    "staging": {
        "label":   "Staging",
        "host":    STAGING_HOST,
        "port":    STAGING_PORT,
        "log_dir": STAGING_LOG_DIR,
    },
    "production": {
        "label":   "Production",
        "host":    PROD_HOST,
        "port":    PROD_PORT,
        "log_dir": PROD_LOG_DIR,
    },
}


def poll_loop(env_key):
    cfg   = ENV_CONFIG[env_key]
    state = STATE[env_key]
    lock  = LOCKS[env_key]
    label = cfg["label"]

    while True:
        with lock:
            state["fetching"] = True
            state["status"]   = "fetching"
        print(f"[{label}] Fetching from {cfg['host']} …", flush=True)
        t0 = time.time()
        try:
            entries = fetch_once(cfg["host"], cfg["port"], SSH_KEY,
                                 cfg["log_dir"], DAYS_BACK)
            elapsed = time.time() - t0
            with lock:
                state["entries"]     = entries
                state["last_fetch"]  = time.strftime("%Y-%m-%dT%H:%M:%S")
                state["status"]      = "ok"
                state["error"]       = ""
                state["fetching"]    = False
                state["fetch_count"] += 1
            print(f"[{label}] OK — {len(entries)} entries in {elapsed:.1f}s "
                  f"(next in {FETCH_INTERVAL}s)", flush=True)
        except Exception as e:
            with lock:
                state["status"]   = "error"
                state["error"]    = str(e)
                state["fetching"] = False
            print(f"[{label}] Error: {e}", flush=True)
        time.sleep(FETCH_INTERVAL)


# ---------------------------------------------------------------------------
# HTTP server
# ---------------------------------------------------------------------------

class Handler(BaseHTTPRequestHandler):
    def log_message(self, fmt, *args):
        pass

    def _send_json(self, obj, status=200):
        body = json.dumps(obj).encode()
        self.send_response(status)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        self.send_header("Access-Control-Allow-Origin", "*")
        self.end_headers()
        self.wfile.write(body)

    def _send_html(self, content):
        self.send_response(200)
        self.send_header("Content-Type", "text/html; charset=utf-8")
        self.send_header("Content-Length", str(len(content)))
        self.end_headers()
        self.wfile.write(content)

    def do_OPTIONS(self):
        self.send_response(204)
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, OPTIONS")
        self.end_headers()

    def do_GET(self):
        path = self.path.split("?")[0]

        # Dashboard HTML
        if path in ("/", "/index.html"):
            try:
                content = DASHBOARD_FILE.read_bytes()
                self._send_html(content)
            except Exception as e:
                self.send_error(500, str(e))
            return

        # API: /api/<env>/logs | /api/<env>/status | /api/<env>/codefile
        if path.startswith("/api/"):
            parts = path.strip("/").split("/")
            if len(parts) >= 2:
                env_key = parts[1]   # "staging" or "production"
                endpoint = parts[2] if len(parts) >= 3 else "status"
                if env_key in STATE:
                    if endpoint == "logs":
                        with LOCKS[env_key]:
                            snap = dict(STATE[env_key])
                        self._send_json({
                            "meta": {
                                "status":     snap["status"],
                                "last_fetch": snap["last_fetch"],
                                "error":      snap["error"],
                                "fetching":   snap["fetching"],
                            },
                            "entries": snap["entries"],
                        })
                        return
                    elif endpoint == "status":
                        with LOCKS[env_key]:
                            snap = dict(STATE[env_key])
                        self._send_json({
                            "status":      snap["status"],
                            "last_fetch":  snap["last_fetch"],
                            "fetch_count": snap["fetch_count"],
                            "error":       snap["error"],
                            "count":       len(snap["entries"]),
                        })
                        return
                    elif endpoint == "codefile":
                        import urllib.parse, os as _os
                        raw = self.path.split("?", 1)
                        qs  = urllib.parse.parse_qs(raw[1]) if len(raw) > 1 else {}
                        rel_path = qs.get("path", [""])[0]
                        if not rel_path:
                            self._send_json({"error": "missing path"}, 400)
                            return
                        # Returns not-found if local codebase doesn't exist on this host
                        self._send_json({"exists": False, "content": ""})
                        return

        self.send_error(404, "Not found")


class Server(HTTPServer):
    allow_reuse_address = True


# ---------------------------------------------------------------------------
# Entry point
# ---------------------------------------------------------------------------

if __name__ == "__main__":
    # Write SSH key from environment variable to ~/.ssh/id_rsa
    setup_ssh_key()

    # Start background polling threads for both environments
    for env_key in ("staging", "production"):
        t = threading.Thread(target=poll_loop, args=(env_key,), daemon=True)
        t.start()
        print(f"[server] Started polling thread: {env_key}", flush=True)

    server = Server(("0.0.0.0", HTTP_PORT), Handler)
    print(f"[server] Dashboard at http://0.0.0.0:{HTTP_PORT}", flush=True)
    print(f"[server] SSH fetch interval: {FETCH_INTERVAL}s ({FETCH_INTERVAL//60} min)", flush=True)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\n[server] Stopped.", flush=True)
