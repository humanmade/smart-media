!function(t,e){for(var i in e)t[i]=e[i]}(this,function(t){var e={};function i(o){if(e[o])return e[o].exports;var n=e[o]={i:o,l:!1,exports:{}};return t[o].call(n.exports,n,n.exports,i),n.l=!0,n.exports}return i.m=t,i.c=e,i.d=function(t,e,o){i.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:o})},i.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},i.t=function(t,e){if(1&e&&(t=i(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var o=Object.create(null);if(i.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var n in t)i.d(o,n,function(e){return t[e]}.bind(null,n));return o},i.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return i.d(e,"a",e),e},i.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},i.p="/",i(i.s=13)}([function(t,e){!function(){t.exports=this.wp.media}()},function(t,e){!function(){t.exports=this.wp.template}()},function(t,e){!function(){t.exports=this.wp.hooks}()},function(t,e){!function(){t.exports=this.wp.i18n}()},function(t,e){!function(){t.exports=this.wp.ajax}()},function(t,e){!function(){t.exports=this.jQuery}()},function(t,e,i){var o;!function(){"use strict";var n={};function r(t,e,i){for(var o=i.data,n=i.width,r=~~t.x,a=~~(t.x+t.width),s=~~t.y,l=~~(t.y+t.height),c=255*t.weight,d=s;d<l;d++)for(var h=r;h<a;h++){o[4*(d*n+h)+3]+=c}}function a(t,e,i){for(var o={detail:0,saturation:0,skin:0,boost:0,total:0},n=e.data,r=t.scoreDownSample,a=1/r,l=e.height*r,c=e.width*r,d=e.width,h=0;h<l;h+=r)for(var u=0;u<c;u+=r){var p=4*(~~(h*a)*d+~~(u*a)),m=s(t,i,u,h),g=n[p+1]/255;o.skin+=n[p]/255*(g+t.skinBias)*m,o.detail+=g*m,o.saturation+=n[p+2]/255*(g+t.saturationBias)*m,o.boost+=n[p+3]/255*m}return o.total=(o.detail*t.detailWeight+o.skin*t.skinWeight+o.saturation*t.saturationWeight+o.boost*t.boostWeight)/(i.width*i.height),o}function s(t,e,i,o){if(e.x>i||i>=e.x+e.width||e.y>o||o>=e.y+e.height)return t.outsideImportance;i=(i-e.x)/e.width,o=(o-e.y)/e.height;var n=2*g(.5-i),r=2*g(.5-o),a=Math.max(n-1+t.edgeRadius,0),s=Math.max(r-1+t.edgeRadius,0),l=(a*a+s*s)*t.edgeWeight,c=1.41-f(n*n+r*r);return t.ruleOfThirds&&(c+=1.2*Math.max(0,c+l+.5)*(w(n)+w(r))),c+l}function l(t,e,i,o){var n=f(e*e+i*i+o*o),r=e/n-t.skinColor[0],a=i/n-t.skinColor[1],s=o/n-t.skinColor[2];return 1-f(r*r+a*a+s*s)}function c(t,e,i){this.width=t,this.height=e,this.data=i?new Uint8ClampedArray(i):new Uint8ClampedArray(t*e*4)}function d(t,e){for(var i=t.data,o=t.width,n=Math.floor(t.width/e),r=Math.floor(t.height/e),a=new c(n,r),s=a.data,l=1/(e*e),d=0;d<r;d++)for(var h=0;h<n;h++){for(var u=4*(d*n+h),p=0,m=0,g=0,f=0,v=0,w=0,b=0;b<e;b++)for(var y=0;y<e;y++){var x=4*((d*e+b)*o+(h*e+y));p+=i[x],m+=i[x+1],g+=i[x+2],f+=i[x+3],v=Math.max(v,i[x]),w=Math.max(w,i[x+1])}s[u]=p*l*.5+.5*v,s[u+1]=m*l*.7+.3*w,s[u+2]=g*l,s[u+3]=f*l}return a}function h(t,e){var i=document.createElement("canvas");return i.width=t,i.height=e,i}function u(t){return{open:function(e){var i=e.naturalWidth||e.width,o=e.naturalHeight||e.height,r=t(i,o),a=r.getContext("2d");return!e.naturalWidth||e.naturalWidth==e.width&&e.naturalHeight==e.height?(r.width=e.width,r.height=e.height):(r.width=e.naturalWidth,r.height=e.naturalHeight),a.drawImage(e,0,0),n.Promise.resolve(r)},resample:function(e,i,o){return Promise.resolve(e).then((function(e){var r=t(~~i,~~o);return r.getContext("2d").drawImage(e,0,0,e.width,e.height,0,0,r.width,r.height),n.Promise.resolve(r)}))},getData:function(t){return Promise.resolve(t).then((function(t){var e=t.getContext("2d").getImageData(0,0,t.width,t.height);return new c(t.width,t.height,e.data)}))}}}n.Promise="undefined"!=typeof Promise?Promise:function(){throw new Error("No native promises and smartcrop.Promise not set.")},n.DEFAULTS={width:0,height:0,aspect:0,cropWidth:0,cropHeight:0,detailWeight:.2,skinColor:[.78,.57,.44],skinBias:.01,skinBrightnessMin:.2,skinBrightnessMax:1,skinThreshold:.8,skinWeight:1.8,saturationBrightnessMin:.05,saturationBrightnessMax:.9,saturationThreshold:.4,saturationBias:.2,saturationWeight:.1,scoreDownSample:8,step:8,scaleStep:.1,minScale:1,maxScale:1,edgeRadius:.4,edgeWeight:-20,outsideImportance:-.5,boostWeight:100,ruleOfThirds:!0,prescale:!0,imageOperations:null,canvasFactory:h,debug:!1},n.crop=function(t,e,i){var o=v({},n.DEFAULTS,e);o.aspect&&(o.width=o.aspect,o.height=1),null===o.imageOperations&&(o.imageOperations=u(o.canvasFactory));var s=o.imageOperations,h=1,g=1;return s.open(t,o.input).then((function(t){return o.width&&o.height&&(h=p(t.width/o.width,t.height/o.height),o.cropWidth=~~(o.width*h),o.cropHeight=~~(o.height*h),o.minScale=p(o.maxScale,m(1/h,o.minScale)),!1!==o.prescale&&((g=p(m(256/t.width,256/t.height),1))<1?(t=s.resample(t,t.width*g,t.height*g),o.cropWidth=~~(o.cropWidth*g),o.cropHeight=~~(o.cropHeight*g),o.boost&&(o.boost=o.boost.map((function(t){return{x:~~(t.x*g),y:~~(t.y*g),width:~~(t.width*g),height:~~(t.height*g),weight:t.weight}})))):g=1)),t})).then((function(t){return s.getData(t).then((function(t){for(var e=function(t,e){var i={},o=new c(e.width,e.height);(function(t,e){for(var i=t.data,o=e.data,n=t.width,r=t.height,a=0;a<r;a++)for(var s=0;s<n;s++){var l,c=4*(a*n+s);l=0===s||s>=n-1||0===a||a>=r-1?y(i,c):4*y(i,c)-y(i,c-4*n)-y(i,c-4)-y(i,c+4)-y(i,c+4*n),o[c+1]=l}})(e,o),function(t,e,i){for(var o=e.data,n=i.data,r=e.width,a=e.height,s=0;s<a;s++)for(var c=0;c<r;c++){var d=4*(s*r+c),h=b(o[d],o[d+1],o[d+2])/255,u=l(t,o[d],o[d+1],o[d+2]),p=u>t.skinThreshold,m=h>=t.skinBrightnessMin&&h<=t.skinBrightnessMax;n[d]=p&&m?(u-t.skinThreshold)*(255/(1-t.skinThreshold)):0}}(t,e,o),function(t,e,i){for(var o=e.data,n=i.data,r=e.width,a=e.height,s=0;s<a;s++)for(var l=0;l<r;l++){var c=4*(s*r+l),d=b(o[c],o[c+1],o[c+2])/255,h=x(o[c],o[c+1],o[c+2]),u=h>t.saturationThreshold,p=d>=t.saturationBrightnessMin&&d<=t.saturationBrightnessMax;n[c+2]=p&&u?(h-t.saturationThreshold)*(255/(1-t.saturationThreshold)):0}}(t,e,o),function(t,e){if(!t.boost)return;for(var i=e.data,o=0;o<e.width;o+=4)i[o+3]=0;for(o=0;o<t.boost.length;o++)r(t.boost[o],t,e)}(t,o);for(var n=d(o,t.scoreDownSample),s=-1/0,h=null,u=function(t,e,i){for(var o=[],n=p(e,i),r=t.cropWidth||n,a=t.cropHeight||n,s=t.maxScale;s>=t.minScale;s-=t.scaleStep)for(var l=0;l+a*s<=i;l+=t.step)for(var c=0;c+r*s<=e;c+=t.step)o.push({x:c,y:l,width:r*s,height:a*s});return o}(t,e.width,e.height),m=0,g=u.length;m<g;m++){var f=u[m];f.score=a(t,n,f),f.score.total>s&&(h=f,s=f.score.total)}i.topCrop=h,t.debug&&h&&(i.crops=u,i.debugOutput=o,i.debugOptions=t,i.debugTopCrop=v({},i.topCrop));return i}(o,t),n=e.crops||[e.topCrop],s=0,h=n.length;s<h;s++){var u=n[s];u.x=~~(u.x/g),u.y=~~(u.y/g),u.width=~~(u.width/g),u.height=~~(u.height/g)}return i&&i(e),e}))}))},n.isAvailable=function(t){if(!n.Promise)return!1;if((t?t.canvasFactory:h)===h&&!document.createElement("canvas").getContext("2d"))return!1;return!0},n.importance=s,n.ImgData=c,n._downSample=d,n._canvasImageOperations=u;var p=Math.min,m=Math.max,g=Math.abs,f=Math.sqrt;function v(t){for(var e=1,i=arguments.length;e<i;e++){var o=arguments[e];if(o)for(var n in o)t[n]=o[n]}return t}function w(t){return t=16*((t-1/3+1)%2*.5-.5),Math.max(1-t*t,0)}function b(t,e,i){return.5126*i+.7152*e+.0722*t}function y(t,e){return b(t[e],t[e+1],t[e+2])}function x(t,e,i){var o=m(t/255,e/255,i/255),n=p(t/255,e/255,i/255);if(o===n)return 0;var r=o-n;return(o+n)/2>.5?r/(2-o-n):r/(o+n)}void 0===(o=function(){return n}.call(e,i,e,t))||(t.exports=o),e.smartcrop=n,t.exports=n}()},function(t,e){!function(){t.exports=this._}()},function(t,e){!function(){t.exports=this.wp.BackBone}()},function(t,e,i){var o=i(10),n=i(11);"string"==typeof(n=n.__esModule?n.default:n)&&(n=[[t.i,n,""]]);var r={insert:"head",singleton:!1};o(n,r);t.exports=n.locals||{}},function(t,e,i){"use strict";var o,n=function(){return void 0===o&&(o=Boolean(window&&document&&document.all&&!window.atob)),o},r=function(){var t={};return function(e){if(void 0===t[e]){var i=document.querySelector(e);if(window.HTMLIFrameElement&&i instanceof window.HTMLIFrameElement)try{i=i.contentDocument.head}catch(t){i=null}t[e]=i}return t[e]}}(),a=[];function s(t){for(var e=-1,i=0;i<a.length;i++)if(a[i].identifier===t){e=i;break}return e}function l(t,e){for(var i={},o=[],n=0;n<t.length;n++){var r=t[n],l=e.base?r[0]+e.base:r[0],c=i[l]||0,d="".concat(l," ").concat(c);i[l]=c+1;var h=s(d),u={css:r[1],media:r[2],sourceMap:r[3]};-1!==h?(a[h].references++,a[h].updater(u)):a.push({identifier:d,updater:f(u,e),references:1}),o.push(d)}return o}function c(t){var e=document.createElement("style"),o=t.attributes||{};if(void 0===o.nonce){var n=i.nc;n&&(o.nonce=n)}if(Object.keys(o).forEach((function(t){e.setAttribute(t,o[t])})),"function"==typeof t.insert)t.insert(e);else{var a=r(t.insert||"head");if(!a)throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");a.appendChild(e)}return e}var d,h=(d=[],function(t,e){return d[t]=e,d.filter(Boolean).join("\n")});function u(t,e,i,o){var n=i?"":o.media?"@media ".concat(o.media," {").concat(o.css,"}"):o.css;if(t.styleSheet)t.styleSheet.cssText=h(e,n);else{var r=document.createTextNode(n),a=t.childNodes;a[e]&&t.removeChild(a[e]),a.length?t.insertBefore(r,a[e]):t.appendChild(r)}}function p(t,e,i){var o=i.css,n=i.media,r=i.sourceMap;if(n?t.setAttribute("media",n):t.removeAttribute("media"),r&&btoa&&(o+="\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(r))))," */")),t.styleSheet)t.styleSheet.cssText=o;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(o))}}var m=null,g=0;function f(t,e){var i,o,n;if(e.singleton){var r=g++;i=m||(m=c(e)),o=u.bind(null,i,r,!1),n=u.bind(null,i,r,!0)}else i=c(e),o=p.bind(null,i,e),n=function(){!function(t){if(null===t.parentNode)return!1;t.parentNode.removeChild(t)}(i)};return o(t),function(e){if(e){if(e.css===t.css&&e.media===t.media&&e.sourceMap===t.sourceMap)return;o(t=e)}else n()}}t.exports=function(t,e){(e=e||{}).singleton||"boolean"==typeof e.singleton||(e.singleton=n());var i=l(t=t||[],e);return function(t){if(t=t||[],"[object Array]"===Object.prototype.toString.call(t)){for(var o=0;o<i.length;o++){var n=s(i[o]);a[n].references--}for(var r=l(t,e),c=0;c<i.length;c++){var d=s(i[c]);0===a[d].references&&(a[d].updater(),a.splice(d,1))}i=r}}}},function(t,e,i){(e=i(12)(!1)).push([t.i,'.wp-core-ui.mode-edit-image .edit-attachment,.wp-core-ui.mode-edit-image .button[id^="imgedit-open-btn-"]{display:none}.wp-core-ui .media-image-edit{display:flex;align-items:stretch;max-height:100%}.wp-core-ui .media-frame.mode-edit-image .media-image-edit{margin-right:30%}.wp-core-ui .media-frame.mode-edit-image .media-sidebar{width:30%;box-sizing:border-box}.wp-core-ui .hm-thumbnail-sizes{flex:0 0 200px;max-height:100%;overflow:auto;background:#e5e5e5}.wp-core-ui .hm-thumbnail-sizes h2{margin:16px;padding:0}.wp-core-ui .hm-thumbnail-sizes__list{margin:0;padding:0}.wp-core-ui .hm-thumbnail-sizes__list li{width:100%;margin:0;padding:0}.wp-core-ui .hm-thumbnail-sizes__list li:first-child button{border-top:0}.wp-core-ui .hm-thumbnail-sizes__list button{background:none;border:0;border-right:1px solid #ddd;margin:0;padding:16px;box-sizing:border-box;cursor:pointer;display:block;width:100%;text-align:left}.wp-core-ui .hm-thumbnail-sizes__list button.current{border:1px solid #ddd;border-width:1px 0;padding:15px 16px;background:#fff;position:relative}.wp-core-ui .hm-thumbnail-sizes__list h3{margin:0 0 8px;padding:0}.wp-core-ui .hm-thumbnail-sizes__list h3 small{font-weight:300;white-space:nowrap}.wp-core-ui .hm-thumbnail-sizes__list img{display:block;width:auto;height:auto;max-width:100%;max-height:80px}.wp-core-ui .hm-thumbnail-editor{padding:16px;overflow:auto;flex:1}.wp-core-ui .hm-thumbnail-editor h2{margin:0 0 16px}.wp-core-ui .hm-thumbnail-editor h2 small{font-weight:normal;white-space:nowrap}.wp-core-ui .hm-thumbnail-editor .imgedit-menu p{margin-bottom:0;font-size:16px}.wp-core-ui .hm-thumbnail-editor .imgedit-menu button::before{margin-left:8px}.wp-core-ui .hm-thumbnail-editor__image-wrap{overflow:hidden}.wp-core-ui .hm-thumbnail-editor__image{float:left;position:relative}.wp-core-ui .hm-thumbnail-editor__image-crop{position:relative;padding:0;margin:10px 0 0}.wp-core-ui .hm-thumbnail-editor__image--preview{float:none}.wp-core-ui .hm-thumbnail-editor__image img{display:block;max-width:100%;max-height:500px;width:auto;height:auto}.wp-core-ui .hm-thumbnail-editor__image img[src$=".svg"]{width:100%}.wp-core-ui .hm-thumbnail-editor__image .image-preview-full{cursor:crosshair}.wp-core-ui .hm-thumbnail-editor__actions{margin:16px 0 8px}.wp-core-ui .hm-thumbnail-editor .imgedit-wait{position:static;width:20px;height:20px;vertical-align:middle;float:right;margin:4px 0 4px 10px}.wp-core-ui .hm-thumbnail-editor .imgedit-wait::before{margin:0;position:static}.wp-core-ui .hm-thumbnail-editor__focal-point{position:absolute;box-sizing:border-box;width:80px;height:80px;margin-left:-40px;margin-top:-40px;left:0;top:0;background:rgba(200,125,125,0.5);border:2.5px solid rgba(200,50,50,0.5);border-radius:200px;cursor:cell;display:none}.wp-core-ui .hm-thumbnail-editor .imgareaselect-outer{position:absolute !important}\n',""]),t.exports=e},function(t,e,i){"use strict";t.exports=function(t){var e=[];return e.toString=function(){return this.map((function(e){var i=function(t,e){var i=t[1]||"",o=t[3];if(!o)return i;if(e&&"function"==typeof btoa){var n=(a=o,s=btoa(unescape(encodeURIComponent(JSON.stringify(a)))),l="sourceMappingURL=data:application/json;charset=utf-8;base64,".concat(s),"/*# ".concat(l," */")),r=o.sources.map((function(t){return"/*# sourceURL=".concat(o.sourceRoot||"").concat(t," */")}));return[i].concat(r).concat([n]).join("\n")}var a,s,l;return[i].join("\n")}(e,t);return e[2]?"@media ".concat(e[2]," {").concat(i,"}"):i})).join("")},e.i=function(t,i,o){"string"==typeof t&&(t=[[null,t,""]]);var n={};if(o)for(var r=0;r<this.length;r++){var a=this[r][0];null!=a&&(n[a]=!0)}for(var s=0;s<t.length;s++){var l=[].concat(t[s]);o&&n[l[0]]||(i&&(l[2]?l[2]="".concat(i," and ").concat(l[2]):l[2]=i),e.push(l))}},e}},function(t,e,i){"use strict";i.r(e);var o=i(2),n=i(3),r=i(0),a=i.n(r),s=i(1),l=i.n(s),c=a.a.View.extend({tagName:"div",className:"hm-thumbnail-sizes",template:l()("hm-thumbnail-sizes"),events:{"click button":"setSize"},initialize:function(){var t=this;this.listenTo(this.model,"change:sizes",this.render),this.listenTo(this.model,"change:uploading",this.render),this.model.get("size")||this.model.set({size:"full"}),this.on("ready",(function(){var e=t.el.querySelector(".current");e&&e.scrollIntoView()}))},setSize:function(t){this.model.set({size:t.currentTarget.dataset.size}),t.currentTarget.parentNode.parentNode.querySelectorAll("button").forEach((function(t){t.className=""})),t.currentTarget.className="current"}}),d=i(4),h=i.n(d),u=i(5),p=i.n(u),m=i(6),g=i.n(m);i(7),i(8);function f(t,e){return function(t){if(Array.isArray(t))return t}(t)||function(t,e){if("undefined"==typeof Symbol||!(Symbol.iterator in Object(t)))return;var i=[],o=!0,n=!1,r=void 0;try{for(var a,s=t[Symbol.iterator]();!(o=(a=s.next()).done)&&(i.push(a.value),!e||i.length!==e);o=!0);}catch(t){n=!0,r=t}finally{try{o||null==s.return||s.return()}finally{if(n)throw r}}return i}(t,e)||function(t,e){if(!t)return;if("string"==typeof t)return v(t,e);var i=Object.prototype.toString.call(t).slice(8,-1);"Object"===i&&t.constructor&&(i=t.constructor.name);if("Map"===i||"Set"===i)return Array.from(t);if("Arguments"===i||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(i))return v(t,e)}(t,e)||function(){throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function v(t,e){(null==e||e>t.length)&&(e=t.length);for(var i=0,o=new Array(e);i<e;i++)o[i]=t[i];return o}var w=p.a,b=a.a.View.extend({tagName:"div",className:"hm-thumbnail-editor",template:l()("hm-thumbnail-editor"),events:{"click .button-apply-changes":"saveCrop","click .button-reset":"reset","click .button-remove-crop":"removeCrop","click .image-preview-full":"onClickPreview","click .focal-point":"removeFocalPoint","click .imgedit-menu button":"onEditImage"},initialize:function(){this.listenTo(this.model,"change:size",this.loadEditor),this.on("ready",this.loadEditor),window.imageEdit&&(window.imageEdit._view=this,window.imageEdit.initCrop=function(){},window.imageEdit.setCropSelection=function(){})},loadEditor:function(){this.cropper&&this.cropper.setOptions({remove:!0}),this.render();var t=this.model.get("size");"full"!==t&&"full-orig"!==t?this.initCropper():this.initFocalPoint()},refresh:function(){this.update()},back:function(){},save:function(){this.update()},update:function(){var t,e=this;wp&&wp.data&&(null===(t=wp.data.dispatch("core"))||void 0===t?void 0:t.saveMedia)&&wp.data.dispatch("core").saveMedia({id:this.model.get("id")}),this.model.fetch({success:function(){return e.loadEditor()},error:function(){}})},applyRatio:function(){var t=this.model.get("width")/Math.min(1e3,this.model.get("width"));return Array.prototype.slice.call(arguments).map((function(e){return Math.round(e*t)}))},reset:function(){var t=this,e=this.model.get("size"),i=this.model.get("sizes"),o=this.model.get("focalPoint"),n=this.model.get("width"),r=this.model.get("height"),a=i[e]||null;if(a){var s=a.cropData;if(s.hasOwnProperty("x"))this.setSelection(s);else if(o.hasOwnProperty("x")){var l=f(function(t,e,i,o){var n=t/i*o;return n<e?[t,Math.round(n)]:[Math.round(e/o*i),e]}(n,r,a.width,a.height),2),c=l[0],d=l[1];this.setSelection({x:Math.min(n-c,Math.max(0,o.x-c/2)),y:Math.min(r-d,Math.max(0,o.y-d/2)),width:c,height:d})}else{var h=this.$el.find('img[id^="image-preview-"]').get(0);g.a.crop(h,{width:a.width,height:a.height}).then((function(e){var i=e.topCrop;t.setSelection(i)}))}}},saveCrop:function(){var t=this,e=this.cropper.getSelection();this.onSelectStart(),this.cropper&&this.cropper.setOptions({disable:!0}),h.a.post("hm_save_crop",{_ajax_nonce:this.model.get("nonces").edit,id:this.model.get("id"),crop:{x:e.x1,y:e.y1,width:e.width,height:e.height},size:this.model.get("size")}).always((function(){t.onSelectEnd(),t.cropper&&t.cropper.setOptions({enable:!0})})).done((function(){t.update()})).fail((function(t){return console.log(t)}))},setSelection:function(t){if(this.onSelectStart(),!t||void 0===t.x)return this.cropper.setOptions({show:!0}),void this.cropper.update();this.cropper.setSelection(t.x,t.y,t.x+t.width,t.y+t.height),this.cropper.setOptions({show:!0}),this.cropper.update()},onSelectStart:function(){this.$el.find(".button-apply-changes, .button-reset").prop("disabled",!0)},onSelectEnd:function(){this.$el.find(".button-apply-changes, .button-reset").prop("disabled",!1)},onSelectChange:function(){this.$el.find(".button-apply-changes:disabled, .button-reset:disabled").prop("disabled",!1)},initCropper:function(){var t=this,e=this.$el.find('img[id^="image-preview-"]'),i=e.parent(),o=this.model.get("size"),n=this.model.get("sizes")[o]||null;if(n){var r="".concat(n.width,":").concat(n.height);this.cropper=e.imgAreaSelect({parent:i,autoHide:!1,instance:!0,handles:!0,keys:!0,imageWidth:this.model.get("width"),imageHeight:this.model.get("height"),minWidth:n.width,minHeight:n.height,aspectRatio:r,persistent:!0,onInit:function(e){var i=w(e);i.next().css("position","absolute").nextAll(".imgareaselect-outer").css("position","absolute"),i.width(Math.round(i.innerWidth())),i.height(Math.round(i.innerHeight())),t.reset()},onSelectStart:function(){t.onSelectStart.apply(t,arguments)},onSelectEnd:function(){t.onSelectEnd.apply(t,arguments)},onSelectChange:function(){t.onSelectChange.apply(t,arguments)}})}},initFocalPoint:function(){var t=this.model.get("width"),e=this.model.get("height"),i=this.model.get("focalPoint")||{},o=this.$el.find(".focal-point");i.hasOwnProperty("x")&&i.hasOwnProperty("y")&&o.css({left:"".concat(100/t*i.x,"%"),top:"".concat(100/e*i.y,"%"),display:"block"})},onClickPreview:function(t){var e=this.model.get("width"),i=this.model.get("height"),o=t.offsetX*(e/t.currentTarget.offsetWidth),n=t.offsetY*(i/t.currentTarget.offsetHeight);this.$el.find(".focal-point").css({left:"".concat(Math.round(100/e*o),"%"),top:"".concat(Math.round(100/i*n),"%"),display:"block"}),this.setFocalPoint({x:o,y:n})},setFocalPoint:function(t){var e=this;h.a.post("hm_save_focal_point",{_ajax_nonce:this.model.get("nonces").edit,id:this.model.get("id"),focalPoint:t}).done((function(){e.update()})).fail((function(t){return console.log(t)}))},removeFocalPoint:function(t){this.$el.find(".focal-point").hide(),t.stopPropagation(),this.setFocalPoint(!1)},removeCrop:function(){var t=this;h.a.post("hm_remove_crop",{_ajax_nonce:this.model.get("nonces").edit,id:this.model.get("id"),size:this.model.get("size")}).done((function(){t.update()})).fail((function(t){return console.log(t)}))},onEditImage:function(){this.$el.find(".focal-point, .note-focal-point").hide(),this.$el.find(".imgedit-submit-btn").prop("disabled",!1)}}),y=a.a.View.extend({tagName:"div",className:"hm-thumbnail-editor",template:l()("hm-thumbnail-preview")}),x=a.a.View.extend({template:l()("hm-thumbnail-container"),initialize:function(){this.model.get("size")||this.model.set({size:"full"}),this.setSizeFromBlock(),this.listenTo(this.model,"change:url",this.onUpdate),this.onUpdate()},setSizeFromBlock:function(){var t;if(wp&&wp.data){var e=null===(t=wp.data.select("core/block-editor"))||void 0===t?void 0:t.getSelectedBlock();if(e){var i=Object(o.applyFilters)("smartmedia.cropper.selectSizeFromBlockAttributes.".concat(e.name.replace(/\W+/g,".")),null,e),n=Object(o.applyFilters)("smartmedia.cropper.selectSizeFromBlockAttributes",i,e);n&&this.model.set({size:n})}}},onUpdate:function(){var t=[];this.model.get("uploading")?t.push(new a.a.view.UploaderStatus({controller:this.controller})):this.model.get("id")&&!this.model.get("url")?t.push(new a.a.view.Spinner):this.model.get("editor")&&this.model.get("mime").match(/image\/(gif|jpe?g|png|webp)/)?(t.push(new c({controller:this.controller,model:this.model,priority:10})),t.push(new b({controller:this.controller,model:this.model,priority:50}))):t.push(new y({controller:this.controller,model:this.model,priority:50})),this.views.set(t)}});x.load=function(t){return new x({controller:t,model:t.model,el:t.$el.find(".media-image-edit").get(0)})};var S=x;i(9);Object(o.addFilter)("smartmedia.cropper.updateBlockAttributesOnSelect.core.image","smartmedia/cropper/update-block-on-select/core/image",(function(t,e){return e.label?{sizeSlug:e.size,url:e.url}:t})),Object(o.addFilter)("smartmedia.cropper.selectSizeFromBlockAttributes.core.image","smartmedia/cropper/select-size-from-block-attributes/core/image",(function(t,e){return t||e.attributes.sizeSlug||"full"})),Object(o.addAction)("amf.extend_toolbar","smartmedia/cropper",(function(t){a.a.view.Toolbar=t(a.a.view.Toolbar,"apply")})),wp&&wp.data&&window._wpLoadBlockEditor&&window._wpLoadBlockEditor.then((function(){var t=document.querySelector(".block-editor");t&&t.addEventListener("focusin",(function(t){t.target.closest(".edit-post-meta-boxes-area")&&wp.data.dispatch("core/block-editor").clearSelectedBlock()}))}));var _=a.a.view.MediaFrame;a.a.view.MediaFrame=_.extend({initialize:function(){_.prototype.initialize.apply(this,arguments),a.a.events.trigger("frame:init",this)}});var k=a.a.view.MediaFrame.Select;a.a.view.MediaFrame.Select=k.extend({initialize:function(t){k.prototype.initialize.apply(this,arguments),this._button=Object.assign({},t.button||{}),this.on("toolbar:create:select",this.onCreateToolbarSetButton,this),this.createImageEditorState(),this.on("ready",this.createImageEditorState,this),this.on("content:create:edit",this.onCreateImageEditorContent,this),this.on("toolbar:create:edit",this.onCreateImageEditorToolbar,this),a.a.events.trigger("frame:select:init",this)},onCreateToolbarSetButton:function(){this._button&&(this.options.mutableButton=Object.assign({},this.options.button),this.options.button=Object.assign({},this._button))},createImageEditorState:function(){var t=this;if(!this.options.multiple&&!this.states.get("cropper")&&!this.states.get("edit")){var e=this.states.get("library")||this.states.get("featured-image");if(e&&e.get("selection")){var i="featured-image"===e.id;this.$el.addClass("hide-toolbar");var o=this.states.add({id:"edit",title:Object(n.__)("Edit image","hm-smart-media"),router:!1,menu:!1,uploader:!1,library:e.get("library"),selection:e.get("selection"),display:e.get("display")});o.on("activate",(function(){t.$el.hasClass("hide-menu")&&t.lastState()&&t.lastState().set("menu",!1),t.$el.addClass("mode-select mode-edit-image"),t.$el.removeClass("hide-toolbar"),t.content.mode("edit"),t.toolbar.mode("edit")})),o.on("deactivate",(function(){t.$el.removeClass("mode-select mode-edit-image"),t.$el.addClass("hide-toolbar")})),e.get("selection").on("selection:single",(function(){var e=t.state("edit").get("selection").single();if((i||e.get("url"))&&e.get("id")&&(!e.get("mime")||e.get("mime").match(/^image\//))){if(i){var o;if(wp&&wp.data)(null===(o=wp.data.select("core/block-editor"))||void 0===o?void 0:o.getSelectedBlock())&&wp.data.dispatch("core/block-editor").clearSelectedBlock();wp.media.view.settings.post.featuredImageId=e.get("id")}t.setState("edit")}})),e.get("selection").on("selection:unsingle",(function(){i&&(wp.media.view.settings.post.featuredImageId=-1),t.setState(e.id)}))}}},onCreateImageEditorContent:function(t){var e=this.state("edit"),i=e.get("selection").single(),o=new a.a.view.Sidebar({controller:this});o.set("details",new a.a.view.Attachment.Details({controller:this,model:i,priority:80})),o.set("compat",new a.a.view.AttachmentCompat({controller:this,model:i,priority:120})),(e.has("display")?e.get("display"):e.get("displaySettings"))&&o.set("display",new a.a.view.Settings.AttachmentDisplay({controller:this,model:e.display(i),attachment:i,priority:160,userSettings:e.model.get("displayUserSettings")})),"insert"===e.id&&o.$el.addClass("visible"),t.view=[new S({tagName:"div",className:"media-image-edit",controller:this,model:i}),o]},onCreateImageEditorToolbar:function(t){var e=this;t.view=new a.a.view.Toolbar({controller:this,requires:{selection:!0},reset:!1,event:"select",items:{change:{text:Object(n.__)("Change image","hm-smart-media"),click:function(){e.state("edit").get("selection").reset([])},priority:20,requires:{selection:!0}},apply:{style:"primary",text:Object(n.__)("Select","hm-smart-media"),click:function(){var t=Object.assign(e.options.mutableButton||e.options.button||{},{event:"select",close:!0}),i=t.close,n=t.event,r=t.reset,a=t.state;if(i&&e.close(),n&&(e.state()._events[n]?e.state().trigger(n):e.lastState()._events[n]?e.lastState().trigger(n):e.trigger(n)),a&&e.setState(a),r&&e.reset(),wp&&wp.data){var s,l=null===(s=wp.data.select("core/block-editor"))||void 0===s?void 0:s.getSelectedBlock();if(!l)return;var c=e.state("edit").get("selection").single();if(!c)return;var d=c.get("sizes"),h=c.get("size"),u=d[h];u.id=c.get("id"),u.size=h;var p=Object(o.applyFilters)("smartmedia.cropper.updateBlockAttributesOnSelect.".concat(l.name.replace(/\W+/g,".")),null,u,c),m=Object(o.applyFilters)("smartmedia.cropper.updateBlockAttributesOnSelect",p,l,u,c);if(!m)return;wp.data.dispatch("core/block-editor").updateBlock(l.clientId,{attributes:m})}},priority:10,requires:{selection:!0}}}})}}),a.a.events.on("frame:init",(function(){a.a.view.Attachment.Details.TwoColumn=a.a.view.Attachment.Details.TwoColumn.extend({template:l()("hm-attachment-details-two-column"),initialize:function(){var t=this;a.a.view.Attachment.Details.prototype.initialize.apply(this,arguments),this.listenTo(this.model,"change:url",(function(){t.render(),S.load(t.controller)})),this.controller.on("ready refresh",(function(){return S.load(t.controller)}))}})}));var C=a.a.view.Attachment,z=a.a.view.Attachment.Library,M=a.a.view.Attachment.EditLibrary,O=a.a.view.Attachment.Selection,T=function(){if(C.prototype.render.apply(this,arguments),"image"===this.model.get("type")&&!this.model.get("uploading")){var t=this.imageSize();this.$el.find("img").attr({width:t.width,height:t.height})}};a.a.view.Attachment=C.extend({render:T}),a.a.view.Attachment.Library=z.extend({render:T}),a.a.view.Attachment.EditLibrary=M.extend({render:T}),a.a.view.Attachment.Selection=O.extend({render:T});var j=a.a.view.UploaderStatus;a.a.view.UploaderStatus=j.extend({info:function(){this.$index&&j.prototype.info.apply(this,arguments)}})}]));