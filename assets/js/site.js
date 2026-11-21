/* Jutar Bazar storefront: cart (localStorage), product buy box, checkout. */
(function(){
'use strict';
var J=window.JB||{base:'/',cur:'৳',din:70,dout:130,free:0};
var KEY='jb_cart_v1';
/* always open a page at the top (no jumping to an old scroll position) */
try{if('scrollRestoration' in history)history.scrollRestoration='manual'}catch(e){}
if(!location.hash){window.scrollTo(0,0);window.addEventListener('load',function(){if(!location.hash)window.scrollTo(0,0)})}
var BN=J.lang==='bn';var T=function(en,b){return BN?b:en};
var bn=function(n){n=String(n);return BN?n.replace(/[0-9]/g,function(d){return '০১২৩৪৫৬৭৮৯'[d]}):n};
var money=function(n){return J.cur+bn(Number(n).toLocaleString('en-US'))};
var esc=function(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})};
function load(){try{return JSON.parse(localStorage.getItem(KEY))||[]}catch(e){return []}}
function save(c){try{localStorage.setItem(KEY,JSON.stringify(c))}catch(e){}badge()}
function badge(){var n=load().reduce(function(a,i){return a+i.qty},0);document.querySelectorAll('[data-cart-count]').forEach(function(b){b.textContent=bn(n);b.hidden=!n})}
function toast(t){var el=document.getElementById('toast');if(!el)return;el.textContent=t;el.classList.add('on');clearTimeout(toast._t);toast._t=setTimeout(function(){el.classList.remove('on')},2200)}
function add(item){var c=load();var f=c.find(function(i){return i.id===item.id&&i.size===item.size&&i.color===item.color});if(f)f.qty=Math.min(10,f.qty+item.qty);else c.push(item);save(c)}
badge();

/* open / close panels */
document.addEventListener('click',function(e){
  var o=e.target.closest('[data-open]');if(o){var t=document.querySelector(o.getAttribute('data-open'));if(t){t.hidden=false;var i=t.querySelector('input');if(i)i.focus()}return}
  var c=e.target.closest('[data-close]');if(c){var t2=document.querySelector(c.getAttribute('data-close'));if(t2)t2.hidden=true}
});
document.addEventListener('keydown',function(e){if(e.key==='Escape')document.querySelectorAll('#drawer,#searchbox').forEach(function(x){x.hidden=true})});

/* WhatsApp popup: shows once per visit after N seconds, not on checkout */
(function(){var pop=document.getElementById('wa-pop');var sec=+window.JB_WAPOP||0;if(!pop||!sec)return;
  var seen=false;try{seen=(Date.now()-(+localStorage.getItem('jb_wa_t')||0))<15*60*1000}catch(e){}
  if(seen||/\/(checkout|order|account|login|register)/.test(location.pathname))return;
  var close=function(){pop.hidden=true;try{localStorage.setItem('jb_wa_t',String(Date.now()))}catch(e){}};
  setTimeout(function(){pop.hidden=false},sec*1000);
  pop.addEventListener('click',function(e){if(e.target.closest('[data-wa-close]')||e.target.closest('#wa-go'))close()});
})();
/* reveal on scroll */
if('IntersectionObserver' in window){
  var io=new IntersectionObserver(function(es){es.forEach(function(en){if(en.isIntersecting){en.target.classList.add('in');io.unobserve(en.target)}})},{rootMargin:'0px 0px -8% 0px'});
  document.querySelectorAll('.col-sec .sec-head,.col-banner,.grid .card,.promo-in,.about-in,.col-tile').forEach(function(el,i){if(el.getBoundingClientRect().top>innerHeight){el.classList.add('rv');io.observe(el)}});
}
var cr=document.querySelector('.col-row');if(cr)cr.style.setProperty('--n',cr.children.length);

