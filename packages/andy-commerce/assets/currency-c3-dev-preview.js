/* C3-D/E/F Dev-admin-only display shadow. No Woo API, price/cart mutation, checkout or payment hooks. */
(function () {
  'use strict';
  var selector = '[data-ac-c3-preview="1"] select';
  var rates = Object.freeze({USD:1,EUR:.9,GBP:.78,AUD:1.5,CAD:1.35});
  var signs = Object.freeze({USD:'$',EUR:'€',GBP:'£',AUD:'A$',CAD:'C$'});
  var key = 'andy_currency_c3_dev_selection';
  var targetSelector = '.woocommerce-Price-amount, .amount';
  function enabled(){return !!document.querySelector(selector) && !document.body.classList.contains('woocommerce-checkout');}
  function selected(){try{var s=sessionStorage.getItem(key);return Object.prototype.hasOwnProperty.call(rates,s)?s:'USD';}catch(e){return 'USD';}}
  function sourceAmount(node){
    if(!node || node.closest('.ac-c3-shadow-display'))return null;
    var s=node.textContent.replace(/,/g,'').trim();
    if(!/^\$\s*\d+(?:\.\d{1,2})?$/.test(s))return null;
    var n=Number(s.replace('$','').trim());
    return Number.isFinite(n)&&n>=0&&n<=1000000?n:null;
  }
  function format(n,code){return signs[code]+(Math.round(n*rates[code]*100)/100).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});}
  function reset(){
    document.querySelectorAll('.ac-c3-shadow-display').forEach(function(n){n.remove();});
  }
  function render(){
    reset();
    if(!enabled())return;
    var code=selected();
    document.querySelectorAll(selector).forEach(function(s){s.value=code;});
    if(code==='USD')return;
    var nodes=document.querySelectorAll(targetSelector), shown=0;
    nodes.forEach(function(node){
      if(shown>=24 || node.closest('del') || node.closest('ins')===null && node.parentElement && node.parentElement.querySelector('.woocommerce-Price-amount')!==node && node.classList.contains('amount') && node.parentElement.querySelectorAll('.amount').length>1)return;
      var amount=sourceAmount(node);
      if(amount===null || !node.parentElement || node.closest('header,footer'))return;
      var span=document.createElement('span');
      span.className='ac-c3-shadow-display';
      span.textContent=' ≈ '+format(amount,code)+' (Dev preview)';
      span.setAttribute('aria-label','Approximate test-only currency display; USD remains actual payable');
      span.style.cssText='font-size:11px;white-space:nowrap;color:#777;margin-left:5px;font-weight:400';
      node.insertAdjacentElement('afterend',span);
      shown++;
    });
  }
  function onChange(e){
    if(!e.target.matches(selector))return;
    var code=e.target.value;
    if(!Object.prototype.hasOwnProperty.call(rates,code))return;
    try{sessionStorage.setItem(key,code);}catch(err){}
    render();
  }
  document.addEventListener('change',onChange);
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',render,{once:true});
  else render();
  window.addEventListener('pageshow',render);
})();
