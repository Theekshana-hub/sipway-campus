<?php
// ===== launch_countdown.php =====
// Settings - methanin wenas karanna puluwan
$LC_TITLE     = 'Sipway Campus';              // project name
$LC_SUBTITLE  = 'Our New Project Starts In';  // countdown eka kalin text
$LC_ONCE      = true;                         // true = browser session ekakata once. false = hama refresh ekakatama
?>
<style>
  .lc-overlay{
    position:fixed; inset:0; z-index:10000;
    background:radial-gradient(circle at 50% 40%, #3b1d8f 0%, #1a1440 45%, #0b0a1f 100%);
    display:flex; align-items:center; justify-content:center; flex-direction:column;
    overflow:hidden; color:#fff; font-family:'Inter','Noto Sans Sinhala',sans-serif;
    opacity:1; transition:opacity .7s ease, transform .7s ease;
  }
  .lc-overlay.lc-hide{ opacity:0; transform:scale(1.06); pointer-events:none; }
  #lcCanvas{ position:absolute; inset:0; width:100%; height:100%; }

  .lc-skip{
    position:absolute; top:20px; right:20px; z-index:5;
    padding:9px 18px; border-radius:999px; cursor:pointer;
    background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2);
    color:#e0e7ff; font-weight:700; font-size:13px; transition:background .2s;
  }
  .lc-skip:hover{ background:rgba(255,255,255,.2); }
  .lc-launching .lc-skip{ opacity:0; pointer-events:none; }

  .lc-stage{ position:relative; z-index:2; text-align:center; padding:20px; width:100%; max-width:560px; }
  .lc-eyebrow{
    font-size:13px; font-weight:800; letter-spacing:3px; text-transform:uppercase;
    color:#c4b5fd; margin-bottom:10px; animation:lcFade 1s ease both;
  }
  .lc-title{
    font-size:clamp(26px,5vw,40px); font-weight:800; letter-spacing:-.5px; margin-bottom:28px;
    background:linear-gradient(135deg,#fff,#c4b5fd 50%,#f9a8d4);
    -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent;
    animation:lcFade 1s .15s ease both;
  }

  .lc-ring-wrap{
    position:relative; width:min(260px,62vw); aspect-ratio:1; margin:0 auto;
    transition:opacity .5s ease, transform .5s ease;
    filter:drop-shadow(0 0 30px rgba(168,85,247,.55));
  }
  .lc-ring-wrap svg{ width:100%; height:100%; }
  .lc-ring-track{ fill:none; stroke:rgba(255,255,255,.1); stroke-width:8; }
  .lc-ring-prog{
    fill:none; stroke:url(#lcGrad); stroke-width:8; stroke-linecap:round;
    transition:stroke-dashoffset 1s linear;
  }
  .lc-number{
    position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
    font-size:clamp(70px,22vw,120px); font-weight:800; letter-spacing:-3px;
    text-shadow:0 0 40px rgba(236,72,153,.7);
  }
  .lc-number.pop{ animation:lcPop .9s cubic-bezier(.2,1.3,.4,1) both; }
  .lc-hot .lc-number{ color:#fda4af; text-shadow:0 0 50px rgba(244,63,94,.9); }
  .lc-hot .lc-ring-wrap{ animation:lcShake .35s infinite; }

  .lc-label{ margin-top:26px; font-size:15px; font-weight:600; color:#ddd6fe; min-height:22px; transition:opacity .3s; }
  .lc-sub{ margin-top:6px; font-size:12px; color:#a5b4fc; letter-spacing:.5px; }
  .lc-launching .lc-ring-wrap,
  .lc-launching .lc-label,
  .lc-launching .lc-sub,
  .lc-launching .lc-eyebrow,
  .lc-launching .lc-title{ opacity:0; transform:scale(.8); pointer-events:none; }
  .lc-launching .lc-ring-wrap,
  .lc-launching .lc-title,
  .lc-launching .lc-eyebrow{ position:relative; }
  .lc-launched .lc-ring-wrap,
  .lc-launched .lc-label,
  .lc-launched .lc-sub,
  .lc-launched .lc-eyebrow,
  .lc-launched .lc-title{ display:none; }

  /* Launch panel */
  .lc-launch{ display:none; text-align:center; }
  .lc-launched .lc-launch{ display:block; animation:lcBoom .9s cubic-bezier(.2,1.3,.4,1) both; }
  .lc-rocket{ font-size:78px; display:inline-block; animation:lcRocket 2.2s ease-in-out infinite; }
  .lc-live{
    font-size:clamp(34px,8vw,60px); font-weight:800; letter-spacing:-1px; margin:8px 0 6px;
    background:linear-gradient(135deg,#fde68a,#f472b6,#a78bfa);
    -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent;
  }
  .lc-live-sub{ font-size:16px; color:#e0e7ff; font-weight:600; margin-bottom:26px; }
  .lc-enter{
    padding:15px 34px; border:none; border-radius:14px; cursor:pointer;
    background:linear-gradient(135deg,#a855f7,#ec4899); color:#fff;
    font-weight:800; font-size:15px; letter-spacing:.3px;
    box-shadow:0 14px 34px -8px rgba(236,72,153,.6); transition:transform .15s, filter .2s;
  }
  .lc-enter:hover{ transform:translateY(-2px); filter:brightness(1.08); }

  /* Flash + shockwave */
  .lc-flash{ position:absolute; inset:0; background:#fff; opacity:0; pointer-events:none; z-index:4; }
  .lc-launching .lc-flash{ animation:lcFlash 1.1s ease-out both; }
  .lc-shock{
    position:absolute; left:50%; top:50%; width:100px; height:100px; margin:-50px 0 0 -50px;
    border-radius:50%; border:4px solid rgba(255,255,255,.9); opacity:0; z-index:3; pointer-events:none;
  }
  .lc-launching .lc-shock{ animation:lcShock 1.3s ease-out both; }
  .lc-launching .lc-shock.s2{ animation-delay:.2s; border-color:rgba(244,114,182,.9); }

  @keyframes lcFade{ from{opacity:0; transform:translateY(12px);} to{opacity:1; transform:none;} }
  @keyframes lcPop{ 0%{opacity:0; transform:scale(1.9);} 40%{opacity:1;} 100%{opacity:1; transform:scale(1);} }
  @keyframes lcShake{ 0%,100%{transform:translate(0,0);} 25%{transform:translate(-2px,1px);} 75%{transform:translate(2px,-1px);} }
  @keyframes lcFlash{ 0%{opacity:0;} 15%{opacity:1;} 100%{opacity:0;} }
  @keyframes lcShock{ 0%{opacity:1; transform:scale(.2);} 100%{opacity:0; transform:scale(14);} }
  @keyframes lcBoom{ from{opacity:0; transform:scale(.4);} to{opacity:1; transform:scale(1);} }
  @keyframes lcRocket{ 0%,100%{transform:translateY(0) rotate(-6deg);} 50%{transform:translateY(-14px) rotate(6deg);} }

  @media (prefers-reduced-motion: reduce){
    .lc-hot .lc-ring-wrap, .lc-rocket{ animation:none; }
  }
</style>

<div class="lc-overlay" id="lcOverlay" role="dialog" aria-modal="true" aria-label="Project launch countdown">
  <canvas id="lcCanvas"></canvas>
  <div class="lc-flash"></div>
  <div class="lc-shock"></div>
  <div class="lc-shock s2"></div>

  <button type="button" class="lc-skip" id="lcSkip">Skip ›</button>

  <div class="lc-stage">
    <div class="lc-eyebrow"><?php echo htmlspecialchars($LC_SUBTITLE); ?></div>
    <h1 class="lc-title"><?php echo htmlspecialchars($LC_TITLE); ?></h1>

    <div class="lc-ring-wrap">
      <svg viewBox="0 0 200 200">
        <defs>
          <linearGradient id="lcGrad" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#a855f7"/>
            <stop offset="100%" stop-color="#ec4899"/>
          </linearGradient>
        </defs>
        <circle class="lc-ring-track" cx="100" cy="100" r="90"/>
        <circle class="lc-ring-prog" id="lcRing" cx="100" cy="100" r="90" transform="rotate(-90 100 100)"/>
      </svg>
      <div class="lc-number" id="lcNumber">10</div>
    </div>

    <div class="lc-label" id="lcLabel"></div>
    <div class="lc-sub">Get ready…</div>

    <div class="lc-launch">
      <div class="lc-rocket">🚀</div>
      <div class="lc-live">We're Live!</div>
      <div class="lc-live-sub"><?php echo htmlspecialchars($LC_TITLE); ?> – New Project Launched 🎉</div>
      <button type="button" class="lc-enter" id="lcEnter">Enter Dashboard →</button>
    </div>
  </div>
</div>

<script>
(function(){
  var overlay = document.getElementById('lcOverlay');
  if (!overlay) return;

  var ONCE  = <?php echo $LC_ONCE ? 'true' : 'false'; ?>;
  var FROM  = 10;
  var AUTO_CLOSE_MS = 7000;

  // ?launch=1 dalaa URL eka open karoth hama welawema pennanawa (test karanna)
  var force = /[?&]launch=1\b/.test(location.search);
  var seen = false;
  try { seen = sessionStorage.getItem('lc_seen') === '1'; } catch(e) {}
  if (ONCE && seen && !force) { overlay.parentNode.removeChild(overlay); return; }

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var prevOverflow = document.body.style.overflow;
  document.body.style.overflow = 'hidden';

  var numEl   = document.getElementById('lcNumber');
  var labelEl = document.getElementById('lcLabel');
  var ringEl  = document.getElementById('lcRing');
  var cv      = document.getElementById('lcCanvas');
  var ctx     = cv.getContext('2d');

  var MSG = [
    'Ignition! 🔥', 'Final check…', 'Almost there…', 'Systems online', 'Loading lecturers…',
    'Syncing calendars…', 'Warming up sessions…', 'Connecting students…',
    'Preparing dashboard…', 'Initializing…', 'Starting up…'
  ];

  var R = 90, C = 2 * Math.PI * R;
  ringEl.style.strokeDasharray = C;
  ringEl.style.strokeDashoffset = 0;

  // ---------- Canvas (starfield + fireworks + confetti) ----------
  var W, H, dpr, raf, speed = reduce ? 0.002 : 0.004, closed = false;
  var stars = [], parts = [];
  var STAR_COUNT = reduce ? 60 : 160;

  function resize(){
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    W = window.innerWidth; H = window.innerHeight;
    cv.width = W * dpr; cv.height = H * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }
  function newStar(z){
    return { x: Math.random()*2-1, y: Math.random()*2-1, z: z !== undefined ? z : Math.random() };
  }
  function initStars(){ stars = []; for (var i=0;i<STAR_COUNT;i++) stars.push(newStar()); }

  function burst(x, y, n){
    var hue = Math.random()*360;
    for (var i=0;i<n;i++){
      var a = Math.random()*Math.PI*2, s = Math.random()*6 + 2;
      parts.push({
        x:x, y:y, vx:Math.cos(a)*s, vy:Math.sin(a)*s, life:1,
        decay:0.008 + Math.random()*0.01, r:1.5 + Math.random()*2,
        c:'hsl(' + (hue + Math.random()*50) + ',100%,' + (55 + Math.random()*20) + '%)', conf:false
      });
    }
  }
  var PALETTE = ['#a855f7','#ec4899','#fbbf24','#34d399','#60a5fa','#f472b6','#fff'];
  function confetti(n){
    for (var i=0;i<n;i++){
      parts.push({
        x:Math.random()*W, y:-20 - Math.random()*H*0.4,
        vx:(Math.random()-.5)*2, vy:2 + Math.random()*3.5, life:1, decay:0.002,
        w:6 + Math.random()*6, h:3 + Math.random()*4, rot:Math.random()*6, vr:(Math.random()-.5)*0.3,
        c:PALETTE[(Math.random()*PALETTE.length)|0], conf:true
      });
    }
  }

  function frame(){
    ctx.clearRect(0, 0, W, H);
    var cx = W/2, cy = H/2, k = cx * 0.4;

    ctx.globalCompositeOperation = 'source-over';
    for (var i=0;i<stars.length;i++){
      var s = stars[i];
      s.z -= speed;
      if (s.z <= 0.02){ stars[i] = newStar(1); continue; }
      var sx = cx + s.x / s.z * k, sy = cy + s.y / s.z * k;
      var pz = Math.min(1, s.z + speed * 2.5);
      var px = cx + s.x / pz * k, py = cy + s.y / pz * k;
      if (sx < -50 || sx > W+50 || sy < -50 || sy > H+50){ stars[i] = newStar(1); continue; }
      ctx.strokeStyle = 'rgba(255,255,255,' + Math.min(1, (1 - s.z) + 0.2) + ')';
      ctx.lineWidth = (1 - s.z) * 2.2 + 0.3;
      ctx.beginPath(); ctx.moveTo(px, py); ctx.lineTo(sx, sy); ctx.stroke();
    }

    for (var j=parts.length-1; j>=0; j--){
      var p = parts[j];
      p.life -= p.decay;
      if (p.life <= 0 || p.y > H + 30){ parts.splice(j,1); continue; }
      if (p.conf){
        p.x += p.vx; p.y += p.vy; p.rot += p.vr; p.vx += Math.sin(p.rot)*0.05;
        ctx.globalCompositeOperation = 'source-over';
        ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.rot);
        ctx.fillStyle = p.c; ctx.fillRect(-p.w/2, -p.h/2, p.w, p.h); ctx.restore();
      } else {
        p.vy += 0.06; p.vx *= 0.985; p.vy *= 0.985;
        p.x += p.vx; p.y += p.vy;
        ctx.globalCompositeOperation = 'lighter';
        ctx.globalAlpha = Math.max(p.life, 0);
        ctx.fillStyle = p.c;
        ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI*2); ctx.fill();
        ctx.globalAlpha = 1;
      }
    }
    ctx.globalCompositeOperation = 'source-over';
    raf = requestAnimationFrame(frame);
  }

  // ---------- Countdown ----------
  var timer, autoT, burstInt;

  function tick(n){
    if (closed) return;
    numEl.textContent = n;
    numEl.classList.remove('pop'); void numEl.offsetWidth; numEl.classList.add('pop');
    labelEl.textContent = MSG[n] || '';
    ringEl.style.strokeDashoffset = C * (1 - Math.max(n - 1, 0) / FROM);
    if (!reduce) speed = 0.004 + (FROM - n) * 0.0042;   // warp speed wadi wenawa
    if (n <= 3) overlay.classList.add('lc-hot');

    if (n === 0){ launch(); }
    else { timer = setTimeout(function(){ tick(n - 1); }, 1000); }
  }

  function launch(){
    overlay.classList.remove('lc-hot');
    overlay.classList.add('lc-launching');
    speed = reduce ? 0.004 : 0.05;
    burst(W/2, H/2, reduce ? 40 : 120);

    setTimeout(function(){ speed = reduce ? 0.002 : 0.006; }, 900);
    setTimeout(function(){
      overlay.classList.add('lc-launched');
      confetti(reduce ? 40 : 170);
    }, 900);

    var count = 0;
    burstInt = setInterval(function(){
      if (closed || count++ > 12){ clearInterval(burstInt); return; }
      burst(W*(0.15 + Math.random()*0.7), H*(0.15 + Math.random()*0.5), reduce ? 25 : 70);
      if (count % 4 === 0) confetti(40);
    }, 380);

    autoT = setTimeout(closeOverlay, AUTO_CLOSE_MS + 900);
  }

  function closeOverlay(){
    if (closed) return;
    closed = true;
    clearTimeout(timer); clearTimeout(autoT); clearInterval(burstInt);
    try { sessionStorage.setItem('lc_seen', '1'); } catch(e) {}
    overlay.classList.add('lc-hide');
    setTimeout(function(){
      cancelAnimationFrame(raf);
      window.removeEventListener('resize', resize);
      document.removeEventListener('keydown', onKey);
      if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
      document.body.style.overflow = prevOverflow;
    }, 750);
  }
  function onKey(e){ if (e.key === 'Escape') closeOverlay(); }

  document.getElementById('lcSkip').addEventListener('click', closeOverlay);
  document.getElementById('lcEnter').addEventListener('click', closeOverlay);
  document.addEventListener('keydown', onKey);
  window.addEventListener('resize', resize);

  resize(); initStars(); frame();
  setTimeout(function(){ tick(FROM); }, 600);
})();
</script>