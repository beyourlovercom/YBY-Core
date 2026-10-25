import json,urllib.request,urllib.error,http.cookiejar
root='http://127.0.0.1:18973/?rest_route='
h={};client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def rq(method,path,data=None):
 b=json.dumps(data).encode() if data is not None else None
 r=urllib.request.Request(root+path,data=b,method=method,headers={**h,'Content-Type':'application/json'})
 try:
  with client.open(r,timeout=25) as result:
   for k in ['Nonce','Cart-Token']:
    if result.headers.get(k):h[k]=result.headers[k]
   return json.load(result)
 except urllib.error.HTTPError as e:
  print('ERROR',e.code,e.read().decode()[:500]);raise
def summary(d):
 return {'items':[(x['id'],x['quantity']) for x in d.get('items',[])],'total':d.get('totals',{}).get('total_price'),'coupon':[(x['code'],x['totals']['total_discount']) for x in d.get('coupons',[])]}
print('INIT',summary(rq('GET','/wc/store/v1/cart')))
for id,qty in [(27,2),(28,1),(29,1)]:
 d=rq('POST','/wc/store/v1/cart/add-item',{'id':id,'quantity':qty})
 print('ADD',id,summary(d))
