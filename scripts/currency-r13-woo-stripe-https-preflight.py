#!/usr/bin/env python3
"""R13 independent Woo HTTPS callback *preflight*, read-only.

Usage:
  python3 scripts/currency-r13-woo-stripe-https-preflight.py \
    --origin https://EXPLICIT_INDEPENDENT_TEST_WOO_HOST

This checks public TLS and Woo routes, NOT webhook signing/delivery.
No secret, no Stripe API call, no orders and no Woo mutations.
"""
import argparse
import http.client
import ipaddress
import json
import socket
import ssl
import sys
from urllib.parse import urlsplit

BLOCKED_SUFFIXES = (
    ".beyourlover.com", ".ybybottle.com", ".ybyirrigation.com",
    ".pairdoll.com", ".centralxi.com", ".bcglassbottles.com",
)
BLOCKED_EXACT = {"beyourlover.com", "ybybottle.com", "ybyirrigation.com",
                 "pairdoll.com", "centralxi.com", "bcglassbottles.com"}

def fail(message: str) -> None:
    raise RuntimeError(message)

def main() -> None:
    cli = argparse.ArgumentParser()
    cli.add_argument("--origin", required=True, help="dedicated public HTTPS Woo TEST hostname")
    args = cli.parse_args()
    raw = urlsplit(args.origin)
    host = (raw.hostname or "").lower()
    if (raw.scheme != "https" or not host
            or raw.path not in ("", "/") or raw.username or raw.password
            or raw.query or raw.fragment or raw.port not in (None, 443)
            or host in BLOCKED_EXACT
            or any(host.endswith(s) for s in BLOCKED_SUFFIXES)):
        fail("Dedicated externally reachable HTTPS test domain required; never Dev/WWW")
    ips = {s[4][0] for s in socket.getaddrinfo(host, 443, type=socket.SOCK_STREAM)}
    if not ips or any(not ipaddress.ip_address(i).is_global for i in ips):
        fail("TLS target cannot resolve exclusively to public addresses")
    # Strict CA + hostname verification. No --insecure fallback.
    ctx = ssl.create_default_context()
    conn = http.client.HTTPSConnection(host, 443, timeout=12, context=ctx)
    try:
        conn.request("GET", "/wp-json/wc/store/v1/cart",
                     headers={"User-Agent": "AICC-R13-Sandbox-Preflight/1.0",
                              "Accept": "application/json",
                              "Cache-Control": "no-store"})
        resp = conn.getresponse()
        body = resp.read(300000)
        if resp.status != 200:
            fail("Guest Woo Store API failed with status " + str(resp.status))
        data = json.loads(body)
        if not isinstance(data, dict) or not isinstance(data.get("totals"), dict):
            fail("Target is not a functioning native Woo Store API")
        tls_version = conn.sock.version() if conn.sock else "connection-closed"
        print("R13_PUBLIC_HTTPS_CA_HOSTNAME_WOO_STORE_API_PASS")
        print(json.dumps({"host": host, "resolved_public_addresses": len(ips),
                          "woo_store_api_http_status": 200,
                          "tls": tls_version,
                          "stripe_webhook_path": "/?wc-api=wc_stripe",
                          "signed_webhook_verified": False,
                          "test_gateway_connected_verified": False,
                          "production_site_untouched": True}, indent=2))
    finally:
        conn.close()

if __name__ == "__main__":
    try:
        main()
    except Exception as err:
        print("R13_PUBLIC_HTTPS_PREFLIGHT_FAIL: " + str(err), file=sys.stderr)
        sys.exit(1)