/* product page */
var buy=document.getElementById('buy');
if(buy){
  var P=JSON.parse(buy.getAttribute('data-product'));
  var q=buy.querySelector('[name=qty]');
  buy.addEventListener('click',function(e){var b=e.target.closest('[data-q]');if(b){q.value=Math.max(1,Math.min(10,(+q.value||1)+(+b.getAttribute('data-q'))))}});
  var go='buy';
  buy.querySelectorAll('button[name=go]').forEach(function(b){b.addEventListener('click',function(){go=b.value})});
  buy.addEventListener('submit',function(e){
    e.preventDefault();
    var sz=buy.querySelector('[name=size]');var s=buy.querySelector('[name=size]:checked');
    if(sz&&!s){buy.classList.remove('need-size');void buy.offsetWidth;buy.classList.add('need-size');toast(T('Please select a size','সাইজ বেছে নিন'));buy.querySelector('.sizes').scrollIntoView({behavior:'smooth',block:'center'});return}
    var col=buy.querySelector('[name=color]:checked');
    add({id:P.id,size:s?s.value:'',color:col?col.value:'',qty:Math.max(1,Math.min(10,+q.value||1))});
    if(window.fbq)fbq('track','AddToCart',{value:P.price,currency:'BDT'});
    if(go==='buy')location.href=J.base+'checkout';else toast(T('Added to cart ✓','কার্টে যোগ হয়েছে ✓'));
  });
  /* gallery thumbs */
  var gm=document.getElementById('gmain');
  document.querySelectorAll('[data-goto]').forEach(function(b){b.addEventListener('click',function(){var i=+b.getAttribute('data-goto');gm.scrollTo({left:gm.clientWidth*i,behavior:'smooth'});document.querySelectorAll('[data-goto]').forEach(function(x){x.classList.toggle('on',x===b)})})});
  /* sticky buy bar on phone */
  var sb=document.querySelector('.sticky-buy');
  if(sb&&'IntersectionObserver' in window){new IntersectionObserver(function(es){sb.classList.toggle('on',!es[0].isIntersecting&&es[0].boundingClientRect.top<0)}).observe(buy.querySelector('.buy-btns')||buy)}
  var sbb=document.querySelector('[data-scroll-buy]');if(sbb)sbb.addEventListener('click',function(){buy.scrollIntoView({behavior:'smooth',block:'center'})});
}

/* cart data */
function fetchItems(cb){
  var c=load();if(!c.length)return cb([],{});
  fetch(J.base+'api/products?ids='+c.map(function(i){return i.id}).join(',')).then(function(r){return r.json()}).then(function(d){
    var keep=c.filter(function(i){return d[i.id]});if(keep.length!==c.length)save(keep);cb(keep,d)}).catch(function(){cb(c,{})});
}
function lines(c,d,editable){
  return c.map(function(i,k){var p=d[i.id]||{name:'...',price:0,img:'',url:'#'};
    return '<div class="ci"><a href="'+esc(p.url)+'"><img src="'+esc(p.img)+'" alt=""></a><div><h3><a href="'+esc(p.url)+'">'+esc(p.name)+'</a></h3><small>'+(i.size?T('Size ','সাইজ ')+bn(esc(i.size)):'')+(i.color?' • '+esc(i.color):'')+(editable?'':T(' • Qty ',' • পরিমাণ ')+bn(i.qty))+'</small>'+
    (editable?'<div class="qty"><button type="button" data-ci="'+k+'" data-d="-1" aria-label="'+T('Decrease','কমান')+'">−</button><input value="'+i.qty+'" readonly aria-label="'+T('Quantity','পরিমাণ')+'"><button type="button" data-ci="'+k+'" data-d="1" aria-label="'+T('Increase','বাড়ান')+'">+</button></div><button class="ci-rm" data-rm="'+k+'">'+T('Remove','মুছুন')+'</button>':'')+
    '</div><b>'+money(p.price*i.qty)+'</b></div>'}).join('');
}
function totals(c,d,area){
  var sub=c.reduce(function(a,i){return a+(d[i.id]?d[i.id].price*i.qty:0)},0);
  var del=area==='out'?J.dout:J.din;if(J.free&&sub>=J.free)del=0;
  return {sub:sub,del:del,total:sub+del};
}
function freeBar(sub){if(!J.free)return '';if(sub>=J.free)return '<div class="free-bar">'+T('🎉 You get free delivery!','🎉 আপনি ফ্রি ডেলিভারি পাচ্ছেন!')+'</div>';
  return '<div class="free-bar">'+(BN?'আর '+money(J.free-sub)+' কিনলেই ফ্রি ডেলিভারি':'Add '+money(J.free-sub)+' more for free delivery')+'<i style="--w:'+Math.round(sub*100/J.free)+'%"></i></div>'}

var root=document.getElementById('cart-root');
if(root){
  var render=function(){fetchItems(function(c,d){
    if(!c.length){root.className='cart-root';root.innerHTML='<div class="empty"><p>'+T('Your cart is empty.','আপনার কার্ট খালি।')+'</p><a class="btn" href="'+J.base+'shop">'+T('Shop shoes','জুতা দেখুন')+'</a></div>';return}
    var t=totals(c,d,'in');root.className='cart-root has';
    root.innerHTML='<div>'+lines(c,d,true)+'</div><div class="sum">'+freeBar(t.sub)+'<div class="row"><span>'+T('Subtotal','সাবটোটাল')+'</span><b>'+money(t.sub)+'</b></div><div class="row"><span>'+T('Delivery','ডেলিভারি')+'</span><span>'+T('Calculated at checkout','চেকআউটে হিসাব হবে')+'</span></div><a class="btn btn-block" href="'+J.base+'checkout">'+T('Checkout','চেকআউট')+'</a></div>';
  })};
  root.addEventListener('click',function(e){var c=load();var b=e.target.closest('[data-ci]');
    if(b){var k=+b.getAttribute('data-ci');c[k].qty=Math.max(1,Math.min(10,c[k].qty+(+b.getAttribute('data-d'))));save(c);render()}
    var r=e.target.closest('[data-rm]');if(r){c.splice(+r.getAttribute('data-rm'),1);save(c);render()}});
  render();
}

