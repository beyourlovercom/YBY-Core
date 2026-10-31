#!/usr/bin/env python3
"""One-shot unauthenticated Dev currency UAT. Never touches checkout/orders.

Exactly one anonymous guest cookie jar, only dev.beyourlover.com,
GET Home/Cart/Store API product/cart, POST currency selection only.
"""
import http.cookiejar
import json
import re
import ssl
import urllib.parse
import urllib.request

BASE = "https://dev.beyourlover.com"
USER_AGENT = "AICC-Currency-Dev-UAT/1.0 (read-only Store API; guest currency form)"
jar = http.cookiejar.CookieJar()
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
client.addheaders = [("User-Agent", USER_AGENT), ("Accept", "text/html,application/json")]

def request(path, data=None):
    url = BASE + path
    if not url.startswith(BASE + "/"):
        raise RuntimeError("Non-Dev URL forbidden")
    req = urllib.request.Request(url, data=data)
    with client.open(req, timeout=18) as response:
        effective = urllib.parse.urlsplit(response.geturl())
        if effective.hostname != "dev.beyourlover.com" or effective.scheme != "https":
            raise RuntimeError("Unexpected guest redirect outside Dev")
        if response.status != 200:
            raise RuntimeError(f"Dev response not 200: {response.status}")
        return response.read(750000).decode("utf-8", errors="replace")

def assert_selected(page, code):
    # Check exactly the server-rendered Currency Runtime shortcode's selector.
    forms = re.findall(r'<form\b[^>]*class="[^"]*ac-currency-runtime-dev[^"]*"[^>]*>.*?</form>',page,re.S)
    if not forms:
        raise AssertionError("Currency shortcode missing from main Footer")
    selected = re.search(r'<option\b(?=[^>]*value="([A-Z]{3})")(?=[^>]*selected)[^>]*>',forms[-1],re.S)
    if not selected or selected.group(1) != code:
        raise AssertionError(f"Currency shortcode selection did not persist: requested {code}")

def change_currency(code):
    page=request("/")
    m=re.search(r'name="ac_currency_nonce"\s+value="([^"]+)"',page)
    if not m:
        raise AssertionError("Dev currency selector nonce not found")
    payload=urllib.parse.urlencode({
        "ac_currency_action":"select",
        "ac_currency_nonce":m.group(1),
        "ac_currency_code":code,
    }).encode("ascii")
    page=request("/",payload)
    assert_selected(page,code)
    assert_selected(request("/"),code)
    assert code in {"USD","EUR","GBP","AUD","CAD"}
    cart=json.loads(request("/wp-json/wc/store/v1/cart"))
    if cart.get("totals",{}).get("currency_code") != code:
        raise AssertionError(f"Guest Woo Store API cart currency mismatch for {code}")
    # Read-only product route uses identical WC currency context to storefront.
    products=json.loads(request("/wp-json/wc/store/v1/products?per_page=1"))
    if products and products[0].get("prices",{}).get("currency_code") != code:
        raise AssertionError(f"Guest Woo Store API product currency mismatch for {code}")
    print(f"ANON_DEV_GUEST_{code}_FOOTER_SESSION_STORE_API_PASS items={len(cart.get('items',[]))}")

def main():
    page=request("/")
    assert_selected(page,"USD")
    print("ANON_DEV_GUEST_DEFAULT_USD_PASS")
    change_currency("EUR")
    # The independent Cart Footer may not contain the selector, but must not
    # reset the shopper currency when navigating from the main Footer.
    request("/cart/")
    c=json.loads(request("/wp-json/wc/store/v1/cart"))
    if c.get("totals",{}).get("currency_code")!="EUR":
        raise AssertionError("Cross-page Cart reset EUR session")
    print("ANON_DEV_GUEST_EUR_HOME_CART_STORE_API_PASS")
    change_currency("GBP")
    print("ANON_DEV_GUEST_CURRENCY_NO_ORDERS_NO_PAYMENTS_PASS")

if __name__ == "__main__":
    main()
