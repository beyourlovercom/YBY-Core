/* All-notice bridge: Woo successes, errors, info, safe action links, fail open. */
const assert=require('node:assert/strict');
const fs=require('node:fs'),path=require('node:path'),vm=require('node:vm');
const source=fs.readFileSync(path.resolve(__dirname,'../public/assets/js/andy-core-notification-woo-classic.js'),'utf8');
function fakeNode(text,type='success',opts={}){
 const attrs={},css={display:'',priority:''};
 const classes=['woocommerce-'+(type==='success'?'message':type)];
 return {
  textContent:text,hidden:false,isConnected:true,attributes:attrs,
  parentElement:null,style:{
   getPropertyValue:()=>css.display,getPropertyPriority:()=>css.priority,
   setProperty:(n,v,p)=>{css.display=v;css.priority=p;},removeProperty:()=>{css.display='';}
  },
  matches(selector){return selector.includes('.'+classes[0]) && !opts.notMatching;},
  closest(selector){if(opts.contextual&&selector.includes('form-row'))return {};return null;},
  querySelectorAll(){return opts.controls||[];},
  getAttribute(key){return Object.hasOwn(attrs,key)?attrs[key]:null;},
  hasAttribute(key){return Object.hasOwn(attrs,key);},
  setAttribute(key,val){attrs[key]=String(val);},
  removeAttribute(key){delete attrs[key];}
 };
}
function setup(ready=true){
 const nodes=[fakeNode('Coupon applied successfully.'),fakeNode('Coupon applied successfully.'),
  fakeNode('Payment declined, please retry.','error'),
  fakeNode('Address cannot be empty.','error',{contextual:true})];
 let next=0,current=null;
 const listeners={},doc={readyState:'complete',body:{},
  querySelectorAll:()=>nodes};
 const api={isReady:()=>ready,notify(p){current={id:++next,type:p.type,key:p.key,action:p.action};return{id:next};},getState:()=>current};
 const win={location:{href:'https://test.invalid/checkout/',pathname:'/checkout/',origin:'https://test.invalid'},
  AndyCoreNotificationUI:api,getComputedStyle:n=>({display:n.style.getPropertyValue('display')||'block',visibility:'visible'}),
  setTimeout:()=>3,clearTimeout:()=>{},addEventListener:(n,f)=>{listeners[n]=f;},
  removeEventListener:n=>{delete listeners[n];},MutationObserver:class{observe(){}disconnect(){}constructor(fn){this.fn=fn;}}};
 vm.runInNewContext(source,{window:win,document:doc,Map,Object,Array,String,URL});
 return {nodes,win,api,listeners};
}
const t=setup();
assert.equal(t.nodes[0].hidden,true);
assert.equal(t.nodes[1].hidden,true);
assert.equal(t.win.AndyCoreWooNoticeBridge.stats().duplicates,1);
assert.equal(t.nodes[2].hidden,false,'payment error always native');
assert.equal(t.api.getState().type,'success','error did not replace success with island');
assert.equal(t.nodes[3].hidden,false,'field error remains inline to keep context');
assert.match(source,/native-only by Owner decision/,'error must never bridge to island');
assert.equal(t.win.AndyCoreWooNoticeBridge.stats().contextual,0,'error context remains native only');
const newInfo=fakeNode('Shipping updated.','info');
t.nodes.push(newInfo);t.win.AndyCoreWooNoticeBridge.scan();
assert.equal(newInfo.hidden,true,'info uses island');
const link={tagName:'A',href:'https://test.invalid/cart/',textContent:'View cart',
 hasAttribute:()=>false,classList:{contains:()=>false}};
const notice=fakeNode('View cart Item added.','success',{controls:[link]});
t.nodes.push(notice);t.win.AndyCoreWooNoticeBridge.scan();
assert.equal(notice.hidden,true);
assert.equal(t.api.getState().action.url,'https://test.invalid/cart/');
const foreign={...link,href:'https://external.example/'};
const unsafe=fakeNode('External help available','info',{controls:[foreign]});
t.nodes.push(unsafe);t.win.AndyCoreWooNoticeBridge.scan();
assert.equal(unsafe.hidden,false,'external action remains native');
t.listeners.error();
assert.equal(t.nodes[0].hidden,false);
assert.equal(t.nodes[2].hidden,false);
assert.equal(t.nodes[4].hidden,false);
const after=fakeNode('Should stay native','error');
t.nodes.push(after);t.win.AndyCoreWooNoticeBridge.scan();
assert.equal(after.hidden,false,'fail open after JS error');
const off=setup(false);
assert.equal(off.nodes.every(n=>!n.hidden),true,'engine unavailable native remains');
assert.doesNotMatch(source,/fetch\(|XMLHttpRequest|wc_add_notice|WC\(\)->cart/);
const nativeSkin=fs.readFileSync(path.resolve(__dirname,'../public/assets/css/andy-core-native-notice-skin.css'),'utf8');
assert.ok(nativeSkin.includes(':is(:focus,:focus-visible) {') && nativeSkin.includes('outline:0 none transparent!important;'), 'noninteractive notice suppresses large focus rectangle');
assert.ok(nativeSkin.includes(':is(a,.button,button):focus-visible') && nativeSkin.includes('outline:2px solid #a54155!important;'), 'action links keep visible keyboard focus');
console.log('PASS native-notice-focus-contract (no selected container rectangle, links accessible)');
console.log('PASS notification-ui-v1-r3-woo-bridge-harness (success/info island, errors native, actions, fail-open)');
