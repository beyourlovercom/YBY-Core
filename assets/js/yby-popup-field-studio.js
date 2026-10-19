/* Popup Field Studio V2: one native matrix inside the existing settings form. */
(function () {
  "use strict";
  var root = document.getElementById("yby-popup-studio");
  var bootNode = document.getElementById("yby-studio-bootstrap");
  if (!root || !bootNode) return;
  var boot;
  try { boot = JSON.parse(bootNode.textContent); } catch (error) { return; }
  var initial = JSON.parse(JSON.stringify(boot.state || { version: 2, fields: {}, presets: {} }));
  var state = JSON.parse(JSON.stringify(initial));
  if (!state.fields || typeof state.fields !== "object") state.fields = {};
  if (!state.presets || typeof state.presets !== "object") state.presets = {};
  var shared = boot.fields || {};
  var legacy = boot.presets || {};
  var active = state.presets[boot.global_preset] ? boot.global_preset : (boot.global_preset || Object.keys(legacy)[0] || "");
  var selected = "email";
  var device = "desktop";
  var values = {};
  var overlayMode = "field";
  var counter = 1;
  var $ = function (selector) { return root.querySelector(selector); };
  var $$ = function (selector) { return Array.prototype.slice.call(root.querySelectorAll(selector)); };
  var escapeHTML = function (v) { return String(v == null ? "" : v).replace(/[&<>"']/g, function (c) { return {"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]; }); };
  var types = {text:"文本", email:"邮箱", tel:"电话", number:"数字", textarea:"多行文本", select:"下拉选择", radio:"单选", multiselect:"多选", checkbox:"勾选"};
  var templateList = {
    farm:{name:"Farm Size", type:"select", options:["Under 1 ha","1–5 ha","5–20 ha","20+ ha"]},
    bottle:{name:"Bottle Category", type:"select", options:["Glass Bottle","Glass Jar","Perfume Bottle","Custom Shape"]},
    film:{name:"Film Size",type:"text",options:[]}
  };
  var changeNotice = function (message) { $("#yby-studio-status").textContent = message || "有未保存修改。点击当前页面底部「保存」写入本站。"; };
  var fieldById = function (id) { return state.fields[id] || shared[id] || null; };
  var allFields = function () { return Object.assign({}, shared, state.fields); };
  var isOwn = function () { return !!state.presets[active]; };
  var selectedPreset = function () { return state.presets[active] || legacy[active] || null; };
  var fieldNames = function () { return Object.keys(allFields()); };
  var idsOfPreset = function (p, mode) { return p && Array.isArray(p[mode === "mobile" ? "mobile_fields" : "fields"]) ? p[mode === "mobile" ? "mobile_fields" : "fields"] : []; };
  var fallbackConfig = function (id) {
    var f = fieldById(id) || {id:id,label:id,placeholder:"",required:false};
    var p = selectedPreset();
    var req = p && p.validation ? p.validation.contact_requirement : "";
    function mode(device) {
      var enabled = idsOfPreset(p, device).indexOf(id) !== -1;
      return {
        enabled:enabled,
        required:enabled && (!!f.required || req === id),
        label:f.label || f.id,
        placeholder:f.placeholder || ""
      };
    }
    return {desktop:mode("desktop"),mobile:mode("mobile")};
  };
  var configOf = function (id) {
    var p = selectedPreset();
    if (isOwn()) {
      if (!p.configs[id]) p.configs[id] = {
        desktop:{enabled:false,required:false,label:(fieldById(id)||{}).label||id,placeholder:(fieldById(id)||{}).placeholder||""},
        mobile:{enabled:false,required:false,label:(fieldById(id)||{}).label||id,placeholder:(fieldById(id)||{}).placeholder||""}
      };
      return p.configs[id];
    }
    return fallbackConfig(id);
  };
  var countEnabled = function (mode) { return fieldNames().filter(function (id) { return configOf(id)[mode].enabled; }).length; };
  var sync = function () {
    state.version = 2;
    $("#yby-studio-payload").value = JSON.stringify(state);
  };
  var dirty = function () {
    sync(); changeNotice();
  };
  var modeCell = function (id, mode, key) {
    var cfg = configOf(id)[mode];
    var checked = cfg[key];
    var disabled = !isOwn() || (key === "required" && !cfg.enabled);
    return '<td class="ys-center"><input class="yby-studio-switch" type="checkbox" data-yby-studio-cfg="' +
      escapeHTML(id + "|" + mode + "|" + key) + '" aria-label="' + escapeHTML((fieldById(id)||{}).label||id) +
      " " + (mode === "desktop"?"电脑":"手机") + " " + (key === "enabled"?"启用":"必填") + '"' +
      (checked?' checked':'') + (disabled?' disabled':'') + '></td>';
  };
  var row = function (id) {
    var f=fieldById(id);if(!f)return "";
    return '<tr'+(id===selected?' class="ys-selected"':'')+'><td><div class="ys-field-name">'+escapeHTML(f.label||id)+'</div><div class="ys-field-id">'+escapeHTML(id)+'</div></td>' +
      '<td><div class="ys-type">'+(state.fields[id]?"本站":"公共")+' / '+escapeHTML(types[f.type]||f.type)+'</div></td>' +
      modeCell(id,"desktop","enabled")+modeCell(id,"desktop","required")+modeCell(id,"mobile","enabled")+modeCell(id,"mobile","required")+
      '<td class="ys-center"><button type="button" class="button button-small" data-yby-studio-select="'+escapeHTML(id)+'">'+(id===selected?"编辑中":"设置")+'</button></td></tr>';
  };
  function renderMatrix() {
    var ids=fieldNames(),standard=ids.filter(function(id){return !state.fields[id];}),local=ids.filter(function(id){return !!state.fields[id];});
    $("#yby-studio-tbody").innerHTML='<tr class="ys-separator"><td colspan="7">公共标准字段 · 当前网站配置独立，不会修改其它网站</td></tr>'+
       standard.map(row).join("")+'<tr class="ys-separator"><td colspan="7">本站扩展业务字段 · 仅存储在当前 WordPress 站点</td></tr>'+
       (local.length?local.map(row).join(""):'<tr><td colspan="7">尚无本站扩展字段，可点击「新增本站业务字段」。</td></tr>');
    $("#yby-studio-count").textContent='公共 '+standard.length+' 个 · 本站扩展 '+local.length+' 个';
    $("#yby-studio-totals").textContent='电脑启用 '+countEnabled("desktop")+' 个 / 手机启用 '+countEnabled("mobile")+' 个';
  }
  function renderPresets() {
    var combined=Object.assign({},legacy,state.presets);
    $("#yby-studio-preset").innerHTML=Object.keys(combined).map(function(id){
      var p=combined[id];return '<option value="'+escapeHTML(id)+'"'+(active===id?' selected':'')+'>'+escapeHTML((p.label||id)+(state.presets[id]?' · 本站方案':' · 原有只读'))+'</option>';
    }).join("");
    $("#yby-studio-activate").disabled=!isOwn();
    $("#yby-studio-note").textContent=isOwn()?
      '当前正在编辑本站独立预设。修改字段后点击页面底部原有「保存」按钮；不会自动影响已有落地页。':
      '原有预设只读。先「复制为本站预设」，再自由配置字段与文案；不会覆盖旧表单。';
  }
  function renderEditor(){
    if(!fieldById(selected))selected=fieldNames()[0]||"";
    var f=fieldById(selected);if(!f)return;
    var cfg=configOf(selected);
    $("#yby-studio-edit-title").textContent='字段属性 · '+(f.label||selected);
    $("#yby-studio-edit-desc").textContent=(state.fields[selected]?"本站扩展字段":"公共字段")+' / '+selected+' / '+(types[f.type]||f.type);
    $$("[data-yby-studio-edit]").forEach(function(input){
      var parts=input.dataset.ybyStudioEdit.split(".");
      input.value=cfg[parts[0]][parts[1]]||"";
      input.disabled=!isOwn()||!cfg[parts[0]].enabled;
    });
    $("#yby-studio-remove-field").disabled=!state.fields[selected];
    var opts=["select","radio","multiselect"].indexOf(f.type)!==-1;
    $("#yby-studio-options-wrap").hidden=!opts;
    $("#yby-studio-options").disabled=!state.fields[selected];
    $("#yby-studio-options").value=(f.options||[]).map(function(o){return o.label||o.value||o;}).join("\n");
  }
  function previewInput(f,c) {
    var id=escapeHTML(f.id),ph=escapeHTML(c.placeholder||""),v=escapeHTML(values[f.id]||"");
    var common='data-yby-studio-value="'+id+'" aria-label="'+escapeHTML(c.label||f.label||id)+'"';
    if(f.type==="textarea")return '<textarea '+common+' placeholder="'+ph+'">'+v+'</textarea>';
    if(["select","radio","multiselect"].indexOf(f.type)!==-1){
      return '<select '+common+(f.type==="multiselect"?' multiple size="3"':'')+'><option value="">'+(ph||"Select")+'</option>'+
       (f.options||[]).map(function(o){var value=o.value||o.label||o;return '<option value="'+escapeHTML(value)+'"'+(values[f.id]===value?' selected':'')+'>'+escapeHTML(o.label||value)+'</option>';}).join("")+'</select>';
    }
    if(f.type==="checkbox")return '<input '+common+' type="checkbox" value="1" '+(values[f.id]?"checked":"")+'>';
    return '<input '+common+' type="'+(f.type==="email"?"email":f.type==="number"?"number":f.type==="tel"?"tel":"text")+'" value="'+v+'" placeholder="'+ph+'">';
  }
  function renderPreview(){
    var p=selectedPreset()||{},ids=fieldNames().filter(function(id){return configOf(id)[device].enabled;});
    var image=isOwn()?p.image:"";
    var withImage=device==="desktop"&&((isOwn()?p.layout==="split_visual":true));
    $("#yby-studio-preview").innerHTML='<div class="yby-studio-preview'+(device==="mobile"?" is-phone":"")+'">'+
      (withImage?'<div class="yby-studio-preview-photo">'+(image?'<img src="'+escapeHTML(image)+'" alt="" loading="lazy" style="max-width:100%;max-height:270px;object-fit:contain">':'<div class="ys-graphic"></div>')+'<p>当前站点产品图片区域</p></div>':'')+
      '<div class="yby-studio-preview-copy"'+(withImage?'':' style="grid-column:1/-1"')+'><div class="ys-eyebrow">'+escapeHTML(boot.site_name||"CURRENT SITE")+' · INQUIRY</div>'+
      '<h4>'+escapeHTML(p.title||$("#andy-popup-business-preset").selectedOptions[0]?.textContent||"Request a Quote")+'</h4>'+
      '<p>'+escapeHTML(p.subtitle||"Share your requirements and we will prepare a quotation.")+'</p>'+
      '<div class="yby-studio-preview-fields">'+ids.map(function(id){var f=fieldById(id),c=configOf(id)[device];return '<div'+(f.type==="textarea"?' class="ys-full"':'')+'><label>'+escapeHTML(c.label||f.label||id)+(c.required?' <b>*</b>':'')+'</label>'+previewInput(f,c)+'</div>';}).join("")+'</div>'+
      '<button type="button" class="ys-submit" id="yby-studio-try">测试必填字段 →</button><div class="ys-privacy">仅预览校验，不提交任何真实询盘</div><div class="ys-message" id="yby-studio-test-result" role="status"></div></div></div>';
  }
  function renderAll() {
    renderPresets();renderMatrix();renderEditor();renderPreview();
    $$("[data-yby-studio-device]").forEach(function(b){b.classList.toggle("is-current",b.dataset.ybyStudioDevice===device);});
    sync();
  }
  function showDialog(mode) {
    overlayMode=mode;
    $("#yby-studio-dialog-title").textContent=mode==="clone"?"复制当前预设为本站方案":"新增本站业务字段";
    $("#yby-studio-dialog-description").textContent=mode==="clone"?
      "新建仅属于本站的独立预设。原有表单及其它网站保持不变。":"只在当前 WordPress 站点注册新业务字段。";
    $("#yby-studio-template-wrap").hidden=mode==="clone";
    $("#yby-studio-type-wrap").hidden=mode==="clone";
    $("#yby-studio-new-options-wrap").hidden=true;
    $("#yby-studio-template").value="custom";
    $("#yby-studio-new-type").value="text";
    $("#yby-studio-new-title").value=mode==="clone"?((selectedPreset()||{}).label||"New Inquiry")+" 副本":"";
    $("#yby-studio-new-options").value="";
    $("#yby-studio-dialog").hidden=false; $("#yby-studio-new-title").focus();
  }
  function closeDialog(){ $("#yby-studio-dialog").hidden=true; }
  function keyId(input,prefix){
    var slug=input.toLowerCase().normalize("NFKD").replace(/[^a-z0-9]+/g,"_").replace(/^_+|_+$/g,"");
    if(slug.length<3)slug="custom_"+counter++;
    slug=slug.slice(0,45);var id=prefix+slug;
    while((prefix==="site_" && (state.fields[id] || shared[id] || state.presets[id] || legacy[id])) || id==="site_")id=prefix+slug+"_"+counter++;
    return id;
  }
  function addField(){
    var name=$("#yby-studio-new-title").value.trim(),type=$("#yby-studio-new-type").value;
    if(!name)return;
    if(Object.keys(state.fields).length>=30){changeNotice("当前站点自定义字段上限为30个。");return;}
    var id=keyId(name,"site_");
    var options=["select","radio","multiselect"].includes(type)?$("#yby-studio-new-options").value.split(/[\n,，]+/).map(function(s){return s.trim();}).filter(Boolean).slice(0,30).map(function(s){return {value:s,label:s};}):[];
    var f={id:id,label:name,type:type,placeholder:"Please enter "+name,options:options};
    state.fields[id]=f;
    if(isOwn()){
      state.presets[active].configs[id]={
        desktop:{enabled:true,required:false,label:name,placeholder:f.placeholder},
        mobile:{enabled:true,required:false,label:name,placeholder:f.placeholder}
      };
    }
    selected=id;closeDialog();dirty();renderAll();
  }
  function clonePreset(){
    var name=$("#yby-studio-new-title").value.trim();
    if(!name)return;
    if(Object.keys(state.presets).length>=16){changeNotice("本站预设已达到上限16个。");return;}
    var id=keyId(name,"site_"),old=selectedPreset()||{};
    var cfg={};fieldNames().forEach(function(fid){cfg[fid]=JSON.parse(JSON.stringify(configOf(fid)));});
    state.presets[id]={
      id:id,label:name,enabled:true,layout:"split_visual",
      title:old.title||$("#andy-popup-business-preset").selectedOptions[0]?.textContent||"Request a Quote",
      subtitle:old.subtitle||"",
      image:old.image||"",
      page_profiles:Array.isArray(old.page_profiles)?old.page_profiles.slice(0,12):[],
      configs:cfg
    };
    active=id;selected="email";closeDialog();dirty();renderAll();
  }
  root.addEventListener("change",function(event){
    var t=event.target;
    if(t.id==="yby-studio-preset"){active=t.value;selected="email";values={};renderAll();return;}
    if(t.dataset.ybyStudioCfg){
      if(!isOwn())return;
      var keys=t.dataset.ybyStudioCfg.split("|"),id=keys[0],mode=keys[1],field=keys[2];
      var c=configOf(id)[mode];
      c[field]=!!t.checked;if(field==="enabled"&&!c.enabled)c.required=false;
      dirty();renderMatrix();renderEditor();renderPreview();return;
    }
    if(t.id==="yby-studio-template"){
      var item=templateList[t.value];
      if(item){
        $("#yby-studio-new-title").value=item.name;
        $("#yby-studio-new-type").value=item.type;
        $("#yby-studio-new-options").value=item.options.join("\n");
      }else{
        $("#yby-studio-new-title").value="";
        $("#yby-studio-new-type").value="text";
        $("#yby-studio-new-options").value="";
      }
    }
    if(t.id==="yby-studio-template"||t.id==="yby-studio-new-type"){
      $("#yby-studio-new-options-wrap").hidden=!["select","radio","multiselect"].includes($("#yby-studio-new-type").value);
    }
    if(t.dataset.ybyStudioValue){values[t.dataset.ybyStudioValue]=t.multiple?Array.from(t.selectedOptions).map(function(o){return o.value;}).join(","):t.type==="checkbox"?(t.checked?"1":""):t.value;}
  });
  root.addEventListener("input",function(event){
    var t=event.target;
    if(t.dataset.ybyStudioEdit && isOwn()){
      var keys=t.dataset.ybyStudioEdit.split("."),c=configOf(selected)[keys[0]];
      c[keys[1]]=t.value;dirty();renderPreview();
    }
    if(t.id==="yby-studio-options"&&state.fields[selected]){
      state.fields[selected].options=t.value.split(/[\n,，]+/).map(function(s){return s.trim();}).filter(Boolean).slice(0,30).map(function(s){return {value:s,label:s};});
      dirty();renderPreview();
    }
    if(t.dataset.ybyStudioValue)values[t.dataset.ybyStudioValue]=t.value;
  });
  root.addEventListener("click",function(event){
    var t=event.target.closest("button");if(!t)return;
    if(t.dataset.ybyStudioSelect){selected=t.dataset.ybyStudioSelect;renderMatrix();renderEditor();return;}
    if(t.dataset.ybyStudioDevice){device=t.dataset.ybyStudioDevice;values={};renderPreview();$$("[data-yby-studio-device]").forEach(function(b){b.classList.toggle("is-current",b.dataset.ybyStudioDevice===device);});return;}
    if(t.id==="yby-studio-clone"){showDialog("clone");return;}
    if(t.id==="yby-studio-new-field"){showDialog("field");return;}
    if(t.id==="yby-studio-cancel"){closeDialog();return;}
    if(t.id==="yby-studio-add-confirm"){overlayMode==="clone"?clonePreset():addField();return;}
    if(t.id==="yby-studio-remove-field"&&state.fields[selected]){
      var id=selected;delete state.fields[id];
      Object.keys(state.presets).forEach(function(p){delete state.presets[p].configs[id];});
      selected="email";dirty();renderAll();return;
    }
    if(t.id==="yby-studio-activate"&&isOwn()){
      var global=$("#andy-popup-business-preset");
      if(!Array.prototype.some.call(global.options,function(o){return o.value===active;})){
        var option=document.createElement("option");option.value=active;option.textContent=state.presets[active].label+" · 本站方案";global.add(option);
      }
      global.value=active;
      changeNotice("已选择本站预设；请点击原有「保存」按钮后才会改变本站 Global Popup。");
      return;
    }
    if(t.id==="yby-studio-try"){
      var errors=[],controls=$$("[data-yby-studio-value]");
      controls.forEach(function(control){
        var id=control.dataset.ybyStudioValue,config=configOf(id)[device],value=control.type==="checkbox"?(control.checked?"1":""):control.value.trim();
        var invalid=config.required&&!value;
        if(id==="email"&&value&&!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value))invalid=true;
        control.classList.toggle("ys-invalid",invalid);
        if(invalid)errors.push(id);
      });
      $("#yby-studio-test-result").textContent=errors.length?"请检查 "+errors.length+" 个必填或格式错误字段。":"✓ 前端模拟验证通过；没有发送任何询盘。";
      $("#yby-studio-test-result").style.color=errors.length?"#a83838":"#17613e";
      return;
    }
    if(t.id==="yby-studio-export"){
      var p=$("#yby-studio-json");p.textContent=JSON.stringify({site:boot.site_name,selected_preset:active,state:state},null,2);p.hidden=!p.hidden;return;
    }
    if(t.id==="yby-studio-reset"){
      state=JSON.parse(JSON.stringify(initial));active=boot.global_preset||Object.keys(legacy)[0]||"";
      selected="email";values={};renderAll();changeNotice("已撤销本次未保存修改；WordPress 保存内容未受影响。");return;
    }
  });
  root.addEventListener("keydown",function(event){if(event.key==="Escape")closeDialog();});
  root.closest("form")?.addEventListener("submit",sync);
  renderAll();
})();
