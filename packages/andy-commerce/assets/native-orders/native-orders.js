/* Native WooCommerce HPOS page enhancement; never replaces WC core forms or payment/fulfillment APIs. */
(function(){
  'use strict';
  const config=window.BYLNativeOrders||{};
  const root=document.body;
  if(!root.classList.contains('byl-native-orders-shopify-v1')) return;
  const $=(s,p=document)=>p.querySelector(s);
  const $$=(s,p=document)=>Array.from(p.querySelectorAll(s));
  const allowed=new Set(config.pinIds||[]);
  const pinned=new Set(config.pins||[]);
  const menu=$('#byl-native-more-menu');
  const menuButton=$('[data-byl-action="more"]');
  const statusMenu=$('#byl-native-status-menu');
  const statusButton=$('[data-byl-action="status"]');
  const statusPrimary=$('[data-byl-action="status-primary"]');
  const statusSave=$('[data-byl-action="save-status"]');
  const statusHelp=$('#byl-native-status-help');
  let dirtyStatus=false;
  function setMenu(open){if(menu&&menuButton){menu.hidden=!open;menuButton.setAttribute('aria-expanded',String(!!open));}}
  function setStatusMenu(open){if(statusMenu&&statusButton){statusMenu.hidden=!open;statusButton.setAttribute('aria-expanded',String(!!open));}}
  function existingNativeButton(selectors){for(const selector of selectors){const b=$(selector);if(b&&!b.disabled)return b;}return null;}
  function moveToBox(id){const el=$(id);if(!el)return false;el.scrollIntoView({behavior:'smooth',block:'start'});return true;}
  function nativeStatus(){return existingNativeButton(['#order_status','select[name="order_status"]','[name="order_status"]']);}
  function revealNativeEditor(kind){
    const toggle=$('#byl-native-advanced-toggle');
    if(toggle&&!root.classList.contains('byl-native-advanced-open'))toggle.click();
    const form=$('#woocommerce-order-data');
    const targets={status:'#order_status',customer:'[name="customer_user"]',shipping:'.order_data_column_shipping',billing:'.order_data_column_billing'};
    const el=$(targets[kind]||'.order_data_column_general',form||document)||form;
    if(el)window.requestAnimationFrame(()=>el.scrollIntoView({behavior:'smooth',block:'center'}));
    return el;
  }
  function normalizedStatus(v){return String(v||'').replace(/^wc-/,'');}
  function warn(message){window.alert(message);}
  function stageStatus(value){
    const select=nativeStatus();
    if(!select){warn('原生状态控件不可用。未修改订单。');return false;}
    const option=Array.from(select.options).find(opt=>normalizedStatus(opt.value)===normalizedStatus(value));
    if(!option){warn('当前订单没有可用的目标状态，未执行修改。');return false;}
    select.value=option.value;
    select.dispatchEvent(new Event('change',{bubbles:true}));
    dirtyStatus=true;
    if(statusPrimary){statusPrimary.textContent=option.textContent.trim()+' · Unsaved';statusPrimary.dataset.primaryStatus='';}
    if(statusHelp)statusHelp.textContent='Pending change: '+option.textContent.trim()+'。点击 Save status 后会触发 WooCommerce 原生 Update。';
    if(statusSave)statusSave.hidden=false;
    setStatusMenu(true);
    return true;
  }
  function saveStatus(){
    if(!dirtyStatus){warn('没有待保存的订单状态更改。');return;}
    const save=existingNativeButton([
      '#woocommerce-order-actions button.save_order',
      '#woocommerce-order-actions input[name="save"]',
      '#woocommerce-order-actions button[name="save"]',
      'button.save_order',
      'input[name="save"]'
    ]);
    if(!save){
      warn('未找到 WooCommerce 原生 Update。请展开 Order actions，使用原生按钮保存。');
      revealNativeEditor('status');moveToBox('#woocommerce-order-actions');return;
    }
    if(!window.confirm('确认保存订单状态？WooCommerce 可能发送邮件并触发其他插件联动。'))return;
    setStatusMenu(false);
    save.click();
    // WooCommerce owns the form submission, success/error notices and database writes.
  }
  function action(a,button){
    if(a!=='more')setMenu(false);
    if(a!=='status'&&a!=='set-status'&&a!=='status-primary'&&a!=='save-status')setStatusMenu(false);
    if(a==='refund'){
      const b=existingNativeButton(['#woocommerce-order-items .refund-items','#woocommerce-order-items button.refund-items','.refund-items']);
      if(b){b.scrollIntoView({behavior:'smooth',block:'center'});b.click();}
      else warn('当前订单没有可用的原生退款按钮。请检查支付状态和权限。');
    }else if(a==='edit'){
      const b=existingNativeButton(['#woocommerce-order-items .edit-order-items','#woocommerce-order-items .edit_order_items','button.edit_order_items']);
      if(b){b.scrollIntoView({behavior:'smooth',block:'center'});b.click();}
      else revealNativeEditor('customer');
    }else if(a==='cancel'){
      if(window.confirm('选择 Cancelled 状态？这一步不会保存。保存可能触发 WooCommerce 邮件及其他插件联动。'))stageStatus('cancelled');
    }else if(a==='print')window.print();
    else if(a==='metafields'){
      if(!moveToBox('#order_custom'))warn('此订单没有可编辑的原生 Custom Fields 模块。');
    }else if(a==='status')setStatusMenu(statusMenu?.hidden);
    else if(a==='status-primary'){
      const value=button?.dataset.primaryStatus||'';
      if(value==='completed'){
        if(window.confirm('Mark as fulfilled 对应 WooCommerce 的 Completed 状态，不会创建 AfterShip 运单或真实发货记录。是否先暂存此状态？'))stageStatus(value);
      }else if(value)stageStatus(value);
      else setStatusMenu(statusMenu?.hidden);
    }else if(a==='set-status')stageStatus(button?.dataset.status||'');
    else if(a==='save-status')saveStatus();
  }
  document.addEventListener('click',event=>{
    const button=event.target.closest('[data-byl-action]');
    if(button){
      if(button.disabled)return;
      event.preventDefault();event.stopPropagation();
      const a=button.dataset.bylAction;
      if(a==='more'){setStatusMenu(false);setMenu(menu?.hidden);}else action(a,button);
      return;
    }
    if(menu&&!menu.hidden&&!menu.contains(event.target))setMenu(false);
    if(statusMenu&&!statusMenu.hidden&&!statusMenu.contains(event.target))setStatusMenu(false);
  });
  document.addEventListener('keydown',event=>{if(event.key==='Escape'){setMenu(false);setStatusMenu(false);}});

  function pinUI(box){
    if(!box.id || !allowed.has(box.id)) return;
    const header=$('.postbox-header',box), actions=$('.handle-actions',box);
    if(!header||!actions||$('.byl-native-pin',actions)) return;
    const button=document.createElement('button');
    button.type='button';button.className='byl-native-pin';button.textContent='📌';
    button.setAttribute('aria-label',pinned.has(box.id)?'取消固定模块':'固定模块');
    button.setAttribute('aria-pressed',String(pinned.has(box.id)));
    button.title=pinned.has(box.id)?'取消固定':'固定';
    if(pinned.has(box.id)) button.classList.add('is-pinned');
    const nativeChevron=$('button.handlediv',actions);
    actions.insertBefore(button,nativeChevron||actions.firstChild);
    if(pinned.has(box.id)&&box.classList.contains('closed')){
      box.classList.remove('closed');
      const toggle=$('button.handlediv',actions);
      if(toggle) toggle.setAttribute('aria-expanded','true');
    }
    button.addEventListener('click',async e=>{
      e.stopPropagation();e.preventDefault();
      const target=!pinned.has(box.id);button.disabled=true;
      try{
        const payload=new URLSearchParams({action:'andy_commerce_native_orders_save_pin',nonce:config.nonce||'',box:box.id,pin:target?'1':'0'});
        const res=await fetch(config.ajaxUrl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:payload.toString()});
        const json=await res.json(); if(!res.ok||!json.success) throw new Error('Save failed');
        if(target) pinned.add(box.id); else pinned.delete(box.id);
        button.classList.toggle('is-pinned',target);
        button.setAttribute('aria-pressed',String(target));
        button.setAttribute('aria-label',target?'取消固定模块':'固定模块');
        button.title=target?'取消固定':'固定';
      }catch(err){ warn('未能保存固定偏好，请重试。'); }
      finally{ button.disabled=false; }
    });
  }
  $$('#poststuff .postbox').forEach(pinUI);

  function addCouponLine(){
    const panel=$('#woocommerce-order-attribution .inside') || $('#order_attribution .inside') ||
      $$('#poststuff .postbox').find(box=>/order attribution|conversion summary|订单归因/i.test(($('.postbox-header h2',box)?.textContent||'')))?.querySelector('.inside');
    if(!panel || $('.byl-native-conversion-coupons',panel)) return;
    const p=document.createElement('div'); p.className='byl-native-conversion-coupons';
    const strong=document.createElement('strong'); strong.textContent='Discount: '; p.appendChild(strong);
    const coupons=Array.isArray(config.coupons)?config.coupons:[];
    if(!coupons.length){ p.appendChild(document.createTextNode('—')); }
    else {
      coupons.forEach((c,i)=>{
        if(i) p.appendChild(document.createTextNode(', '));
        if(c.url){ const link=document.createElement('a'); link.href=c.url; link.textContent=c.code; p.appendChild(link); }
        else { const span=document.createElement('span'); span.textContent=c.code; p.appendChild(span); }
      });
    }
    panel.insertBefore(p,panel.firstChild);
  }
  addCouponLine();

  // The Advanced editing control is server-rendered as a real checkbox+label.
  // CSS can expand/collapse native fields without JS; JavaScript only adds nicer scroll/focus.
  function setupAdvancedEditor(){
    const native=$('#woocommerce-order-data');
    const checkbox=native && $('#byl-native-advanced-check',native);
    const host=native && $('.byl-native-advanced-host',native);
    if(!native||!checkbox||!host) return;
    const columns=$('.order_data_column_container',native);
    const itemColumns=$$('.order_data_column',native);
    // If Woo changed markup completely, do not risk suppressing edit fields.
    const safeShape=!!columns || itemColumns.length>=2;
    if(!safeShape){
      checkbox.checked=true;
      checkbox.disabled=true;
      host.classList.add('byl-native-advanced-unsupported');
      return;
    }
    const sync=()=>{
      root.classList.toggle('byl-native-advanced-open',checkbox.checked);
      checkbox.setAttribute('aria-expanded',String(checkbox.checked));
    };
    checkbox.addEventListener('change',sync);
    document.addEventListener('click',event=>{
      const btn=event.target.closest('[data-byl-native-edit]');
      if(!btn)return;
      event.preventDefault();event.stopPropagation();
      checkbox.checked=true;
      sync();
      const kind=btn.dataset.bylNativeEdit||'customer';
      const selectors={customer:'[name="customer_user"]',billing:'.order_data_column_billing, .order_data_column:nth-child(2)',shipping:'.order_data_column_shipping, .order_data_column:nth-child(3)'};
      const el=$(selectors[kind]||'.order_data_column',native)||native;
      window.requestAnimationFrame(()=>el.scrollIntoView({behavior:'smooth',block:'center'}));
    });
    sync();
    root.classList.add('byl-native-ready');
  }
  setupAdvancedEditor();

  // Conversion summary defaults open, while the native postbox remains the controller.
  function showAttribution(){
    const panel=$('#woocommerce-order-attribution') || $('#order_attribution') ||
      $$('#postbox-container-1 .postbox').find(box=>/order attribution|conversion summary|订单归因/i.test(($('.postbox-header h2',box)?.textContent||'')));
    if(panel && panel.classList.contains('closed')){
      panel.classList.remove('closed');
      const handle=$('button.handlediv',panel);
      if(handle)handle.setAttribute('aria-expanded','true');
    }
  }
  showAttribution();

  // V0.4.5: the two native editor modules are managed by native-fold.js,
  // a separate first-loaded script. Never re-toggle them here.

  const shipCard=$('#byl-native-customer .byl-native-shipcol');
  if(shipCard){
    const copy=$('.byl-native-copy',shipCard);
    if(copy) copy.addEventListener('click', async e=>{
      e.preventDefault(); e.stopPropagation();
      const lines=$$('.byl-native-address-line',shipCard).map(el=>(el.innerText||'').trim()).filter(Boolean);
      if(!lines.length){ warn('订单没有可复制的收货地址'); return; }
      try{
        if(navigator.clipboard?.writeText){ await navigator.clipboard.writeText(lines.join('\n')); }
        else {
          const t=document.createElement('textarea'); t.value=lines.join('\n'); t.style.position='fixed'; t.style.left='-9999px'; document.body.appendChild(t); t.select();
          const ok=document.execCommand('copy'); t.remove(); if(!ok) throw new Error('Copy failed');
        }
        const tip=$('.byl-native-tooltip',copy);
        if(tip){ tip.textContent='Copied!'; setTimeout(()=>{ if(tip.isConnected) tip.textContent='Copy address'; },1500); }
      }catch(err){ warn('复制失败，请手动复制收货地址。'); }
    });
  }
})();