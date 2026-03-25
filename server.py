#!/usr/bin/env python3
"""
Simple HTTP server to serve the Magento Log Dashboard HTML on port 5000.
The dashboard JS connects to the backend log fetchers on ports 7755 (staging)
and 7756 (production) directly from the browser.
"""
from http.server import BaseHTTPRequestHandler, HTTPServer
from pathlib import Path

DASHBOARD_FILE = Path(__file__).parent / "magento_log_dashboard.html"
PORT = 5000
HOST = "0.0.0.0"


class Handler(BaseHTTPRequestHandler):
    def log_message(self, fmt, *args):
        pass

    def do_GET(self):
        if self.path in ("/", "/index.html"):
            content = DASHBOARD_FILE.read_bytes()
            self.send_response(200)
            self.send_header("Content-Type", "text/html; charset=utf-8")
            self.send_header("Content-Length", str(len(content)))
            self.end_headers()
            self.wfile.write(content)
        else:
            self.send_response(404)
            self.end_headers()


class Server(HTTPServer):
    allow_reuse_address = True


if __name__ == "__main__":
    server = Server((HOST, PORT), Handler)
    print(f"Dashboard available at http://{HOST}:{PORT}", flush=True)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\nStopped.", flush=True)
