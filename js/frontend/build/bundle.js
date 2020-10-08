!function e(t,o,n){function s(a,r){if(!o[a]){if(!t[a]){var l="function"==typeof require&&require;if(!r&&l)return l(a,!0);if(i)return i(a,!0);var c=new Error("Cannot find module '"+a+"'");throw c.code="MODULE_NOT_FOUND",c}var u=o[a]={exports:{}};t[a][0].call(u.exports,(function(e){return s(t[a][1][e]||e)}),u,u.exports,e,t,o,n)}return o[a].exports}for(var i="function"==typeof require&&require,a=0;a<n.length;a++)s(n[a]);return s}({1:[function(e,t,o){"use strict";Object.defineProperty(o,"__esModule",{value:!0}),o.default=void 0;var n=e("diff-dom");o.default=class{constructor(){this.dd=new n.DiffDOM({maxChildCount:!1}),this.observer=null,this.blocked=[],this.included=[],this.dom_prev=document.createElement("body"),this.dom_not_translated=document.createElement("body"),this.jobCur=null,this.jobTodo=null,this.cache={},this.translateAllDebounce=this.debounce(()=>{this.translateAll()},1e3)}init(){this.included=window.gtbabel_detect_dom_changes_include,void 0!==this.included&&this.setupMutationObserver()}async translateAll(){if(this.jobCur!==this.jobTodo){let e=this.jobTodo;this.jobCur=e;for(let t of this.included)await this.translate(t,e)}}async translate(e,t){if(null===document.querySelector(e))return;let o=document.querySelectorAll(e),n=-1;for(let s of o){if(n++,!this.jobIsActive(t))return;if(this.pauseMutationObserverForNode(s),await this.deleteCommentsFromNode(s),await this.resumeMutationObserverForNode(s),this.pauseMutationObserverForNode(s),s.normalize(),await this.resumeMutationObserverForNode(s),!this.jobIsActive(t))return;this.sync(t);let o=this.dom_not_translated.querySelectorAll(e)[n];o.classList.remove("notranslate"),o.classList.add("notranslate_OFF");let i=o.outerHTML;if(await this.wait("complete"===document.readyState?250:1e3),!this.jobIsActive(t))return;let a=null;if(i in this.cache?a=this.cache[i]:(a=await this.getTranslation(i),this.cache[i]=a),!1===a.success||!("data"in a)||!("input"in a.data)||!("output"in a.data)||a.data.input==a.data.output)return void this.showNode(s);if(!this.jobIsActive(t))return;if(a.data.input=a.data.input.replace("notranslate_OFF","notranslate"),a.data.output=a.data.output.replace("notranslate_OFF","notranslate"),this.sync(t),!this.jobIsActive(t))return;this.setHtmlAndKeepEventListeners(s,a.data,t),this.dom_prev=document.body.cloneNode(!0),this.sync(t),this.showNode(s)}}sync(e){let t=this.dd.diff(this.dom_prev,document.body);try{this.dd.apply(this.dom_not_translated,t)}catch(e){console.log(e)}this.dom_prev=document.body.cloneNode(!0)}jobIsActive(e){return this.jobTodo===e}setupMutationObserver(){this.observer=new MutationObserver(e=>{e.forEach(e=>{this.onDomChange(e)})}).observe(document.documentElement,{childList:!0,attributes:!1,characterData:!0,subtree:!0})}async onDomChange(e){let t=null,o=[];if(e.addedNodes.length>0?(o=e.addedNodes,t="added"):null!==e.target&&(o=[e.target],t="modified"),0!==o.length)for(let e of o)if((e.nodeType==Node.ELEMENT_NODE||e.nodeType==Node.TEXT_NODE)&&(e.nodeType==Node.TEXT_NODE&&(e=e.parentNode),null!==e)){if("added"===t){let t=this.getIncludedChildrenIfAvailable(e,this.included);null!==t&&(e=t)}this.isInsideGroup(e,this.included)&&(this.isInsideGroup(e,this.blocked)||(this.hideNode(e),e=this.getIncludedParent(e),document.body.contains(e)&&null!==e.closest("body")&&"BODY"!==e.tagName&&null===e.closest("iframe")&&(this.jobTodo=1e3+~~(9e3*Math.random()),this.translateAllDebounce())))}}async deleteCommentsFromNode(e){this.pauseMutationObserverForNode(e);let t,o=[],n=document.createTreeWalker(e,NodeFilter.SHOW_COMMENT,null,!1);for(;t=n.nextNode();)o.push(t);for(let e of o)e.remove();await this.resumeMutationObserverForNode(e)}async hideNode(e){this.pauseMutationObserverForNode(e),e.setAttribute("data-gtbabel-hide",""),await this.resumeMutationObserverForNode(e)}async showNode(e){this.pauseMutationObserverForNode(e),e.removeAttribute("data-gtbabel-hide"),null!==e.querySelectorAll("[data-gtbabel-hide]")&&e.querySelectorAll("[data-gtbabel-hide]").forEach(e=>{e.removeAttribute("data-gtbabel-hide")}),await this.resumeMutationObserverForNode(e)}setHtmlAndKeepEventListeners(e,t,o){this.pauseMutationObserverForNode(e);let n=this.dd.diff(t.input,t.output);try{this.dd.apply(e,n)}catch(e){console.log(e)}this.resumeMutationObserverForNode(e)}async wait(e=1e4){await new Promise(t=>setTimeout(()=>t(),e))}pauseMutationObserverForNode(e){this.blocked.push(e)}async resumeMutationObserverForNode(e){await new Promise(t=>requestAnimationFrame(()=>{this.blocked=this.blocked.filter(t=>t!==e),t()}))}getTranslation(e){return new Promise(t=>{let o=new URLSearchParams;o.append("html",e);let n=window.location.protocol+"//"+window.location.host+window.location.pathname;n+=(n.indexOf("?")>-1?"&":"?")+"gtbabel_translate_part=1",fetch(n,{method:"POST",body:o,cache:"no-cache"}).then(e=>e.json()).catch(e=>({success:!1,message:e})).then(e=>{t(e)})})}isInsideGroup(e,t){for(let o of t)if("string"==typeof o||o instanceof String){let t=document.querySelectorAll(o);if(0===t.length)continue;for(let o of t)if(o.contains(e))return!0}else if(o.contains(e))return!0;return!1}getIncludedParent(e){let t=document.querySelectorAll(this.included);if(0===t.length)return null;for(let o of t)if(o.contains(e))return o;return null}getIncludedChildrenIfAvailable(e,t){for(let o of t)if("string"==typeof o||o instanceof String){let t=e.querySelector(o);if(null!==t)return t}else if(e.contains(o))return o;return null}debounce(e,t,o){var n;return function(){var s=this,i=arguments,a=function(){n=null,o||e.apply(s,i)},r=o&&!n;clearTimeout(n),n=setTimeout(a,t),r&&e.apply(s,i)}}}},{"diff-dom":4}],2:[function(e,t,o){"use strict";(new(e("@babel/runtime/helpers/interopRequireDefault")(e("./DetectChanges")).default)).init()},{"./DetectChanges":1,"@babel/runtime/helpers/interopRequireDefault":3}],3:[function(e,t,o){t.exports=function(e){return e&&e.__esModule?e:{default:e}}},{}],4:[function(e,t,o){"use strict";function n(e,t,o){var s;return"#text"===e.nodeName?s=o.document.createTextNode(e.data):"#comment"===e.nodeName?s=o.document.createComment(e.data):(t?s=o.document.createElementNS("http://www.w3.org/2000/svg",e.nodeName):"svg"===e.nodeName.toLowerCase()?(s=o.document.createElementNS("http://www.w3.org/2000/svg","svg"),t=!0):s=o.document.createElement(e.nodeName),e.attributes&&Object.entries(e.attributes).forEach((function(e){var t=e[0],o=e[1];return s.setAttribute(t,o)})),e.childNodes&&e.childNodes.forEach((function(e){return s.appendChild(n(e,t,o))})),o.valueDiffing&&(e.value&&(s.value=e.value),e.checked&&(s.checked=e.checked),e.selected&&(s.selected=e.selected))),s}function s(e,t){for(t=t.slice();t.length>0;){if(!e.childNodes)return!1;var o=t.splice(0,1)[0];e=e.childNodes[o]}return e}function i(e,t,o){var i,a,r,l,c=s(e,t[o._const.route]),u={diff:t,node:c};if(o.preDiffApply(u))return!0;switch(t[o._const.action]){case o._const.addAttribute:if(!c||!c.setAttribute)return!1;c.setAttribute(t[o._const.name],t[o._const.value]);break;case o._const.modifyAttribute:if(!c||!c.setAttribute)return!1;c.setAttribute(t[o._const.name],t[o._const.newValue]),"INPUT"===c.nodeName&&"value"===t[o._const.name]&&(c.value=t[o._const.newValue]);break;case o._const.removeAttribute:if(!c||!c.removeAttribute)return!1;c.removeAttribute(t[o._const.name]);break;case o._const.modifyTextElement:if(!c||3!==c.nodeType)return!1;o.textDiff(c,c.data,t[o._const.oldValue],t[o._const.newValue]);break;case o._const.modifyValue:if(!c||void 0===c.value)return!1;c.value=t[o._const.newValue];break;case o._const.modifyComment:if(!c||void 0===c.data)return!1;o.textDiff(c,c.data,t[o._const.oldValue],t[o._const.newValue]);break;case o._const.modifyChecked:if(!c||void 0===c.checked)return!1;c.checked=t[o._const.newValue];break;case o._const.modifySelected:if(!c||void 0===c.selected)return!1;c.selected=t[o._const.newValue];break;case o._const.replaceElement:c.parentNode.replaceChild(n(t[o._const.newValue],"http://www.w3.org/2000/svg"===c.namespaceURI,o),c);break;case o._const.relocateGroup:Array.apply(void 0,new Array(t.groupLength)).map((function(){return c.removeChild(c.childNodes[t[o._const.from]])})).forEach((function(e,n){0===n&&(a=c.childNodes[t[o._const.to]]),c.insertBefore(e,a||null)}));break;case o._const.removeElement:c.parentNode.removeChild(c);break;case o._const.addElement:l=(r=t[o._const.route].slice()).splice(r.length-1,1)[0],(c=s(e,r)).insertBefore(n(t[o._const.element],"http://www.w3.org/2000/svg"===c.namespaceURI,o),c.childNodes[l]||null);break;case o._const.removeTextElement:if(!c||3!==c.nodeType)return!1;c.parentNode.removeChild(c);break;case o._const.addTextElement:if(l=(r=t[o._const.route].slice()).splice(r.length-1,1)[0],i=o.document.createTextNode(t[o._const.value]),!(c=s(e,r))||!c.childNodes)return!1;c.insertBefore(i,c.childNodes[l]||null);break;default:console.log("unknown action")}return u.newNode=i,o.postDiffApply(u),!0}function a(e,t,o){var n=e[t];e[t]=e[o],e[o]=n}Object.defineProperty(o,"__esModule",{value:!0});var r=function(e){var t=this;void 0===e&&(e={}),Object.entries(e).forEach((function(e){var o=e[0],n=e[1];return t[o]=n}))};function l(e){var t=[];return t.push(e.nodeName),"#text"!==e.nodeName&&"#comment"!==e.nodeName&&e.attributes&&(e.attributes.class&&t.push(e.nodeName+"."+e.attributes.class.replace(/ /g,".")),e.attributes.id&&t.push(e.nodeName+"#"+e.attributes.id)),t}function c(e){var t={},o={};return e.forEach((function(e){l(e).forEach((function(e){var n=e in t;n||e in o?n&&(delete t[e],o[e]=!0):t[e]=!0}))})),t}function u(e,t){var o=c(e),n=c(t),s={};return Object.keys(o).forEach((function(e){n[e]&&(s[e]=!0)})),s}function d(e){return delete e.outerDone,delete e.innerDone,delete e.valueDone,!e.childNodes||e.childNodes.every(d)}function h(e,t){if(!["nodeName","value","checked","selected","data"].every((function(o){return e[o]===t[o]})))return!1;if(Boolean(e.attributes)!==Boolean(t.attributes))return!1;if(Boolean(e.childNodes)!==Boolean(t.childNodes))return!1;if(e.attributes){var o=Object.keys(e.attributes),n=Object.keys(t.attributes);if(o.length!==n.length)return!1;if(!o.every((function(o){return e.attributes[o]===t.attributes[o]})))return!1}if(e.childNodes){if(e.childNodes.length!==t.childNodes.length)return!1;if(!e.childNodes.every((function(e,o){return h(e,t.childNodes[o])})))return!1}return!0}function f(e,t,o,n,s){if(!e||!t)return!1;if(e.nodeName!==t.nodeName)return!1;if("#text"===e.nodeName)return!!s||e.data===t.data;if(e.nodeName in o)return!0;if(e.attributes&&t.attributes){if(e.attributes.id){if(e.attributes.id!==t.attributes.id)return!1;if(e.nodeName+"#"+e.attributes.id in o)return!0}if(e.attributes.class&&e.attributes.class===t.attributes.class&&e.nodeName+"."+e.attributes.class.replace(/ /g,".")in o)return!0}if(n)return!0;var i=e.childNodes?e.childNodes.slice().reverse():[],a=t.childNodes?t.childNodes.slice().reverse():[];if(i.length!==a.length)return!1;if(s)return i.every((function(e,t){return e.nodeName===a[t].nodeName}));var r=u(i,a);return i.every((function(e,t){return f(e,a[t],r,!0,!0)}))}function p(e){return JSON.parse(JSON.stringify(e))}function m(e,t,o,n){var s=0,i=[],a=e.length,r=t.length,c=Array.apply(void 0,new Array(a+1)).map((function(){return[]})),d=u(e,t),h=a===r;h&&e.some((function(e,o){var n=l(e),s=l(t[o]);return n.length!==s.length?(h=!1,!0):(n.some((function(e,t){if(e!==s[t])return h=!1,!0})),!h||void 0)}));for(var p=0;p<a;p++)for(var m=e[p],_=0;_<r;_++){var g=t[_];o[p]||n[_]||!f(m,g,d,h)?c[p+1][_+1]=0:(c[p+1][_+1]=c[p][_]?c[p][_]+1:1,c[p+1][_+1]>=s&&(s=c[p+1][_+1],i=[p+1,_+1]))}return 0!==s&&{oldValue:i[0]-s,newValue:i[1]-s,length:s}}function _(e,t){return Array.apply(void 0,new Array(e)).map((function(){return t}))}r.prototype.toString=function(){return JSON.stringify(this)},r.prototype.setValue=function(e,t){return this[e]=t,this};var g=function(){this.list=[]};function v(e,t){var o,n,s=e;for(t=t.slice();t.length>0;){if(!s.childNodes)return!1;n=t.splice(0,1)[0],o=s,s=s.childNodes[n]}return{node:s,parentNode:o,nodeIndex:n}}function b(e,t,o){return t.forEach((function(t){!function(e,t,o){var n,s,i,a=v(e,t[o._const.route]),r=a.node,l=a.parentNode,c=a.nodeIndex,u=[],d={diff:t,node:r};if(o.preVirtualDiffApply(d))return!0;switch(t[o._const.action]){case o._const.addAttribute:r.attributes||(r.attributes={}),r.attributes[t[o._const.name]]=t[o._const.value],"checked"===t[o._const.name]?r.checked=!0:"selected"===t[o._const.name]?r.selected=!0:"INPUT"===r.nodeName&&"value"===t[o._const.name]&&(r.value=t[o._const.value]);break;case o._const.modifyAttribute:r.attributes[t[o._const.name]]=t[o._const.newValue];break;case o._const.removeAttribute:delete r.attributes[t[o._const.name]],0===Object.keys(r.attributes).length&&delete r.attributes,"checked"===t[o._const.name]?r.checked=!1:"selected"===t[o._const.name]?delete r.selected:"INPUT"===r.nodeName&&"value"===t[o._const.name]&&delete r.value;break;case o._const.modifyTextElement:r.data=t[o._const.newValue];break;case o._const.modifyValue:r.value=t[o._const.newValue];break;case o._const.modifyComment:r.data=t[o._const.newValue];break;case o._const.modifyChecked:r.checked=t[o._const.newValue];break;case o._const.modifySelected:r.selected=t[o._const.newValue];break;case o._const.replaceElement:(n=p(t[o._const.newValue])).outerDone=!0,n.innerDone=!0,n.valueDone=!0,l.childNodes[c]=n;break;case o._const.relocateGroup:r.childNodes.splice(t[o._const.from],t.groupLength).reverse().forEach((function(e){return r.childNodes.splice(t[o._const.to],0,e)})),r.subsets&&r.subsets.forEach((function(e){if(t[o._const.from]<t[o._const.to]&&e.oldValue<=t[o._const.to]&&e.oldValue>t[o._const.from]){e.oldValue-=t.groupLength;var n=e.oldValue+e.length-t[o._const.to];n>0&&(u.push({oldValue:t[o._const.to]+t.groupLength,newValue:e.newValue+e.length-n,length:n}),e.length-=n)}else if(t[o._const.from]>t[o._const.to]&&e.oldValue>t[o._const.to]&&e.oldValue<t[o._const.from]){e.oldValue+=t.groupLength;var s=e.oldValue+e.length-t[o._const.to];s>0&&(u.push({oldValue:t[o._const.to]+t.groupLength,newValue:e.newValue+e.length-s,length:s}),e.length-=s)}else e.oldValue===t[o._const.from]&&(e.oldValue=t[o._const.to])}));break;case o._const.removeElement:l.childNodes.splice(c,1),l.subsets&&l.subsets.forEach((function(e){e.oldValue>c?e.oldValue-=1:e.oldValue===c?e.delete=!0:e.oldValue<c&&e.oldValue+e.length>c&&(e.oldValue+e.length-1===c?e.length--:(u.push({newValue:e.newValue+c-e.oldValue,oldValue:c,length:e.length-c+e.oldValue-1}),e.length=c-e.oldValue))})),r=l;break;case o._const.addElement:s=t[o._const.route].slice(),i=s.splice(s.length-1,1)[0],r=v(e,s).node,(n=p(t[o._const.element])).outerDone=!0,n.innerDone=!0,n.valueDone=!0,r.childNodes||(r.childNodes=[]),i>=r.childNodes.length?r.childNodes.push(n):r.childNodes.splice(i,0,n),r.subsets&&r.subsets.forEach((function(e){if(e.oldValue>=i)e.oldValue+=1;else if(e.oldValue<i&&e.oldValue+e.length>i){var t=e.oldValue+e.length-i;u.push({newValue:e.newValue+e.length-t,oldValue:i+1,length:t}),e.length-=t}}));break;case o._const.removeTextElement:l.childNodes.splice(c,1),"TEXTAREA"===l.nodeName&&delete l.value,l.subsets&&l.subsets.forEach((function(e){e.oldValue>c?e.oldValue-=1:e.oldValue===c?e.delete=!0:e.oldValue<c&&e.oldValue+e.length>c&&(e.oldValue+e.length-1===c?e.length--:(u.push({newValue:e.newValue+c-e.oldValue,oldValue:c,length:e.length-c+e.oldValue-1}),e.length=c-e.oldValue))})),r=l;break;case o._const.addTextElement:s=t[o._const.route].slice(),i=s.splice(s.length-1,1)[0],(n={}).nodeName="#text",n.data=t[o._const.value],(r=v(e,s).node).childNodes||(r.childNodes=[]),i>=r.childNodes.length?r.childNodes.push(n):r.childNodes.splice(i,0,n),"TEXTAREA"===r.nodeName&&(r.value=t[o._const.newValue]),r.subsets&&r.subsets.forEach((function(e){if(e.oldValue>=i&&(e.oldValue+=1),e.oldValue<i&&e.oldValue+e.length>i){var t=e.oldValue+e.length-i;u.push({newValue:e.newValue+e.length-t,oldValue:i+1,length:t}),e.length-=t}}));break;default:console.log("unknown action")}r.subsets&&(r.subsets=r.subsets.filter((function(e){return!e.delete&&e.oldValue!==e.newValue})),u.length&&(r.subsets=r.subsets.concat(u))),d.newNode=n,o.postVirtualDiffApply(d)}(e,t,o)})),!0}function V(e,t){void 0===t&&(t={});var o={};return o.nodeName=e.nodeName,"#text"===o.nodeName||"#comment"===o.nodeName?o.data=e.data:(e.attributes&&e.attributes.length>0&&(o.attributes={},Array.prototype.slice.call(e.attributes).forEach((function(e){return o.attributes[e.name]=e.value}))),"TEXTAREA"===o.nodeName?o.value=e.value:e.childNodes&&e.childNodes.length>0&&(o.childNodes=[],Array.prototype.slice.call(e.childNodes).forEach((function(e){return o.childNodes.push(V(e,t))}))),t.valueDiffing&&(void 0!==e.checked&&e.type&&["radio","checkbox"].includes(e.type.toLowerCase())?o.checked=e.checked:void 0!==e.value&&(o.value=e.value),void 0!==e.selected&&(o.selected=e.selected))),o}g.prototype.add=function(e){var t;(t=this.list).push.apply(t,e)},g.prototype.forEach=function(e){this.list.forEach((function(t){return e(t)}))};var N=/<(?:"[^"]*"['"]*|'[^']*'['"]*|[^'">])+>/g,y=Object.create?Object.create(null):{},w=/\s([^'"/\s><]+?)[\s/>]|([^\s=]+)=\s?(".*?"|'.*?')/g;function E(e){return e.replace(/&lt;/g,"<").replace(/&gt;/g,">").replace(/&amp;/g,"&")}var k={area:!0,base:!0,br:!0,col:!0,embed:!0,hr:!0,img:!0,input:!0,keygen:!0,link:!0,menuItem:!0,meta:!0,param:!0,source:!0,track:!0,wbr:!0};function A(e){var t={nodeName:"",attributes:{}},o=e.match(/<\/?([^\s]+?)[/\s>]/);if(o&&(t.nodeName=o[1].toUpperCase(),(k[o[1].toLowerCase()]||"/"===e.charAt(e.length-2))&&(t.voidElement=!0),t.nodeName.startsWith("!--"))){var n=e.indexOf("--\x3e");return{type:"comment",data:-1!==n?e.slice(4,n):""}}for(var s=new RegExp(w),i=null,a=!1;!a;)if(null===(i=s.exec(e)))a=!0;else if(i[0].trim())if(i[1]){var r=i[1].trim(),l=[r,""];r.indexOf("=")>-1&&(l=r.split("=")),t.attributes[l[0]]=l[1],s.lastIndex--}else i[2]&&(t.attributes[i[2]]=i[3].trim().substring(1,i[3].length-1));return t}function O(e){return function e(t){return delete t.voidElement,t.childNodes&&t.childNodes.forEach((function(t){return e(t)})),t}(function(e,t){void 0===t&&(t={components:y});var o,n=[],s=-1,i=[],a=!1;return e.replace(N,(function(r,l){if(a){if(r!=="</"+o.nodeName+">")return;a=!1}var c,u="/"!==r.charAt(1),d=r.startsWith("\x3c!--"),h=l+r.length,f=e.charAt(h);if(d){var p=A(r);return s<0?(n.push(p),n):((c=i[s])&&(c.childNodes||(c.childNodes=[]),c.childNodes.push(p)),n)}if(u&&(o=A(r),s++,"tag"===o.type&&t.components[o.nodeName]&&(o.type="component",a=!0),o.voidElement||a||!f||"<"===f||(o.childNodes||(o.childNodes=[]),o.childNodes.push({nodeName:"#text",data:E(e.slice(h,e.indexOf("<",h)))})),0===s&&n.push(o),(c=i[s-1])&&(c.childNodes||(c.childNodes=[]),c.childNodes.push(o)),i[s]=o),(!u||o.voidElement)&&(s--,!a&&"<"!==f&&f)){c=-1===s?n:i[s].childNodes||[];var m=e.indexOf("<",h),_=E(e.slice(h,-1===m?void 0:m));c.push({nodeName:"#text",data:_})}})),n[0]}(e))}var x=function(e,t,o){this.options=o,this.t1=e instanceof HTMLElement?V(e,this.options):"string"==typeof e?O(e,this.options):JSON.parse(JSON.stringify(e)),this.t2=t instanceof HTMLElement?V(t,this.options):"string"==typeof t?O(t,this.options):JSON.parse(JSON.stringify(t)),this.diffcount=0,this.foundAll=!1,this.debug&&(this.t1Orig=V(e,this.options),this.t2Orig=V(t,this.options)),this.tracker=new g};x.prototype.init=function(){return this.findDiffs(this.t1,this.t2)},x.prototype.findDiffs=function(e,t){var o;do{if(this.options.debug&&(this.diffcount+=1,this.diffcount>this.options.diffcap))throw window.diffError=[this.t1Orig,this.t2Orig],new Error("surpassed diffcap:"+JSON.stringify(this.t1Orig)+" -> "+JSON.stringify(this.t2Orig));0===(o=this.findNextDiff(e,t,[])).length&&(h(e,t)||(this.foundAll?console.error("Could not find remaining diffs!"):(this.foundAll=!0,d(e),o=this.findNextDiff(e,t,[])))),o.length>0&&(this.foundAll=!1,this.tracker.add(o),b(e,o,this.options))}while(o.length>0);return this.tracker.list},x.prototype.findNextDiff=function(e,t,o){var n,s;if(this.options.maxDepth&&o.length>this.options.maxDepth)return[];if(!e.outerDone){if(n=this.findOuterDiff(e,t,o),this.options.filterOuterDiff&&(s=this.options.filterOuterDiff(e,t,n))&&(n=s),n.length>0)return e.outerDone=!0,n;e.outerDone=!0}if(!e.innerDone){if((n=this.findInnerDiff(e,t,o)).length>0)return n;e.innerDone=!0}if(this.options.valueDiffing&&!e.valueDone){if((n=this.findValueDiff(e,t,o)).length>0)return e.valueDone=!0,n;e.valueDone=!0}return[]},x.prototype.findOuterDiff=function(e,t,o){var n,s,i,a,l,c,u=[];if(e.nodeName!==t.nodeName){if(!o.length)throw new Error("Top level nodes have to be of the same kind.");return[(new r).setValue(this.options._const.action,this.options._const.replaceElement).setValue(this.options._const.oldValue,p(e)).setValue(this.options._const.newValue,p(t)).setValue(this.options._const.route,o)]}if(o.length&&this.options.maxNodeDiffCount<Math.abs((e.childNodes||[]).length-(t.childNodes||[]).length))return[(new r).setValue(this.options._const.action,this.options._const.replaceElement).setValue(this.options._const.oldValue,p(e)).setValue(this.options._const.newValue,p(t)).setValue(this.options._const.route,o)];if(e.data!==t.data)return"#text"===e.nodeName?[(new r).setValue(this.options._const.action,this.options._const.modifyTextElement).setValue(this.options._const.route,o).setValue(this.options._const.oldValue,e.data).setValue(this.options._const.newValue,t.data)]:[(new r).setValue(this.options._const.action,this.options._const.modifyComment).setValue(this.options._const.route,o).setValue(this.options._const.oldValue,e.data).setValue(this.options._const.newValue,t.data)];for(s=e.attributes?Object.keys(e.attributes).sort():[],i=t.attributes?Object.keys(t.attributes).sort():[],a=s.length,c=0;c<a;c++)n=s[c],-1===(l=i.indexOf(n))?u.push((new r).setValue(this.options._const.action,this.options._const.removeAttribute).setValue(this.options._const.route,o).setValue(this.options._const.name,n).setValue(this.options._const.value,e.attributes[n])):(i.splice(l,1),e.attributes[n]!==t.attributes[n]&&u.push((new r).setValue(this.options._const.action,this.options._const.modifyAttribute).setValue(this.options._const.route,o).setValue(this.options._const.name,n).setValue(this.options._const.oldValue,e.attributes[n]).setValue(this.options._const.newValue,t.attributes[n])));for(a=i.length,c=0;c<a;c++)n=i[c],u.push((new r).setValue(this.options._const.action,this.options._const.addAttribute).setValue(this.options._const.route,o).setValue(this.options._const.name,n).setValue(this.options._const.value,t.attributes[n]));return u},x.prototype.findInnerDiff=function(e,t,o){var n=e.childNodes?e.childNodes.slice():[],s=t.childNodes?t.childNodes.slice():[],i=Math.max(n.length,s.length),a=Math.abs(n.length-s.length),l=[],c=0;if(!this.options.maxChildCount||i<this.options.maxChildCount){var u=e.subsets&&e.subsetsAge--?e.subsets:e.childNodes&&t.childNodes?function(e,t){for(var o=e.childNodes?e.childNodes:[],n=t.childNodes?t.childNodes:[],s=_(o.length,!1),i=_(n.length,!1),a=[],r=!0,l=function(){return arguments[1]};r;)(r=m(o,n,s,i))&&(a.push(r),Array.apply(void 0,new Array(r.length)).map(l).forEach((function(e){return t=e,s[r.oldValue+t]=!0,void(i[r.newValue+t]=!0);var t})));return e.subsets=a,e.subsetsAge=100,a}(e,t):[];if(u.length>0&&(l=this.attemptGroupRelocation(e,t,u,o)).length>0)return l}for(var d=0;d<i;d+=1){var f=n[d],g=s[d];a&&(f&&!g?"#text"===f.nodeName?(l.push((new r).setValue(this.options._const.action,this.options._const.removeTextElement).setValue(this.options._const.route,o.concat(c)).setValue(this.options._const.value,f.data)),c-=1):(l.push((new r).setValue(this.options._const.action,this.options._const.removeElement).setValue(this.options._const.route,o.concat(c)).setValue(this.options._const.element,p(f))),c-=1):g&&!f&&("#text"===g.nodeName?l.push((new r).setValue(this.options._const.action,this.options._const.addTextElement).setValue(this.options._const.route,o.concat(c)).setValue(this.options._const.value,g.data)):l.push((new r).setValue(this.options._const.action,this.options._const.addElement).setValue(this.options._const.route,o.concat(c)).setValue(this.options._const.element,p(g))))),f&&g&&(!this.options.maxChildCount||i<this.options.maxChildCount?l=l.concat(this.findNextDiff(f,g,o.concat(c))):h(f,g)||(n.length>s.length?(l=l.concat([(new r).setValue(this.options._const.action,this.options._const.removeElement).setValue(this.options._const.element,p(f)).setValue(this.options._const.route,o.concat(c))]),n.splice(d,1),c-=1,a-=1):n.length<s.length?(l=l.concat([(new r).setValue(this.options._const.action,this.options._const.addElement).setValue(this.options._const.element,p(g)).setValue(this.options._const.route,o.concat(c))]),n.splice(d,0,{}),a-=1):l=l.concat([(new r).setValue(this.options._const.action,this.options._const.replaceElement).setValue(this.options._const.oldValue,p(f)).setValue(this.options._const.newValue,p(g)).setValue(this.options._const.route,o.concat(c))]))),c+=1}return e.innerDone=!0,l},x.prototype.attemptGroupRelocation=function(e,t,o,n){for(var s,i,a,l,c,u,d=function(e,t,o){var n=e.childNodes?_(e.childNodes.length,!0):[],s=t.childNodes?_(t.childNodes.length,!0):[],i=0;return o.forEach((function(e){for(var t=e.oldValue+e.length,o=e.newValue+e.length,a=e.oldValue;a<t;a+=1)n[a]=i;for(var r=e.newValue;r<o;r+=1)s[r]=i;i+=1})),{gaps1:n,gaps2:s}}(e,t,o),h=d.gaps1,m=d.gaps2,g=Math.min(h.length,m.length),v=[],b=0,V=0;b<g;V+=1,b+=1)if(!0===h[b])if("#text"===(l=e.childNodes[V]).nodeName)if("#text"===t.childNodes[b].nodeName){if(l.data!==t.childNodes[b].data){for(u=V;e.childNodes.length>u+1&&"#text"===e.childNodes[u+1].nodeName;)if(u+=1,t.childNodes[b].data===e.childNodes[u].data){c=!0;break}if(!c)return v.push((new r).setValue(this.options._const.action,this.options._const.modifyTextElement).setValue(this.options._const.route,n.concat(b)).setValue(this.options._const.oldValue,l.data).setValue(this.options._const.newValue,t.childNodes[b].data)),v}}else v.push((new r).setValue(this.options._const.action,this.options._const.removeTextElement).setValue(this.options._const.route,n.concat(b)).setValue(this.options._const.value,l.data)),h.splice(b,1),g=Math.min(h.length,m.length),b-=1;else v.push((new r).setValue(this.options._const.action,this.options._const.removeElement).setValue(this.options._const.route,n.concat(b)).setValue(this.options._const.element,p(l))),h.splice(b,1),g=Math.min(h.length,m.length),b-=1;else if(!0===m[b])"#text"===(l=t.childNodes[b]).nodeName?(v.push((new r).setValue(this.options._const.action,this.options._const.addTextElement).setValue(this.options._const.route,n.concat(b)).setValue(this.options._const.value,l.data)),h.splice(b,0,!0),g=Math.min(h.length,m.length),V-=1):(v.push((new r).setValue(this.options._const.action,this.options._const.addElement).setValue(this.options._const.route,n.concat(b)).setValue(this.options._const.element,p(l))),h.splice(b,0,!0),g=Math.min(h.length,m.length),V-=1);else if(h[b]!==m[b]){if(v.length>0)return v;if(a=o[h[b]],(i=Math.min(a.newValue,e.childNodes.length-a.length))!==a.oldValue){s=!1;for(var N=0;N<a.length;N+=1)f(e.childNodes[i+N],e.childNodes[a.oldValue+N],[],!1,!0)||(s=!0);if(s)return[(new r).setValue(this.options._const.action,this.options._const.relocateGroup).setValue("groupLength",a.length).setValue(this.options._const.from,a.oldValue).setValue(this.options._const.to,i).setValue(this.options._const.route,n)]}}return v},x.prototype.findValueDiff=function(e,t,o){var n=[];return e.selected!==t.selected&&n.push((new r).setValue(this.options._const.action,this.options._const.modifySelected).setValue(this.options._const.oldValue,e.selected).setValue(this.options._const.newValue,t.selected).setValue(this.options._const.route,o)),(e.value||t.value)&&e.value!==t.value&&"OPTION"!==e.nodeName&&n.push((new r).setValue(this.options._const.action,this.options._const.modifyValue).setValue(this.options._const.oldValue,e.value||"").setValue(this.options._const.newValue,t.value||"").setValue(this.options._const.route,o)),e.checked!==t.checked&&n.push((new r).setValue(this.options._const.action,this.options._const.modifyChecked).setValue(this.options._const.oldValue,e.checked).setValue(this.options._const.newValue,t.checked).setValue(this.options._const.route,o)),n};var D={debug:!1,diffcap:10,maxDepth:!1,maxChildCount:50,valueDiffing:!0,textDiff:function(e,t,o,n){e.data=n},preVirtualDiffApply:function(){},postVirtualDiffApply:function(){},preDiffApply:function(){},postDiffApply:function(){},filterOuterDiff:null,compress:!1,_const:!1,document:!(!window||!window.document)&&window.document},T=function(e){var t=this;if(void 0===e&&(e={}),this.options=e,Object.entries(D).forEach((function(e){var o=e[0],n=e[1];Object.prototype.hasOwnProperty.call(t.options,o)||(t.options[o]=n)})),!this.options._const){var o=["addAttribute","modifyAttribute","removeAttribute","modifyTextElement","relocateGroup","removeElement","addElement","removeTextElement","addTextElement","replaceElement","modifyValue","modifyChecked","modifySelected","modifyComment","action","route","oldValue","newValue","element","group","from","to","name","value","data","attributes","nodeName","childNodes","checked","selected"];this.options._const={},this.options.compress?o.forEach((function(e,o){return t.options._const[e]=o})):o.forEach((function(e){return t.options._const[e]=e}))}this.DiffFinder=x};T.prototype.apply=function(e,t){return function(e,t,o){return t.every((function(t){return i(e,t,o)}))}(e,t,this.options)},T.prototype.undo=function(e,t){return function(e,t,o){t.length||(t=[t]),(t=t.slice()).reverse(),t.forEach((function(t){!function(e,t,o){switch(t[o._const.action]){case o._const.addAttribute:t[o._const.action]=o._const.removeAttribute,i(e,t,o);break;case o._const.modifyAttribute:a(t,o._const.oldValue,o._const.newValue),i(e,t,o);break;case o._const.removeAttribute:t[o._const.action]=o._const.addAttribute,i(e,t,o);break;case o._const.modifyTextElement:case o._const.modifyValue:case o._const.modifyComment:case o._const.modifyChecked:case o._const.modifySelected:case o._const.replaceElement:a(t,o._const.oldValue,o._const.newValue),i(e,t,o);break;case o._const.relocateGroup:a(t,o._const.from,o._const.to),i(e,t,o);break;case o._const.removeElement:t[o._const.action]=o._const.addElement,i(e,t,o);break;case o._const.addElement:t[o._const.action]=o._const.removeElement,i(e,t,o);break;case o._const.removeTextElement:t[o._const.action]=o._const.addTextElement,i(e,t,o);break;case o._const.addTextElement:t[o._const.action]=o._const.removeTextElement,i(e,t,o);break;default:console.log("unknown action")}}(e,t,o)}))}(e,t,this.options)},T.prototype.diff=function(e,t){return new this.DiffFinder(e,t,this.options).init()};var C=function(e){var t=this;void 0===e&&(e={}),this.pad="│   ",this.padding="",this.tick=1,this.messages=[];var o=function(e,o){var n=e[o];e[o]=function(){for(var s=[],i=arguments.length;i--;)s[i]=arguments[i];t.fin(o,Array.prototype.slice.call(s));var a=n.apply(e,s);return t.fout(o,a),a}};for(var n in e)"function"==typeof e[n]&&o(e,n);this.log("┌ TRACELOG START")};C.prototype.fin=function(e,t){this.padding+=this.pad,this.log("├─> entering "+e,t)},C.prototype.fout=function(e,t){this.log("│<──┘ generated return value",t),this.padding=this.padding.substring(0,this.padding.length-this.pad.length)},C.prototype.format=function(e,t){return function(e){for(e=""+e;e.length<4;)e="0"+e;return e}(t)+"> "+this.padding+e},C.prototype.log=function(){var e=Array.prototype.slice.call(arguments),t=function(e){return e?"string"==typeof e?e:e instanceof HTMLElement?e.outerHTML||"<empty>":e instanceof Array?"["+e.map(t).join(",")+"]":e.toString()||e.valueOf()||"<unknown>":"<falsey>"};e=e.map(t).join(", "),this.messages.push(this.format(e,this.tick++))},C.prototype.toString=function(){for(var e="└───";e.length<=this.padding.length+this.pad.length;)e+="×   ";var t=this.padding;return this.padding="",e=this.format(e,this.tick),this.padding=t,this.messages.join("\n")+"\n"+e},o.DiffDOM=T,o.TraceLogger=C,o.nodeToObj=V,o.stringToObj=O},{}]},{},[2]);