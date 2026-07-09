document.addEventListener('DOMContentLoaded',()=>{

/* FAQ Accordion */
document.querySelectorAll('.faq-question').forEach(btn=>{
  btn.addEventListener('click',()=>{
    const item=btn.closest('.faq-item');
    const isOpen=item.classList.contains('open');
    document.querySelectorAll('.faq-item.open').forEach(el=>el.classList.remove('open'));
    if(!isOpen)item.classList.add('open');
  });
});

/* Smooth hash scroll — skip bare '#' links */
document.querySelectorAll('a[href^="#"]').forEach(a=>{
  const h=a.getAttribute('href');
  if(!h||h==='#')return;
  a.addEventListener('click',e=>{
    const target=document.querySelector(h);
    if(target){e.preventDefault();target.scrollIntoView({behavior:'smooth'})}
  });
});

/* Nav scroll effect */
const nav=document.getElementById('navbar');
let lastScroll=0;
window.addEventListener('scroll',()=>{
  const y=window.scrollY;
  nav.classList.toggle('scrolled',y>60);
  lastScroll=y;
},{passive:true});

/* Hero canvas connection lines — skip on v2 landing page */
(function initHeroCanvas(){
  const canvas=document.getElementById('heroCanvas');
  if(!canvas)return;
  if(document.querySelector('.sphere-ring'))return;
  const ctx=canvas.getContext('2d');
  let w,h,pts=[];
  function resize(){
    w=canvas.width=window.innerWidth;
    h=canvas.height=window.innerHeight;
    pts=[];
    const n=Math.min(Math.floor((w*h)/12000),60);
    for(let i=0;i<n;i++)pts.push({x:Math.random()*w,y:Math.random()*h,vx:(Math.random()-0.5)*0.3,vy:(Math.random()-0.5)*0.3});
  }
  resize();
  let mouse={x:null,y:null};
  window.addEventListener('mousemove',e=>{mouse.x=e.clientX;mouse.y=e.clientY});
  window.addEventListener('mouseleave',()=>{mouse.x=null;mouse.y=null});
  function draw(){
    ctx.clearRect(0,0,w,h);
    for(let i=0;i<pts.length;i++){
      const p=pts[i];
      p.x+=p.vx;p.y+=p.vy;
      if(p.x<0||p.x>w)p.vx*=-1;
      if(p.y<0||p.y>h)p.vy*=-1;
      let near=false;
      if(mouse.x!==null){
        const dx=mouse.x-p.x,dy=mouse.y-p.y;
        if(dx*dx+dy*dy<36000){near=true;ctx.beginPath();ctx.moveTo(p.x,p.y);ctx.lineTo(mouse.x,mouse.y);ctx.strokeStyle='rgba(255,255,255,0.04)';ctx.lineWidth=1;ctx.stroke()}
      }
      for(let j=i+1;j<pts.length;j++){
        const q=pts[j],dx=p.x-q.x,dy=p.y-q.y;
        if(dx*dx+dy*dy<18000){ctx.beginPath();ctx.moveTo(p.x,p.y);ctx.lineTo(q.x,q.y);ctx.strokeStyle=`rgba(255,255,255,${0.03+Math.random()*0.02})`;ctx.lineWidth=0.5;ctx.stroke()}
      }
      ctx.beginPath();ctx.arc(p.x,p.y,near?2:1.5,0,Math.PI*2);
      ctx.fillStyle=near?'rgba(255,255,255,0.5)':'rgba(255,255,255,0.2)';ctx.fill();
    }
    requestAnimationFrame(draw);
  }
  draw();
  window.addEventListener('resize',resize);
})();

/* Animate hero stats on scroll */
const statsObserver=new IntersectionObserver(entries=>{
  entries.forEach(entry=>{
    if(entry.isIntersecting){
      entry.target.querySelectorAll('.stat-num').forEach(el=>{
        el.classList.add('visible');
      });
      statsObserver.unobserve(entry.target);
    }
  });
},{threshold:0.5});
const heroStats=document.querySelector('.hero-stats');
if(heroStats)statsObserver.observe(heroStats);

/* Scroll-triggered entrance animations */
const appearObserver=new IntersectionObserver(entries=>{
  entries.forEach(entry=>{
    if(entry.isIntersecting){
      entry.target.classList.add('visible');
      appearObserver.unobserve(entry.target);
    }
  });
},{threshold:0.08});
document.querySelectorAll('.feature-card,.security-step,.tech-item,.faq-item').forEach(el=>{
  el.classList.add('appear');
  appearObserver.observe(el);
});

/* Hero scroll indicator fade */
const heroScroll=document.getElementById('heroScroll');
if(heroScroll){
  window.addEventListener('scroll',()=>{
    heroScroll.style.opacity=Math.max(0,1-window.scrollY/200);
  },{passive:true});
}

/* Count-up animation */
function animateCountUp(el,target){
  if(!target)return;
  const isNum=!isNaN(target);
  let current=0;
  const step=Math.ceil(target/40);
  const t=setInterval(()=>{
    current+=step;
    if(current>=target){current=target;clearInterval(t)}
    el.textContent=isNum?current.toLocaleString():target;
  },30);
}

/* Shield card click */
document.querySelectorAll('.shield-card').forEach(el=>{
  el.addEventListener('click',()=>{
    const href=el.dataset.href;
    if(href)window.location.href=href;
  });
});

/* Nav mobile toggle */
const toggle=document.getElementById('navMobileToggle');
if(toggle){
  toggle.addEventListener('click',()=>{
    nav.classList.toggle('nav-open');
  });
  document.querySelectorAll('.nav-links a').forEach(a=>{
    a.addEventListener('click',()=>{
      nav.classList.remove('nav-open');
    });
  });
}
document.addEventListener('click',e=>{
  if(nav&&!nav.contains(e.target))nav.classList.remove('nav-open');
});
});
