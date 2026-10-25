import os,json,urllib.request,urllib.error,http.cookiejar,pathlib
case=os.environ['AICC_M14_CASE']
expected={'fixed':(1200,19764),'percent':(1850,19062),'bogo':(2250,18630),'bundle':(2300,18576),'tier':(3700,17064),'gift':(0,21060)}
if case not in expected: raise SystemExit('Unknown local case')
root='http://127.0.0.1:18973/?rest_route='
h={};opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def req(method,path,data=None):
 b=json.dumps(data).encode() if data is not None else None
 r=urllib.request.Request(root+path,method=method,data=b,headers={**h,'Content-Type':'application/json'})
 try:
  with opener.open(r,timeout=20) as response:
   for k in ['Nonce','Cart-Token']:
    if response.headers.get(k):h[k]=response.headers[k]
   return json.load(response)
 except urllib.error.HTTPError as exc:
  print('HTTP_FAIL',method,path,exc.code,exc.read().decode()[:420]);raise
req('GET','/wc/store/v1/cart')
for id,qty in [(27,2),(28,1),(29,1)]:
 cart=req('POST','/wc/store/v1/cart/add-item',{'id':id,'quantity':qty})
address={'first_name':'AICC','last_name':'M14 Local','address_1':'1 Sandbox Street','address_2':'','city':'Testville','state':'CA','postcode':'90001','country':'US','phone':'5550101000','company':''}
billing={**address,'email':'aicc-m14@example.invalid'}
cart=req('POST','/wc/store/v1/cart/update-customer',{'billing_address':billing,'shipping_address':address})
rates=cart.get('shipping_rates',[])
if not rates or not rates[0].get('shipping_rates'):raise RuntimeError('No verified shipping rate')
rate=next(x for x in rates[0]['shipping_rates'] if x['rate_id'].startswith('flat_rate'))
cart=req('POST','/wc/store/v1/cart/select-shipping-rate',{'package_id':0,'rate_id':rate['rate_id']})
actual=int(cart['totals']['total_price'])
disc=sum(int(x['totals']['total_discount']) for x in cart['coupons'])
ids=[x['id'] for x in cart['items']]
if (disc,actual)!=expected[case]: raise RuntimeError(f'Auto promo mismatch {case} {(disc,actual)} expected={expected[case]}')
if case=='gift':
 assert ids.count(30)==1,ids
 assert int(next(x for x in cart['items'] if x['id']==30)['totals']['line_total'])==0
else:
 assert ids.count(30)==0
 assert len(cart['coupons'])==1
req('GET','/wc/store/v1/checkout')
out=req('POST','/wc/store/v1/checkout',{'billing_address':billing,'shipping_address':address,'payment_method':'cod','payment_data':[],'create_account':False,'customer_note':'M14 disposable localhost only; no external payment'})
oid=out.get('order_id')
if not isinstance(oid,int) or oid<1: raise RuntimeError('No native Woo checkout order')
result={'mode':'LOCAL_WOO_M14_AUTOMATIC_HOOK_PROOF','case':case,'order_id':oid,'discount_cents':disc,'cart_total_cents':actual,'cart_item_ids':ids,'shipping_rate':rate['rate_id'],'external_payment':False}
print(json.dumps(result,sort_keys=True))
p=pathlib.Path.home()/'AI/uat/andy-commerce-p5p6-20261023/logs'
(p/f'm14-http-{case}.json').write_text(json.dumps(result,indent=2))
