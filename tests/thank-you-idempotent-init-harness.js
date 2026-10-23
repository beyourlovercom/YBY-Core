"use strict";
const assert = require("assert");
const fs = require("fs");
const vm = require("vm");
const path = require("path");
const source = fs.readFileSync(path.join(__dirname, "../public/assets/js/yby-core-public.js"),"utf8");
const match = source.match(/  window\.YBYThankYou\.init = function \(\) \{[\s\S]*?\n  \};/);
assert(match,"missing Andy Core thank-you initialization function");
function exercise(initialThankYou, transitions) {
 const counts = {hydrate:0,name:0,caseId:0,links:0,actions:0,view:0,lead:0};
 let isPage=initialThankYou;
 const page = {
  hydrateCaseIdFromUrl(){counts.hydrate++},
  renderLeadName(){counts.name++},
  renderCaseId(){counts.caseId++},
  applyConfigLinks(){counts.links++},
  bindActions(){counts.actions++},
  trackPageView(){counts.view++},
  trackGenerateLeadOnce(){counts.lead++}
 };
 const context=vm.createContext({window:{YBYThankYou:page},isThankYouPage:()=>isPage});
 vm.runInContext(match[0],context,{filename:"yby-core-thank-you-init.js"});
 for (const step of transitions) {
   if (typeof step==="boolean")isPage=step;
   else page.init();
 }
 return {counts,initialized:page._initialized===true};
}
const twice=exercise(true,["init","init"]);
assert.deepStrictEqual(twice.counts,{hydrate:1,name:1,caseId:1,links:1,actions:1,view:1,lead:1},"second inline init must not duplicate analytics or listeners");
console.log("PASS duplicate init called twice => once per action");
const wrongPage=exercise(false,["init","init"]);
assert.deepStrictEqual(Object.values(wrongPage.counts),Array(7).fill(0));
assert.strictEqual(wrongPage.initialized,false);
console.log("PASS non-thank-you page => no init and no sentinel");
const delayed=exercise(false,["init",true,"init","init"]);
assert.deepStrictEqual(Object.values(delayed.counts),Array(7).fill(1));
assert.strictEqual(delayed.initialized,true);
console.log("PASS later thank-you activation => exactly once");
console.log("THANK_YOU_INIT_REGRESSION 3/3 PASS");