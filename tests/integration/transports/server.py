import json
import ssl
import threading
import time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer


class Handler(BaseHTTPRequestHandler):
    protocol_version = "HTTP/1.1"

    def log_message(self, *args):
        pass

    def do_GET(self):
        self.send_response(200)
        self.send_header("Content-Length", "0")
        self.end_headers()

    def do_POST(self):
        content = self.rfile.read(int(self.headers.get("Content-Length", "0")))
        status = 200
        body = json.dumps({
            "method": self.command,
            "content": content.decode("utf-8"),
            "content_type": self.headers.get("Content-Type"),
            "content_length": self.headers.get("Content-Length"),
            "user_agent": self.headers.get("User-Agent"),
            "client_certificate": bool(self.connection.getpeercert())
            if isinstance(self.connection, ssl.SSLSocket) else False,
        }).encode()
        if self.path == "/interim":
            self.wfile.write(b"HTTP/1.1 100 Continue\r\nContent-Type: interim/type\r\n\r\n")
        if self.path == "/status/503":
            status, body = 503, b"<Error/>"
        elif self.path == "/redirect":
            status, body = 302, b"<Redirect/>"
        elif self.path == "/empty":
            status, body = 204, b""
        elif self.path in ("/truncated", "/timeout", "/chunked"):
            body = b"<Response/>"
        self.send_response(status)
        self.send_header("Content-Type", "application/json" if body.startswith(b"{") else "application/xml")
        self.send_header("Connection", "close")
        if self.path == "/redirect":
            self.send_header("Location", "https://server:8443/echo")
        if self.path == "/chunked":
            self.send_header("Transfer-Encoding", "chunked")
        else:
            self.send_header("Content-Length", "100" if self.path == "/truncated" else str(len(body)))
        self.end_headers()
        if self.path == "/chunked":
            self.wfile.write(b"b\r\n<Response/>\r\n0\r\n\r\n")
        elif self.path == "/timeout":
            self.wfile.write(body[:1])
            self.wfile.flush()
            time.sleep(2)
        else:
            self.wfile.write(body)
        self.close_connection = True


servers = []
for port in (8080, 8443, 8444):
    server = ThreadingHTTPServer(("0.0.0.0", port), Handler)
    if port != 8080:
        context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
        context.load_cert_chain("/certs/server.crt", "/certs/server.key")
        if port == 8444:
            context.verify_mode = ssl.CERT_REQUIRED
            context.load_verify_locations("/certs/ca.crt")
        server.socket = context.wrap_socket(server.socket, server_side=True)
    servers.append(server)
    threading.Thread(target=server.serve_forever, daemon=True).start()
threading.Event().wait()
