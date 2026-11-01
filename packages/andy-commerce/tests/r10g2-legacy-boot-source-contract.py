from pathlib import Path
base=Path(__file__).resolve().parents[1]
s=(base/'includes/class-andy-commerce.php').read_text(encoding='utf-8')
entry=(base/'andy-commerce.php').read_text(encoding='utf-8')
early=entry.index('Andy_Commerce_Observation_Mode::is_observing()')
assert early<entry.index("class-andy-commerce-shipping-promotion-runtime.php")
assert early<entry.index("class-andy-commerce-r10c-transaction-compatibility.php")
rules={
 'currency':['Andy_Commerce_Currency_V2_Dev::boot()', 'Andy_Commerce_Currency_R13_Dev_Canary::boot()', 'Andy_Commerce_Currency_Runtime_C1::boot()', 'Andy_Commerce_Currency_Runtime_Dev_V1::admin_boot()', 'Andy_Commerce_Currency_Runtime_Dev_V1::boot()'],
 'marketing':['Andy_Commerce_Marketing_P2_Woo_Adapter::boot()', 'Andy_Commerce_Marketing_P5_Checkout_Hooks::boot()', 'Andy_Commerce_M7_Draft_Studio::boot()', 'Andy_Commerce_Marketing_Dev_UAT::boot()'],
 'shipping':['Andy_Commerce_Shipping_Promotion_Policy::boot()', 'Andy_Commerce_Shipping_Promotion_Runtime::boot()', 'Andy_Commerce_Shipping_Promotion_Runtime::boot_default_shipping_preference()']
}
ranges={}
for mod in ['currency','marketing','shipping']:
 finder="Andy_Commerce_R10G2_Legacy_Runtime_Quarantine::allowed( '"+mod+"' )"
 pos=0;blocks=[]
 while True:
  i=s.find(finder,pos)
  if i<0:break
  start=s.find('{',i)
  assert start>=0
  level=1;j=start+1
  while level and j<len(s):
   if s[j]=='{':level+=1
   if s[j]=='}':level-=1
   j+=1
  assert level==0
  blocks.append((i,j,s[start:j]))
  pos=j
 ranges[mod]=blocks
 assert blocks,mod
for mod,calls in rules.items():
 for call in calls:
  occurrences=[];i=0
  while True:
   x=s.find(call,i)
   if x<0:break
   occurrences.append(x);i=x+len(call)
  assert occurrences,(mod,call,'missing')
  for x in occurrences:
   assert any(start<=x<end for start,end,_ in ranges[mod]),(mod,call,'unguarded')
  print('R10G2_BOOT_QUARANTINE_'+mod.upper()+'_'+call.split('::')[-1].upper()+'=PASS')
# Verify that the new allowlist does NOT control the separate R10-C modules.
r10=(base/'includes/class-andy-commerce-r10c-transaction-compatibility.php').read_text(encoding='utf-8')
assert 'andy_commerce_r10c_shipping_enabled_v1' in r10
assert 'andy_commerce_r10c_coupon_enabled_v1' in r10
assert 'ANDY_COMMERCE_R10G2' not in r10
assert 'ANDY_COMMERCE_R10G2' not in entry
print('R10G2_R10C_MODULE_INDEPENDENCE=PASS')
print('R10G2_EARLY_OBSERVE_PRIORITY=PASS')
print('R10G2_UNGUARDED_BOOT_COUNT=0')
