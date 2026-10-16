/**
 * Standalone real Chrome mobile emulation via CDP. No external packages.
 * Opens inquiry popup only; NEVER submits a lead. Uses isolated headless profile.
 * Usage: node scripts/wholesale-v2-browser-uat.mjs <url> <output_directory>
 */
import {spawn,execFileSync} from 'node:child_process';
import {mkdir,readFile,writeFile,rm} from 'node:fs/promises';
import {join,resolve} from 'node:path';
const url=process.argv[2]||'https://ybyirrigation.com/lp/irrigation-wholesale-germany/';
const out=resolve(process.argv[3]||'D:\\ai\\_uat\\wholesale-production-de');
const profile=join(out,'chrome-cdp-v2-profile');
const chrome='C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
await mkdir(out,{recursive:true});
await rm(profile,{recursive:true,force:true});
await mkdir(profile,{recursive:true});
let child,ws;
try {
 child=spawn(chrome,['--headless=new','--disable-gpu','--hide-scrollbars','--no-first-run','--no-default-browser-check','--remote-allow-origins=*','--remote-debugging-port=0','--user-data-dir='+profile,'--window-size=1440,900',url],{stdio:'ignore'});
 let port;
 for(let i=0;i<100;i++){
   try{const info=(await readFile(join(profile,'DevToolsActivePort'),'utf8')).split('\n');if(/^\d+$/.test(info[0].trim())){port=info[0].trim();break;}}catch{}
   await pause(100);
 }
 if(!port)throw Error('isolated DevTools port not ready');
 const tabs=await(await fetch('http://127.0.0.1:'+port+'/json/list')).json();
 const tab=tabs.find(x=>x.type==='page'&&x.webSocketDebuggerUrl);
 if(!tab)throw Error('no isolated page tab');
 ws=new WebSocket(tab.webSocketDebuggerUrl);
 await new Promise((res,rej)=>{const timer=setTimeout(()=>rej(Error('ws timeout')),8000);ws.addEventListener('open',()=>{clearTimeout(timer);res();},{once:true});ws.addEventListener('error',()=>{clearTimeout(timer);rej(Error('ws error'));},{once:true});});
 let seq=1;const pending=new Map();
 ws.addEventListener('message',(ev)=>{let x;try{x=JSON.parse(ev.data.toString());}catch{return;}if(!x.id||!pending.has(x.id))return;const p=pending.get(x.id);pending.delete(x.id);clearTimeout(p.t);x.error?p.reject(Error(JSON.stringify(x.error))):p.resolve(x.result);});
 const rpc=(method,params={})=>new Promise((resolveRpc,rejectRpc)=>{const id=seq++;const t=setTimeout(()=>{pending.delete(id);rejectRpc(Error('CDP timeout '+method));},13000);pending.set(id,{resolve:resolveRpc,reject:rejectRpc,t});ws.send(JSON.stringify({id,method,params}));});
 await rpc('Page.enable');await rpc('Runtime.enable');
 const expression="(()=>{const e=document.querySelector('.yby-wholesale-hero-v3__title-line'),i=document.querySelector('.yby-wholesale-hero-v3__content-inner'),h=document.querySelector('.yby-wholesale-hero-v3__title'),d=document.querySelector('.yby-wholesale-hero-v3__desc');const box=x=>x?{x:Math.round(x.getBoundingClientRect().x),right:Math.round(x.getBoundingClientRect().right),width:Math.round(x.getBoundingClientRect().width)}:null;return {innerWidth:window.innerWidth,outerWidth:window.outerWidth,devicePixelRatio:devicePixelRatio,documentScrollWidth:document.documentElement.scrollWidth,documentClientWidth:document.documentElement.clientWidth,media991:matchMedia('(max-width:991px)').matches,media480:matchMedia('(max-width:480px)').matches,cssMarker:!!document.getElementById('yby-wholesale-de-inline-css-v2'),titleWhiteSpace:e?getComputedStyle(e).whiteSpace:null,titleOverflowWrap:e?getComputedStyle(e).overflowWrap:null,titleFontSize:h?getComputedStyle(h).fontSize:null,contentBox:box(i),titleBox:box(h),descriptionBox:box(d),titleText:h?h.textContent.replace(/\\s+/g,' ').trim():null,triggers:document.querySelectorAll('[data-yby-inquiry-trigger]').length,globalPopup:!!document.getElementById('yby-global-inquiry-popup')};})()";
 const report={url,runs:[]};
 for(const size of [{name:'iphone14',width:390,height:844,mobile:true,dpr:2},{name:'desktop',width:1440,height:900,mobile:false,dpr:1}]){
   await rpc('Emulation.setDeviceMetricsOverride',{width:size.width,height:size.height,mobile:size.mobile,deviceScaleFactor:size.dpr});
   await rpc('Emulation.setTouchEmulationEnabled',{enabled:size.mobile,maxTouchPoints:1});
   await rpc('Page.navigate',{url});await pause(3400);
   const data=await rpc('Runtime.evaluate',{expression,returnByValue:true});
   const png=await rpc('Page.captureScreenshot',{format:'png',captureBeyondViewport:false,fromSurface:true});
   const shot=join(out,'cdp-'+size.name+'.png');await writeFile(shot,Buffer.from(png.data,'base64'));
   report.runs.push({size,shot,computed:data.result?.value||null});
 }
 await rpc('Emulation.setDeviceMetricsOverride',{width:390,height:844,mobile:true,deviceScaleFactor:2});
 await rpc('Page.navigate',{url});await pause(2200);
 const click=await rpc('Runtime.evaluate',{expression:"(()=>{const b=document.querySelector('.yby-wholesale-hero-v3__btn[data-yby-inquiry-trigger]')||document.querySelector('[data-yby-inquiry-trigger]');if(!b)return {button:false};b.click();const m=document.getElementById('yby-global-inquiry-popup')||document.querySelector('[data-yby-inquiry-modal]');return {button:true,modal:!!m,ariaHidden:m?.getAttribute('aria-hidden')||null,hidden:m?.hidden??null,form:!!m?.querySelector('[data-yby-inquiry-form]')};})()",returnByValue:true});
 report.inquiryClick=click.result?.value||null;
 await writeFile(join(out,'cdp-report.json'),JSON.stringify(report,null,2));
 console.log('CDP_UAT='+JSON.stringify(report));
}finally{
 if(ws)try{ws.close();}catch{}
 if(child?.pid)try{execFileSync('taskkill',['/PID',String(child.pid),'/T','/F'],{stdio:'ignore'});}catch{}
}
