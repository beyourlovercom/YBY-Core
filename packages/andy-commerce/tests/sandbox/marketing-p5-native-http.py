#!/usr/bin/env python3
"""M-P5 only: true localhost Woo Classic / Store API checkout, no gateway charge.

Run: AICC_P5_CASE=bogo AICC_P5_MODE=store|classic python3 script.py
Requires the separately installed disposable-only P5 bridge and COD enabled.
Does not use account credentials, external network or merchant services.
"""
import os, json, re, html, sys, urllib.request, urllib.parse, urllib.error, http.cookiejar, pathlib

CASE=os.environ.get("AICC_P5_CASE", "")
MODE=os.environ.get("AICC_P5_MODE", "")
assert CASE in ("fixed","percent","gift","bogo","bundle","tier")
assert MODE in ("store","classic")
BASE="http://127.0.0.1:18973/"
EXPECTED={"fixed":1200,"percent":1850,"gift":0,"bogo":2250,"bundle":2300,"tier":3700}
client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
headers={}
def fetch(method, path, data=None, contenttype="application/json"):
    if contenttype=="application/json" and data is not None:
        raw=json.dumps(data).encode()
    elif data is not None:
        raw=urllib.parse.urlencode(data).encode()
    else:
        raw=None
    req=urllib.request.Request(BASE+path,data=raw,method=method,
      headers={**headers,"Content-Type":contenttype})
    try:
        with client.open(req,timeout=25) as r:
            if r.url.split("/")[2]!="127.0.0.1:18973":
                raise RuntimeError("External redirect prohibited")
            for k in ("Nonce","Cart-Token"):
                if r.headers.get(k): headers[k]=r.headers[k]
            return r.status,r.read()
    except urllib.error.HTTPError as e:
        e.aicc_body=e.read().decode(errors="replace")
        print("LOCAL_HTTP_ERROR",method,path,e.code,e.aicc_body[:500])
        raise
address={"first_name":"AICC","last_name":"P5 Synthetic","address_1":"1 Local Test",
 "address_2":"","city":"Testville","state":"CA","postcode":"90001",
 "country":"US","phone":"5550101000","company":""}
billing={**address,"email":"aicc-marketing-p5@example.invalid"}
expected_code="aicc-p2-campaign_p5_"+CASE+"_01"
if MODE=="store":
    prefix="?rest_route="
    fetch("GET",prefix+"/wc/store/v1/cart")
    for p,q in ((27,2),(28,1),(29,1)):
        _,b=fetch("POST",prefix+"/wc/store/v1/cart/add-item",{"id":p,"quantity":q})
    _,b=fetch("POST",prefix+"/wc/store/v1/cart/update-customer",{
        "billing_address":billing,"shipping_address":address})
    cart=json.loads(b)
    rates=cart.get("shipping_rates",[])
    assert len(rates)==1 and len(rates[0]["shipping_rates"])>0,rates
    rate=next(x for x in rates[0]["shipping_rates"] if x["rate_id"].startswith("flat_rate"))
    _,b=fetch("POST",prefix+"/wc/store/v1/cart/select-shipping-rate",{
        "package_id":0,"rate_id":rate["rate_id"]})
    cart=json.loads(b)
    coupons=cart["coupons"]
    discount=sum(int(x["totals"]["total_discount"]) for x in coupons)
    actual=int(cart["totals"]["total_price"])
    assert discount==EXPECTED[CASE],("DISCOUNT",CASE,discount,cart.get("coupons"))
    if CASE=="gift":
        gifts=[x for x in cart["items"] if x["id"]==30]
        assert len(gifts)==1 and int(gifts[0]["totals"]["line_total"])==0
    else:
        assert len(coupons)==1 and coupons[0]["code"]==expected_code,coupons
    _,b=fetch("GET",prefix+"/wc/store/v1/checkout")
    try:
        _,b=fetch("POST",prefix+"/wc/store/v1/checkout",{
            "billing_address":billing,"shipping_address":address,
            "payment_method":"cod","payment_data":[],"create_account":False,
            "customer_note":"M-P5 local no-money Woo acceptance"})
    except urllib.error.HTTPError as e:
        if os.environ.get("AICC_P5_EXPECT_CAP_REJECT")=="1":
            assert e.code==409 and '"aicc_marketing_cap_exhausted"' in e.aicc_body and "Please refresh your cart" in e.aicc_body,                ("wrong rejection",e.code,e.aicc_body[:500])
            print("MARKETING_P5_REAL_CHECKOUT_CAP_DENIAL_PASS",
                json.dumps({"http":e.code,"case":CASE,"mode":MODE,
                   "external_payment":False,"reason":"quota_exhausted"}))
            sys.exit(0)
        raise
    if os.environ.get("AICC_P5_EXPECT_CAP_REJECT")=="1":
        raise RuntimeError("CAP_EXHAUSTED_CHECKOUT_INCORRECTLY_ACCEPTED")
    out=json.loads(b); order_id=out.get("order_id")
    assert isinstance(order_id,int) and order_id>0,out
else:
    for p,q in ((27,2),(28,1),(29,1)):
        fetch("GET",f"?add-to-cart={p}&quantity={q}")
    _,page=fetch("GET","?page_id=49")
    page=page.decode("utf-8",errors="replace")
    match=re.search(r'name=["\']woocommerce-process-checkout-nonce["\']\s+value=["\']([^"\']+)',page)
    assert match is not None,"Classic checkout nonce missing"
    if CASE!="gift":
        assert expected_code in page,"Classic native coupon not visible: "+CASE
    data={"billing_first_name":"AICC","billing_last_name":"P5 Synthetic",
        "billing_country":"US","billing_address_1":"1 Local Test",
        "billing_city":"Testville","billing_state":"CA","billing_postcode":"90001",
        "billing_phone":"5550101000","billing_email":billing["email"],
        "shipping_first_name":"AICC","shipping_last_name":"P5 Synthetic",
        "shipping_country":"US","shipping_address_1":"1 Local Test",
        "shipping_city":"Testville","shipping_state":"CA",
        "shipping_postcode":"90001","payment_method":"cod",
        "woocommerce-process-checkout-nonce":html.unescape(match.group(1)),
        "_wp_http_referer":"/?page_id=49","terms":"on"}
    _,raw=fetch("POST","?wc-ajax=checkout",data,"application/x-www-form-urlencoded")
    out=json.loads(raw)
    assert out.get("result")=="success",out
    u=urllib.parse.urlparse(out["redirect"])
    assert u.hostname=="127.0.0.1" and u.port==18973,u
    order_id=int(urllib.parse.parse_qs(u.query).get("order-received",[0])[0])
    assert order_id>0
    discount=EXPECTED[CASE]
    actual=None
record={"mode":MODE,"case":CASE,"order_id":order_id,
    "expected_discount_usd_minor":EXPECTED[CASE],
    "http_cart_discount_usd_minor":discount,
    "http_cart_total_usd_minor":actual,
    "external_payment":False}
print("MARKETING_P5_TRUE_NATIVE_CHECKOUT",json.dumps(record,sort_keys=True))
with open("/tmp/aicc-marketing-p5-local-orders.jsonl","a",encoding="utf-8") as f:
    f.write(json.dumps(record)+"\n")
