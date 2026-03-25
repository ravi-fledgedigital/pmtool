#!/usr/bin/env python3
"""
magento_ssh_core.py  — shared SSH + log-reading core
Imported by magento_log_fetcher.py (staging) and magento_log_fetcher_production.py (production).
All three files must live in the same directory.
"""

import base64
import json
import subprocess
import sys
import textwrap
import threading
import time
from http.server import BaseHTTPRequestHandler, HTTPServer
from pathlib import Path

# ---------------------------------------------------------------------------
# Remote Python script — base64-injected so SSH quoting never corrupts it.
# Runs inside the Magento Cloud container.
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

# Hard timeout so SSH never hangs indefinitely
def _on_alarm(sig, frame):
    dbg("TIMEOUT hit — flushing partial results")
try:
    signal.signal(signal.SIGALRM, _on_alarm)
    signal.alarm(80)
except Exception:
    pass

dbg("START  LOG_DIR=" + LOG_DIR + "  DAYS=" + str(DAYS))

# Auto-discover var/log if path doesn't exist
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

        # Format A: Monolog JSON-per-line
        # {"message":"...","level":400,"level_name":"ERROR","channel":"...","datetime":"..."}
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

                # Attach context file/line to message
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
                pass  # fall through to Format B

        # Format B: standard [timestamp] channel.LEVEL: message
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

    # Collect gz candidates
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

    # Only read 3 most recent (rotation number 1 = newest)
    for gz_path in sorted(gz_candidates, key=rot_num)[:3]:
        read_gz(gz_path, log_name + "[" + os.path.basename(gz_path) + "]")

# Deduplicate
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


def fetch_once(host, ssh_port, key_path, log_dir, days, debug):
    """SSH in, run the embedded Python script, return list of log entry dicts."""
    b64 = base64.b64encode(REMOTE_PYTHON.encode()).decode()
    env = f'M_LOG_DIR="{log_dir}" M_DAYS="{days}" M_DEBUG="{"1" if debug else "0"}"'
    remote_cmd = f'{env} bash -c "echo {b64} | base64 -d | python3"'

    stdout, stderr, code = ssh_exec(host, ssh_port, key_path, remote_cmd)

    if debug and stderr.strip():
        for line in stderr.strip().splitlines():
            print(line, file=sys.stderr)

    if code != 0:
        print(f"[fetcher] SSH exit {code}", file=sys.stderr)
        if stderr.strip():
            print(f"[fetcher] stderr: {stderr[:300]}", file=sys.stderr)

    for line in reversed(stdout.strip().splitlines()):
        line = line.strip()
        if line.startswith("["):
            try:
                return json.loads(line)
            except json.JSONDecodeError:
                pass

    print(f"[fetcher] No JSON found. stdout={repr(stdout[:200])}", file=sys.stderr)
    return []


# ---------------------------------------------------------------------------
# Shared HTTP server + polling loop
# ---------------------------------------------------------------------------

def make_state():
    return {
        "entries": [], "last_fetch": None,
        "status": "starting", "error": "",
        "fetching": False, "fetch_count": 0,
    }


def poll_loop(state, lock, label, host, ssh_port, key_path, log_dir, days, debug, interval):
    while True:
        with lock:
            state["fetching"] = True
            state["status"]   = "fetching"
        print(f"[{label}] Fetching from {host} …", file=sys.stderr)
        t0 = time.time()
        try:
            entries = fetch_once(host, ssh_port, key_path, log_dir, days, debug)
            elapsed = time.time() - t0
            with lock:
                state["entries"]     = entries
                state["last_fetch"]  = time.strftime("%Y-%m-%dT%H:%M:%S")
                state["status"]      = "ok"
                state["error"]       = ""
                state["fetching"]    = False
                state["fetch_count"] += 1
            print(f"[{label}] OK — {len(entries)} entries in {elapsed:.1f}s "
                  f"(next in {interval}s)", file=sys.stderr)
        except Exception as e:
            with lock:
                state["status"]   = "error"
                state["error"]    = str(e)
                state["fetching"] = False
            print(f"[{label}] Error: {e}", file=sys.stderr)
        time.sleep(interval)


def make_handler(state, lock, codebase=''):
    class Handler(BaseHTTPRequestHandler):
        def log_message(self, fmt, *args): pass

        def _json(self, obj, status=200):
            body = json.dumps(obj).encode()
            self.send_response(status)
            self.send_header("Content-Type", "application/json")
            self.send_header("Content-Length", str(len(body)))
            self.send_header("Access-Control-Allow-Origin", "*")
            self.end_headers()
            self.wfile.write(body)

        def do_OPTIONS(self):
            self.send_response(204)
            self.send_header("Access-Control-Allow-Origin", "*")
            self.send_header("Access-Control-Allow-Methods", "GET, OPTIONS")
            self.end_headers()

        def do_GET(self):
            if self.path in ("/", "/status"):
                with lock:
                    snap = dict(state)
                self._json({
                    "status": snap["status"], "last_fetch": snap["last_fetch"],
                    "fetch_count": snap["fetch_count"], "error": snap["error"],
                    "count": len(snap["entries"]),
                })
            elif self.path == "/logs":
                with lock:
                    entries = list(state["entries"])
                    meta = {
                        "status": state["status"], "last_fetch": state["last_fetch"],
                        "error": state["error"], "fetching": state["fetching"],
                    }
                self._json({"meta": meta, "entries": entries})

            elif self.path == "/codebase-info":
                self._json({"codebase": codebase})

            elif self.path.startswith("/codefile?"):
                # Read a local codebase file and return its contents.
                # Called by the dashboard when building Claude context for a card.
                import urllib.parse, os
                qs = urllib.parse.parse_qs(self.path.split("?",1)[1])
                rel_path = qs.get("path", [""])[0]
                base     = qs.get("base", [""])[0] or codebase

                if not rel_path or not base:
                    self._json({"error": "missing path or base"}, 400)
                    return

                # Sanitise — only allow reads under the declared base
                full = os.path.normpath(os.path.join(base, rel_path.lstrip("/")))
                if not full.startswith(os.path.normpath(base)):
                    self._json({"error": "path traversal denied"}, 403)
                    return

                if not os.path.isfile(full):
                    self._json({"exists": False, "content": ""})
                    return

                try:
                    with open(full, "r", errors="replace") as f:
                        content_str = f.read(8000)  # cap at 8KB per file
                    self._json({"exists": True, "path": full, "content": content_str})
                except Exception as e:
                    self._json({"error": str(e)}, 500)

            else:
                self._json({"error": "not found"}, 404)
    return Handler


def run_server(label, http_port, state, lock, host, ssh_port, key_path,
               log_dir, days, debug, interval, codebase=""):
    print(f"[{label}] SSH host  : {host}", file=sys.stderr)
    print(f"[{label}] Log dir   : {log_dir}", file=sys.stderr)
    print(f"[{label}] Days back : {days}", file=sys.stderr)
    print(f"[{label}] Interval  : {interval}s", file=sys.stderr)
    print(f"[{label}] Dashboard : http://localhost:{http_port}/logs", file=sys.stderr)

    threading.Thread(
        target=poll_loop,
        args=(state, lock, label, host, ssh_port, key_path,
              log_dir, days, debug, interval),
        daemon=True,
    ).start()

    class Server(HTTPServer):
        allow_reuse_address = True

    server = Server(("0.0.0.0", http_port), make_handler(state, lock, codebase))
    print(f"[{label}] HTTP server listening on :{http_port}", file=sys.stderr)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print(f"\n[{label}] Stopped.", file=sys.stderr)
