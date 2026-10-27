/* Real Chrome integration fixture; no live Woo requests, orders or payments. */
const {spawn}=require('node:child_process');
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
const port=9253, profile=require('node:path').join(require('node:os').tmpdir(),'andy-core-notice-r3-chrome');
const chrome=process.env.CHROME_BIN || 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const url=process.env.ANDY_NOTIFICATION_UAT_URL || require('node:url').pathToFileURL(require('node:path').join(__dirname,'fixtures','notification-ui-v1-woo-r3-browser.html')).href;
const child=spawn(chrome,['--headless=new','--disable-extensions','--no-first-run','--no-default-browser-check','--disable-background-networking','--remote-debugging-port='+port,'--user-data-dir='+profile,'about:blank'],{windowsHide:true,stdio:'ignore'});
let socket,cmdId=0,handlers=new Map(),exceptions=[];
function assert(x,msg){if(!x)throw new Error('FAIL '+msg);}
async function connect(){
let tab;
for(let i=0;i<90;i++){try{let a=await(await fetch('http://127.0.0.1:'+port+'/json')).json();tab=a.find(x=>x.type==='page');if(tab)break;}catch(e){}await sleep(120);}
assert(tab,'Chrome available');
socket=new WebSocket(tab.webSocketDebuggerUrl);
await new Promise((resolve,reject)=>{socket.onopen=resolve;socket.onerror=reject;});
socket.onmessage=e=>{let obj=JSON.parse(e.data);if(obj.method==='Runtime.exceptionThrown')exceptions.push(obj.params.exceptionDetails.text);if(obj.id&&handlers.has(obj.id)){let h=handlers.get(obj.id);handlers.delete(obj.id);obj.error?h.reject(Error(obj.error.message)):h.resolve(obj);}};
await send('Runtime.enable');await send('Page.enable');
}
function send(method,params={}){return new Promise((resolve,reject)=>{let id=++cmdId;handlers.set(id,{resolve,reject});socket.send(JSON.stringify({id,method,params}));setTimeout(()=>{if(handlers.has(id)){handlers.delete(id);reject(Error('CDP timeout '+method));}},9000);});}
async function evalJs(expression){let d=await send('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(d.result.exceptionDetails)throw Error(JSON.stringify(d.result.exceptionDetails));return d.result.result.value;}
async function waitFor(expression,loops=55){for(let i=0;i<loops;i++){const v=await evalJs(expression);if(v)return v;await sleep(130);}throw Error('Timed out: '+expression);}
async function normal(){
await send('Page.navigate',{url});
await waitFor("!!(window.AndyCoreWooNoticeBridge && window.AndyCoreNotificationUI)");
const count=await waitFor("window.AndyCoreWooNoticeBridge.stats().accepted >= 2 && window.AndyCoreNotificationUI.isReady()");
assert(count,'two native duplicate successes bridged');
const s=await evalJs("(()=>{let el=document.querySelector('andy-core-notification-ui'),root=el.shadowRoot,a=window.AndyCoreWooNoticeBridge.stats();return {a,first:document.querySelector('#success-1').hidden,second:document.querySelector('#success-2').hidden,error:!document.querySelector('#native-error').hidden,action:!document.querySelector('#native-action').hidden,coreText:root.querySelector('.island-title').textContent,computed:getComputedStyle(root.querySelector('.island-title')).fontSize}})()");
assert(s.first&&s.second&&s.error&&s.action,'duplicate successes suppressed, critical/action native intact');
assert(s.coreText==='Coupon applied successfully.'&&s.computed==='12.5px','theme isolated CSS');
assert(s.a.duplicates>=1,'identical native duplicate recognized');
const newMsg=await evalJs("(()=>{const n=document.createElement('div');n.className='woocommerce-message';n.id='new-coupon-msg';n.textContent='Coupon B applied successfully.';document.querySelector('.woocommerce-notices-wrapper').appendChild(n);return n.textContent;})()");
await waitFor("document.querySelector('#new-coupon-msg').hidden");
const core=await evalJs("document.querySelector('andy-core-notification-ui').shadowRoot.querySelector('.island-title').textContent");
assert(core===newMsg,'new Woo native event preempts');
await send('Emulation.setDeviceMetricsOverride',{width:390,height:844,deviceScaleFactor:1,mobile:true});
const mobile=await evalJs("(()=>{let h=document.querySelector('andy-core-notification-ui');return {viewport:innerWidth,width:Math.ceil(h.getBoundingClientRect().width),height:Math.ceil(h.shadowRoot.querySelector('.island').getBoundingClientRect().height)}})()");
assert(mobile.viewport===390&&mobile.width<=370&&mobile.height<=45,'compact mobile success');
const restored=await evalJs("(()=>{window.dispatchEvent(new Event('error'));return {first:document.querySelector('#success-1').hidden,second:document.querySelector('#success-2').hidden,third:document.querySelector('#new-coupon-msg').hidden};})()");
assert(!restored.first&&!restored.second&&!restored.third,'browser error -> native recovered');
await send('Emulation.clearDeviceMetricsOverride');
return {s,mobile,restored};
}
async function broken(){
await send('Page.navigate',{url:url+'?broken=1'});
await waitFor("!!window.AndyCoreWooNoticeBridge");
await sleep(1600);
const s=await evalJs("(()=>({ready:window.AndyCoreNotificationUI.isReady(),state:window.AndyCoreWooNoticeBridge.stats(),first:document.querySelector('#success-1').hidden,second:document.querySelector('#success-2').hidden,error:document.querySelector('#native-error').hidden}))()");
assert(!s.ready&&s.state.accepted===0&&!s.first&&!s.second&&!s.error,'stylesheet failure native fallback');
return s;
}
async function main(){await connect();const good=await normal();const failure=await broken();assert(exceptions.length===0,'unexpected JS exceptions '+exceptions);console.log(JSON.stringify({pass:true,normal:good,cssFailure:failure,exceptions}));}
main().catch(e=>{console.error(e.stack||e);process.exitCode=1;}).finally(()=>{try{socket&&socket.close()}catch(e){}try{child.kill()}catch(e){}});
