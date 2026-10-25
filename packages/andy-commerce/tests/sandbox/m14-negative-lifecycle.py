import json,urllib.request,urllib.error,http.cookiejar,os
case=os.environ['AICC_M14_CASE']
root='http://127.0.0.1:18973/?rest_route='
h={};o=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def send(method,p,data=None):
 b=json.dumps(data).encode() if data is not None else None
 req=urllib.request.Request(root+p,data=b,method=method,headers={**h,'Content-Type':'application/json'})
 try:
  with o.open(req,timeout=22) as r:
   for k in ['Nonce','Cart-Token']:
    if r.headers.get(k):h[k]=r.headers[k]
   return json.load(r)
 except urllib.error.HTTPError as e:
  print('HTTP',e.code,e.read().decode()[:400]);raise
def discount(d): return sum(int(x['totals']['total_discount']) for x in d.get('coupons',[]))
def ids(d):return [x['id'] for x in d['items']]
def find(d,id):return next(x for x in d['items'] if x['id']==id)
send('GET','/wc/store/v1/cart')
a=send('POST','/wc/store/v1/cart/add-item',{'id':27,'quantity':2})
b=send('POST','/wc/store/v1/cart/add-item',{'id':28,'quantity':1})
c=send('POST','/wc/store/v1/cart/add-item',{'id':29,'quantity':1})
if case=='tier':
 assert [discount(a),discount(b),discount(c)]==[800,1250,3700]
 d=send('POST','/wc/store/v1/cart/remove-item',{'key':find(c,29)['key']})
 assert discount(d)==1250,(discount(d),ids(d))
 e=send('POST','/wc/store/v1/cart/remove-item',{'key':find(d,28)['key']})
 assert discount(e)==800,discount(e)
 print('TIER_DYNAMIC_REMOVAL_PASS',json.dumps({'after4':3700,'after3':1250,'after2':800}))
elif case=='gift':
 assert 30 not in ids(a)
 assert ids(b).count(30)==1 and ids(c).count(30)==1,ids(c)
 d=send('POST','/wc/store/v1/cart/remove-item',{'key':find(c,28)['key']})
 # subtotal 140 still qualifies, so remove product 29 as well.
 assert ids(d).count(30)==1,ids(d)
 e=send('POST','/wc/store/v1/cart/remove-item',{'key':find(d,29)['key']})
 assert 30 not in ids(e),ids(e)
 print('GIFT_THRESHOLD_REVOKE_PASS',json.dumps({'before':ids(c),'after':ids(e)}))
else:
 raise RuntimeError('unsupported case')
