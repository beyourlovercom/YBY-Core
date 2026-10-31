import json,urllib.request,urllib.error,http.cookiejar
root='http://127.0.0.1:18973/?rest_route='
h={};o=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def run(method,p,data=None):
 req=urllib.request.Request(root+p,data=json.dumps(data).encode() if data is not None else None,method=method,headers={**h,'Content-Type':'application/json'})
 try:
  with o.open(req,timeout=20) as r:
   for k in ('Nonce','Cart-Token'):
    if r.headers.get(k):h[k]=r.headers[k]
   return {'code':r.status,'data':json.load(r)}
 except urllib.error.HTTPError as e:
  return {'code':e.code,'data':json.loads(e.read().decode())}
run('GET','/wc/store/v1/cart')
run('POST','/wc/store/v1/cart/add-item',{'id':27,'quantity':2})
res=run('POST','/wc/store/v1/cart/apply-coupon',{'code':'aicc-p5-only-25'})
cart=run('GET','/wc/store/v1/cart')
print(json.dumps({'apply_http':res['code'],'apply_code':res['data'].get('code'),'apply_message':res['data'].get('message'),'after_coupon_codes':[x['code'] for x in cart['data'].get('coupons',[])],'after_discount':sum(int(x['totals']['total_discount']) for x in cart['data'].get('coupons',[]))}))
