/* Andy Commerce — preserve native HPOS preview/edit actions; remove only Woo's literal '#' */
(()=>{
  'use strict';
  const update=()=>{
    if(!document.body.classList.contains('woocommerce_page_wc-orders')) return;
    for(const row of document.querySelectorAll('table.wp-list-table tr[id^="order-"]')){
      const strong=row.querySelector('a.order-view strong');
      if(!strong) continue;
      const id=row.id.slice('order-'.length);
      if(!/^[0-9]+$/.test(id)) continue;
      const current=strong.textContent.trim();
      if(current.startsWith('#'+id) && (current.length===(id.length+1) || current[id.length+1]===' ')){
        strong.textContent=current.slice(1);
      }
    }
  };
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',update,{once:true});
  else update();
})();
