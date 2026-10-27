/* Woo Classic bridge state machine — no Woo orders, sessions or network. */
const assert=require('node:assert/strict'), fs=require('node:fs'), path=require('node:path'), vm=require('node:vm');
const src=fs.readFileSync(path.resolve(__dirname,'../public/assets/js/andy-core-notification-woo-classic.js'),'utf8');
function setup(ready=true){
 const listeners={}, notices=[], body={classList:{contains:s=>s==='woocommerce-checkout'},isConnected:true};
 const make=(id,txt,opts={})=>({
   id,textContent:txt,hidden:false,isConnected:true,attrs:{},style:{
     current:'',priority:'',
     getPropertyValue:function(n){return n==='display'?this.current:'';},
     getPropertyPriority:function(){return this.priority;},
     setProperty:function(n,v,p=''){if(n==='display'){this.current=v;this.priority=p;}},
     removeProperty:function(n){if(n==='display')this.current='';}
   },
   matches:s=>s.includes('woocommerce-message')&&!opts.error,
   closest:s=>opts.error?true:null,querySelector:s=>opts.action?{}:null,
   getAttribute:function(n){return this.attrs[n]??null;},
   setAttribute:function(n,v){this.attrs[n]=String(v);},
   removeAttribute:function(n){delete this.attrs[n];},
   hasAttribute:function(n){return n in this.attrs;}
 });
 const a=make('a','Coupon applied successfully.');
 const b=make('b','Coupon applied successfully.');
 const err=make('e','Payment failed.',{error:true});
 const link=make('l','Added to cart.',{action:true});
 notices.push(a,b,err,link);
 const doc={body,readyState:'complete',querySelectorAll:s=>notices.slice()};
 let current=null,n=0;
 const api={isReady:()=>ready,notify:p=>{current={id:++n,type:p.type,key:p.key};return {id:n};},getState:()=>current};
 const win={
   AndyCoreNotificationUI:api,location:{pathname:'/checkout'},
   getComputedStyle:node=>({display:node.style.current||'block',visibility:'visible'}),
   setTimeout:()=>1,clearTimeout:()=>{},
   addEventListener:(n,cb)=>{listeners[n]=cb;},
   removeEventListener:(n)=>{delete listeners[n];},
   MutationObserver:class {constructor(cb){this.cb=cb;}observe(){}disconnect(){}}
 };
 vm.runInNewContext(src,{window:win,document:doc,Map,String,Object,Array,Number});
 return {api,win,notices,make,listeners};
}
const x=setup(true);
assert.equal(x.notices[0].hidden,true);
assert.equal(x.notices[1].hidden,true);
assert.equal(x.notices[2].hidden,false,'payment error remains native');
assert.equal(x.notices[3].hidden,false,'actionable notice stays native');
assert.equal(x.win.AndyCoreWooNoticeBridge.stats().duplicates,1);
assert.equal(x.api.getState().type,'success');
const newer=x.make('new','Coupon B applied successfully.');
x.notices.push(newer);
x.win.AndyCoreWooNoticeBridge.scan();
assert.equal(newer.hidden,true,'new notice safely handed over');
assert.ok(x.api.getState().key.includes('coupon b'));
x.listeners.error();
for(const n of x.notices)assert.equal(n.hidden,false,'restore on fatal error');
assert.equal(x.win.AndyCoreWooNoticeBridge.stats().suppressed,0);
const before=x.api.getState().key;
const later=x.make('late','This should remain visible.');
x.notices.push(later);
x.win.AndyCoreWooNoticeBridge.scan();
assert.equal(later.hidden,false,'bridge disabled after fatal error');
assert.equal(before,x.api.getState().key);
const off=setup(false);
for(const n of off.notices)assert.equal(n.hidden,false,'CSS unavailable -> original native');
assert.equal(off.win.AndyCoreWooNoticeBridge.stats().accepted,0);
assert.doesNotMatch(src,/fetch\(|XMLHttpRequest|wc_add_notice|WC\(\)->cart/,'no network or business writes');
console.log('PASS notification-ui-v1-r3-woo-bridge-harness (truthful handoff, duplicate, errors, fail open, dispose)');