var co=document.getElementById('co-form');
if(co){
  var sum=document.getElementById('co-sum'),C=[],D={};
  var draw=function(){var area=(co.querySelector('[name=area]:checked')||{}).value||'in';var t=totals(C,D,area);
    sum.innerHTML='<div class="sum"><h3 style="margin-bottom:6px">'+T('Your order','আপনার অর্ডার')+'</h3>'+lines(C,D,false)+'<div style="height:8px"></div>'+freeBar(t.sub)+'<div class="row"><span>'+T('Subtotal','সাবটোটাল')+'</span><b>'+money(t.sub)+'</b></div><div class="row"><span>'+T('Delivery charge','ডেলিভারি চার্জ')+'</span><b>'+(t.del?money(t.del):T('Free','ফ্রি'))+'</b></div><div class="row total"><span>'+T('Total','সর্বমোট')+'</span><span>'+money(t.total)+'</span></div><a href="'+J.base+'cart" class="link" style="margin:8px 0 0;display:inline-block">'+T('Edit cart','কার্ট এডিট করুন')+'</a></div>'};
  fetchItems(function(c,d){C=c;D=d;if(!c.length){location.href=J.base+'cart';return}draw()});
  co.addEventListener('change',function(e){if(e.target.name==='area')draw()});
  co.addEventListener('submit',function(e){
    e.preventDefault();var err=document.getElementById('co-err');err.hidden=true;
    var f=new FormData(co),bad=null;
    ['name','phone','address'].forEach(function(n){var el=co.querySelector('[name='+n+']');var v=(el.value||'').trim();var ok=n==='phone'?/^(\+?88)?01[3-9]\d{8}$/.test(v.replace(/[\s-]/g,'').replace(/[০-৯]/g,function(d){return '০১২৩৪৫৬৭৮৯'.indexOf(d)})):v.length>=(n==='address'?8:2);el.classList.toggle('bad',!ok);if(!ok&&!bad)bad=el});
    if(bad){bad.focus();err.textContent=T('Please fill in the highlighted fields.','লাল চিহ্নিত ঘরগুলো ঠিকভাবে পূরণ করুন।');err.hidden=false;return}
    var btn=document.getElementById('co-btn');btn.disabled=true;btn.textContent=T('Sending order...','অর্ডার পাঠানো হচ্ছে...');
    var body={name:f.get('name'),phone:String(f.get('phone')).replace(/[০-৯]/g,function(d){return '০১২৩৪৫৬৭৮৯'.indexOf(d)}),address:f.get('address'),area:f.get('area'),note:f.get('note'),website:f.get('website'),items:load()};
    fetch(J.base+'api/order',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)}).then(function(r){return r.json()}).then(function(r){
      if(r.ok){save([]);location.href=r.url}else{throw new Error(r.error||'error')}
    }).catch(function(x){err.textContent=x.message&&x.message!=='error'?x.message:T('Could not send the order. Check your internet and try again.','অর্ডার পাঠানো যায়নি। ইন্টারনেট চেক করে আবার চেষ্টা করুন।');err.hidden=false;btn.disabled=false;btn.textContent=T('Place order','অর্ডার কনফার্ম করুন')});
  });
}
/* recently viewed (kept on this phone/computer only) */
(function(){var RK='jb_rv';var ids=[];try{ids=JSON.parse(localStorage.getItem(RK)||'[]')}catch(e){}
  var b=document.getElementById('buy'),cur=0;
  if(b){try{cur=JSON.parse(b.getAttribute('data-product')).id}catch(e){}
    if(cur){ids=[cur].concat(ids.filter(function(x){return x!==cur})).slice(0,12);try{localStorage.setItem(RK,JSON.stringify(ids))}catch(e){}}}
  var box=document.getElementById('recent'),grid=document.getElementById('recent-grid');if(!box||!grid)return;
  var show=ids.filter(function(x){return x!==cur}).slice(0,4);if(!show.length)return;
  fetch(J.base+'api/products?ids='+show.join(',')).then(function(r){return r.json()}).then(function(d){
    var h=show.map(function(id){var p=d[id];if(!p)return '';return '<article class="card"><a class="card-img" href="'+esc(p.url)+'"><img src="'+esc(p.img)+'" alt="'+esc(p.name)+'" loading="lazy" width="600" height="600"></a><div class="card-body"><h3><a href="'+esc(p.url)+'">'+esc(p.name)+'</a></h3><div class="price"><b>'+money(p.price)+'</b></div></div></article>'}).join('');
    if(h){grid.innerHTML=h;box.hidden=false}}).catch(function(){});
})();
})();
/* listing: filter sheet + 1/2 column view */
(function(){var sh=document.getElementById('lx-sheet'),g=document.getElementById('lx-grid');
  document.addEventListener('click',function(e){
    var ob=e.target.closest('[data-lx-open]');
    if(ob&&window.innerWidth>=900){var hid=document.body.classList.toggle('lx-hide');var ft=ob.querySelector('.lx-ft');if(ft)ft.textContent=ft.getAttribute(hid?'data-off':'data-on');try{localStorage.setItem('jb_lxhide',hid?'1':'')}catch(x){}return}
    if(ob&&sh){sh.hidden=false;document.body.style.overflow='hidden'}
    if(e.target.closest('[data-lx-close]')&&sh){sh.hidden=true;document.body.style.overflow=''}
    var b=e.target.closest('.lx-view button');if(b&&g){set(b.getAttribute('data-cols'));try{localStorage.setItem(window.innerWidth>=900?'jb_cols_d':'jb_cols',b.getAttribute('data-cols'))}catch(x){}}
  });
  function set(n){if(!g)return;g.classList.toggle('one',n==='1');g.classList.toggle('c2',n==='2');g.classList.toggle('c3',n==='3');g.classList.toggle('c4',n==='4');[].forEach.call(document.querySelectorAll('.lx-view button'),function(x){x.classList.toggle('on',x.getAttribute('data-cols')===n)})}
  try{var v=localStorage.getItem(window.innerWidth>=900?'jb_cols_d':'jb_cols');if(v)set(v);else if(window.innerWidth>=900)set('3')}catch(x){}
  try{if(window.innerWidth>=900&&localStorage.getItem('jb_lxhide')==='1'&&document.getElementById('lx-bar')){document.body.classList.add('lx-hide');var ft=document.querySelector('.lx-ft');if(ft)ft.textContent=ft.getAttribute('data-off')}}catch(x){}
})();
/* phone: hide header when scrolling down, show when scrolling up */
(function(){var h=document.querySelector('.hdr');if(!h)return;var y0=window.scrollY,tick=false;
  window.addEventListener('scroll',function(){if(tick)return;tick=true;requestAnimationFrame(function(){var y=window.scrollY;
    if(window.innerWidth<900&&!document.body.style.overflow){var hide=y>140&&y>y0+4;var show=y<y0-4||y<80;
      if(hide){h.classList.add('hide');document.body.classList.add('hdr-hidden')}else if(show){h.classList.remove('hide');document.body.classList.remove('hdr-hidden')}}
    y0=y;tick=false})},{passive:true});
})();
/* PC: hero fills the screen under the header */
(function(){var h=document.querySelector('.hdr');if(!h)return;function s(){document.documentElement.style.setProperty('--hdr-pc',h.offsetHeight+'px')}s();window.addEventListener('resize',s)})();
/* offer slider: auto slide every 4.5 s, swipe on phone */
(function(){[].forEach.call(document.querySelectorAll('[data-ofs]'),function(r){var t=r.querySelector('.ofs-track'),d=[].slice.call(r.querySelectorAll('.ofs-dots button')),n=t.children.length,i=0,pause=false,tm;if(n<2)return;
  function go(k){i=(k+n)%n;t.scrollTo({left:i*t.clientWidth,behavior:'smooth'})}
  function mark(){var k=Math.round(t.scrollLeft/Math.max(1,t.clientWidth));d.forEach(function(b,j){b.classList.toggle('on',j===k)});[].forEach.call(t.children,function(s,j){s.setAttribute('aria-hidden',j===k?'false':'true');s.classList.toggle('on',j===k);s.tabIndex=j===k?0:-1});i=k}
  t.addEventListener('scroll',function(){clearTimeout(t._s);t._s=setTimeout(mark,80)},{passive:true});
  d.forEach(function(b,j){b.addEventListener('click',function(){go(j)})});
  r.addEventListener('mouseenter',function(){pause=true});r.addEventListener('mouseleave',function(){pause=false});
  r.addEventListener('touchstart',function(){pause=true;clearTimeout(r._p);r._p=setTimeout(function(){pause=false},6000)},{passive:true});
  if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches)tm=setInterval(function(){if(!pause&&!document.hidden)go(i+1)},4500);
})})();
