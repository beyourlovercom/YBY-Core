import json,urllib.request,urllib.parse,urllib.error,http.cookiejar,re,html,pathlib,os
base='http://127.0.0.1:18973/'
case=os.environ['AICC_M14_CASE']
expected={'fixed':1200,'percent':1850,'bogo':2250,'bundle':2300,'tier':3700,'gift':0}
if case not in expected:raise RuntimeError('UNKNOWN_CASE')
jar=http.cookiejar.CookieJar()
o=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
def req(method,path,body=None,head=None):
 r=urllib.request.Request(base+path,data=body,method=method,headers=head or {})
 with o.open(r,timeout=30) as x:return x.status,x.url,x.headers,x.read()
for id,qty in [(27,2),(28,1),(29,1)]:
 status,u,h,b=req('GET',f'?add-to-cart={id}&quantity={qty}')
 print('CLASSIC_ADD',id,status,u.split('/')[-1],len(jar))
status,u,h,b=req('GET','?page_id=49')
page=b.decode('utf-8',errors='replace')
print('CLASSIC_PAGE',status,u,len(page),'form.woocommerce-checkout' in page,'name="woocommerce-process-checkout-nonce"' in page,'aicc-m14-only-fixed' in page,'cookies',len(jar))
match=re.search(r'name=["\']woocommerce-process-checkout-nonce["\']\s+value=["\']([^"\']+)',page)
if not match:
 raise RuntimeError('Classic nonce / form not found')
if case != 'gift' and ('aicc-m14-only-'+case) not in page:
 raise RuntimeError('Classic checkout does not show its M14 automatic coupon: '+case)
if case=='gift' and 'AICC M13 local Gift' not in page:
 raise RuntimeError('Classic gift not present')
nonce=html.unescape(match.group(1))
fields={'billing_first_name':'AICC','billing_last_name':'Classic','billing_company':'','billing_country':'US','billing_address_1':'1 Local Test',
'billing_address_2':'','billing_city':'Testville','billing_state':'CA','billing_postcode':'90001','billing_phone':'5550101000',
'billing_email':'aicc-classic@example.invalid','shipping_first_name':'AICC','shipping_last_name':'Classic',
'shipping_country':'US','shipping_address_1':'1 Local Test','shipping_city':'Testville','shipping_state':'CA','shipping_postcode':'90001',
'payment_method':'cod','woocommerce-process-checkout-nonce':nonce,'_wp_http_referer':'/?page_id=49','terms':'on'}
status,u,h,data=req('POST','?wc-ajax=checkout',urllib.parse.urlencode(fields).encode(),{'Content-Type':'application/x-www-form-urlencoded'})
try:j=json.loads(data)
except:print('HTML_RESULT',data[:450]);raise
if j.get('result')!='success':raise RuntimeError('Classic native checkout did not succeed')
destination=urllib.parse.urlparse(j.get('redirect',''))
order_id=int(urllib.parse.parse_qs(destination.query).get('order-received',[0])[0])
if destination.hostname!='127.0.0.1' or destination.port!=18973 or order_id<=0:
 raise RuntimeError('Classic result attempted to leave isolated localhost')
proof={'http':status,'result':j['result'],'local_sandbox_order_id':order_id,'automatic_coupon':'aicc-m14-only-'+case if case!='gift' else 'zero-price-stock-gift','external_payment':False}
print('CLASSIC_SUBMIT',json.dumps(proof))
path=pathlib.Path.home()/('AI/uat/andy-commerce-p5p6-20261023/logs/m14-classic-'+case+'.json')
path.write_text(json.dumps(proof,indent=2))
