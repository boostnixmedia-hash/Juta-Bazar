(function(){
'use strict';
var $=function(s,r){return (r||document).querySelector(s)};
function post(a,fd){fd.append('csrf',window.CSRF);return fetch(window.AURL+'?a='+a,{method:'POST',body:fd,credentials:'same-origin',headers:{'X-CSRF':window.CSRF}}).then(function(r){return r.json().catch(function(){throw new Error('সার্ভার থেকে উত্তর আসেনি ('+r.status+')')})})}

/* shrink big photos in the browser before upload (host upload limit is often 2 MB) */
function shrink(file,max){return new Promise(function(res){
  if(!/^image\/(jpeg|png|webp)$/.test(file.type)||file.size<700000)return res(file);
  var img=new Image(),u=URL.createObjectURL(file);
  img.onload=function(){var s=Math.min(1,max/Math.max(img.width,img.height));var c=document.createElement('canvas');c.width=Math.round(img.width*s);c.height=Math.round(img.height*s);
    var x=c.getContext('2d');x.drawImage(img,0,0,c.width,c.height);URL.revokeObjectURL(u);
    var type=file.type==='image/png'?'image/png':'image/jpeg';
    c.toBlob(function(b){if(b&&b.size>1800000&&type==='image/png'){c.toBlob(function(b2){res(b2||file)},'image/jpeg',.86)}else res(b||file)},type,.86)};
  img.onerror=function(){res(file)};img.src=u})}

/* product images */
var box=$('#imgs'),inp=$('#imgfile');
if(box&&inp){
  var addBtn=inp.closest('.im');
  inp.addEventListener('change',function(){
    var files=[].slice.call(inp.files);inp.value='';
    var chain=Promise.resolve();
    files.forEach(function(f){
      var ph=document.createElement('div');ph.className='im loading';ph.textContent='আপলোড হচ্ছে...';box.insertBefore(ph,addBtn);
      chain=chain.then(function(){return shrink(f,1800)}).then(function(b){var fd=new FormData();fd.append('file',b,f.name.replace(/\.\w+$/,'')+(b.type==='image/png'?'.png':'.jpg'));return window.JB_DEMO_UPLOAD?window.JB_DEMO_UPLOAD(b):post('upload',fd)}).then(function(r){
        if(!r.ok)throw new Error(r.error);
        ph.className='im';ph.draggable=true;ph.innerHTML='<img src="'+r.url+'" alt=""><input type="hidden" name="images[]" value="'+r.path+'"><button type="button" class="rm" title="সরান">×</button>';
      }).catch(function(e){ph.remove();alert('ছবি আপলোড হয়নি: '+e.message)});
    });
    chain.then(function(){autoCaption('image')});
  });
  box.addEventListener('click',function(e){if(e.target.classList.contains('rm'))e.target.closest('.im').remove()});
  var drag=null;
  box.addEventListener('dragstart',function(e){drag=e.target.closest('.im:not(.add)');if(drag)drag.classList.add('dragging')});
  box.addEventListener('dragend',function(){if(drag)drag.classList.remove('dragging');drag=null});
  box.addEventListener('dragover',function(e){e.preventDefault();var t=e.target.closest('.im:not(.add)');if(drag&&t&&t!==drag){var r=t.getBoundingClientRect();box.insertBefore(drag,(e.clientX-r.left)>r.width/2?t.nextSibling:t)}});
}

/* auto caption */
var gen=$('#gen'),gen2=$('#gen2'),busy=false,auto=false;
function val(id){var el=$(id);return el?el.value:''}
function caption(mode){
  if(busy)return;
  if(!val('#f-name').trim()){alert('আগে প্রোডাক্টের নাম লিখুন।');$('#f-name').focus();return}
  busy=true;var ai=$('.ai-box');ai.classList.add('busy');gen.disabled=gen2.disabled=true;var old=gen.textContent;gen.textContent='✨ লেখা তৈরি হচ্ছে...';
  var fd=new FormData();
  fd.append('name',val('#f-name'));fd.append('collection_id',val('#f-col'));fd.append('brand',val('#f-brand'));fd.append('price',val('#f-price'));fd.append('old_price',val('#f-old'));fd.append('colors',val('#f-colors'));fd.append('notes',val('#f-notes'));fd.append('mode',mode||'ai');var tn=document.querySelector('#tone input:checked');fd.append('tone',tn?tn.value:'premium');
  fd.append('sizes',[].map.call(document.querySelectorAll('#sizes input:checked'),function(i){return i.value}).join(','));
  var first=document.querySelector('#imgs input[name="images[]"]');if(first)fd.append('image',first.value);
  (window.JB_DEMO_CAP?new Promise(function(ok){setTimeout(function(){ok(window.JB_DEMO_CAP(fd))},700)}):post('caption',fd)).then(function(r){
    if(!r.ok)throw new Error(r.error||'');
    [['#f-short','short'],['#f-desc','description'],['#f-short-bn','short_bn'],['#f-desc-bn','description_bn'],['#f-fb','fb_post'],['#f-fb-en','fb_post_en'],['#f-insta','insta'],['#f-seo-t','seo_title'],['#f-seo-d','seo_desc'],['#f-tags','tags']].forEach(function(x){var el=$(x[0]);if(el&&r[x[1]]){el.value=r[x[1]];el.classList.remove('flash');void el.offsetWidth;el.classList.add('flash')}});
    $('#gen-note').textContent=r.source==='ai'?'✅ AI দিয়ে লেখা হয়েছে। দরকার হলে নিজের মতো বদলে নিন।':'✅ '+(r.note||'টেমপ্লেট দিয়ে লেখা হয়েছে।');
  }).catch(function(e){$('#gen-note').textContent='লেখা তৈরি হয়নি: '+e.message}).then(function(){busy=false;ai.classList.remove('busy');gen.disabled=gen2.disabled=false;gen.textContent=old});
}
function autoCaption(why){
  if(!gen||auto)return;
  if(val('#f-desc').trim()||val('#f-desc-bn').trim()||!val('#f-name').trim()||val('#f-col')==='0')return;
  if(why==='image'&&!document.querySelector('#imgs input[name="images[]"]'))return;
  auto=true;caption('ai');
}
if(gen){
  gen.addEventListener('click',function(){caption('ai')});
  gen2.addEventListener('click',function(){caption('ai')});
}

/* caption language tabs */
document.addEventListener('click',function(e){var b=e.target.closest('[data-captab]');if(!b)return;var k=b.getAttribute('data-captab');
  [].forEach.call(document.querySelectorAll('[data-captab]'),function(x){x.classList.toggle('on',x===b)});
  [].forEach.call(document.querySelectorAll('.cap-pane'),function(p){p.hidden=p.getAttribute('data-pane')!==k})});

/* copy buttons */
document.addEventListener('click',function(e){var b=e.target.closest('[data-copy]');if(!b)return;e.preventDefault();var t=$(b.getAttribute('data-copy'));
  (navigator.clipboard?navigator.clipboard.writeText(t.value):Promise.reject()).catch(function(){t.select();document.execCommand('copy')}).then(function(){b.textContent='✓ কপি হয়েছে';setTimeout(function(){b.textContent='📋 কপি'},1500)})});

/* collection image preview */
document.querySelectorAll('.cimg input[type=file]').forEach(function(i){i.addEventListener('change',function(){if(i.files[0])i.closest('.cimg').querySelector('img').src=URL.createObjectURL(i.files[0])})});

/* Gemini key test */
var gt=$('#gtest');
if(gt)gt.addEventListener('click',function(){var o=$('#gtest-out');o.textContent='টেস্ট হচ্ছে... (আগে key সেভ করে নিন)';
  post('gemini_test',new FormData()).then(function(r){o.textContent=r.ok?'✅ কাজ করছে! নমুনা: '+r.sample:'❌ কাজ করেনি: '+r.error}).catch(function(e){o.textContent='❌ '+e.message})});

/* warn before leaving an unsaved product form */
var pf=$('#pform');
if(pf){var dirty=false;pf.addEventListener('input',function(){dirty=true});pf.addEventListener('submit',function(){dirty=false});
  window.addEventListener('beforeunload',function(e){if(dirty){e.preventDefault();e.returnValue=''}})}
})();
