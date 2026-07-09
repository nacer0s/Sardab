<?php
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$dir = str_replace('\\', '/', __DIR__);
$basePath = $docRoot !== '' && strpos($dir, $docRoot) === 0 ? rtrim(substr($dir, strlen($docRoot)), '/') : '';

// Static file handler
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#\.(css|js|svg|png|jpg|jpeg|webp|ico|json|woff2?|ttf|otf|txt|wasm|mp4|webm|ogg|mp3)(\?.*)?$#', $requestUri)) {
  $relPath = $requestUri;
  if ($basePath !== '' && strpos($relPath, $basePath) === 0) $relPath = substr($relPath, strlen($basePath));
  $file = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
  if (file_exists($file) && is_file($file)) {
    $mime = ['css'=>'text/css','js'=>'application/javascript','svg'=>'image/svg+xml','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','webp'=>'image/webp','ico'=>'image/x-icon','json'=>'application/json','woff2'=>'font/woff2','woff'=>'font/woff','ttf'=>'font/ttf','otf'=>'font/otf','txt'=>'text/plain','mp4'=>'video/mp4','webm'=>'video/webm','ogg'=>'audio/ogg','mp3'=>'audio/mpeg','wasm'=>'application/wasm'];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($mime[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($file));
    if ($ext === 'svg') header('Cache-Control: public, max-age=31536000, immutable');
    elseif (in_array($ext, ['css','js'])) header('Cache-Control: public, max-age=2592000');
    else header('Cache-Control: public, max-age=86400');
    readfile($file); exit;
  }
}

if (preg_match('#^/app/(chat|voice|video|meet)/(.+)\.php$#', $requestUri, $m)) {
  $phpFile = __DIR__ . '/app/' . $m[1] . '/' . $m[2] . '.php';
  if (file_exists($phpFile)) { require $phpFile; exit; }
  http_response_code(404); exit;
}

if (preg_match('#^/app/(chat|voice|video|meet)(/.*)?$#', $requestUri, $m)) {
  $appFile = __DIR__ . '/app/' . $m[1] . '/index.php';
  if (file_exists($appFile)) { require $appFile; exit; }
}

if ($requestUri === '/app/' || $requestUri === '/app') {
  require __DIR__ . '/app/index.php'; exit;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=5.0">
<title data-i18n="page.title">Sardab — Encrypted P2P Communication</title>
<meta name="description" content="Zero-knowledge encrypted peer-to-peer communication. Chat, voice, video, and meetings — fully private, no server ever sees your data.">
<meta name="keywords" content="encrypted chat, p2p communication, webrtc, video calls, secure messaging, zero knowledge, privacy">
<link rel="icon" type="image/svg+xml" href="<?=$basePath?>/favicon.svg">
<link rel="manifest" href="<?=$basePath?>/manifest.json">
<meta name="theme-color" content="#050508">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta property="og:title" content="Sardab — Encrypted P2P Communication">
<meta property="og:description" content="Zero-knowledge encrypted peer-to-peer communication. Your data never touches our servers.">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://kit.fontawesome.com/835a2ccfff.js" crossorigin="anonymous"></script>
<link rel="stylesheet" href="<?=$basePath?>/assets/css/style.css?v=7">
<style>
/* ===================== LANDING V2 ===================== */

/* Hero – Network Sphere */
.hero-v2{position:relative;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:100px 24px 60px;overflow:hidden}
.hero-v2-bg{position:absolute;inset:0;pointer-events:none}
.hero-v2-glow{position:absolute;border-radius:50%;filter:blur(100px);animation:glowDrift 8s ease-in-out infinite}
.hero-v2-glow.g1{width:700px;height:700px;top:-15%;left:-10%;background:radial-gradient(circle,rgba(120,120,240,0.10),transparent 70%);animation-delay:0s}
.hero-v2-glow.g2{width:500px;height:500px;bottom:-10%;right:-8%;background:radial-gradient(circle,rgba(80,80,200,0.08),transparent 70%);animation-delay:3s}
.hero-v2-glow.g3{width:400px;height:400px;top:30%;left:60%;background:radial-gradient(circle,rgba(160,160,255,0.06),transparent 70%);animation-delay:6s}
@keyframes glowDrift{0%,100%{opacity:0.5;transform:scale(1) translate(0,0)}33%{opacity:0.8;transform:scale(1.1) translate(20px,-20px)}66%{opacity:0.6;transform:scale(0.95) translate(-10px,15px)}}
.hero-v2-canvas{position:absolute;inset:0;pointer-events:none;z-index:1}
.hero-v2-content{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:1.1fr 1fr;gap:60px;align-items:center;width:100%;position:relative;z-index:2}
.hero-v2-text{z-index:2}
.hero-v2-text h1{font-size:clamp(34px,5vw,60px);font-weight:700;line-height:1.05;margin-bottom:18px;letter-spacing:-1.2px}
.hero-v2-text h1 .grad{background:linear-gradient(135deg,#f0f0f5 20%,#a8a8f0 50%,#7a7ae0 80%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero-v2-tag{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:600;padding:5px 12px;border-radius:20px;background:rgba(120,120,220,0.08);border:1px solid rgba(120,120,220,0.2);color:var(--text3);margin-bottom:18px;letter-spacing:0.5px;text-transform:uppercase;animation:fadeSlideUp 0.7s ease both}
.hero-v2-tag i{color:rgba(120,120,220,0.8);font-size:10px}
.hero-v2-text p{font-size:16px;color:var(--text3);max-width:480px;margin-bottom:28px;line-height:1.7;animation:fadeSlideUp 0.7s 0.1s ease both}
.hero-v2-actions{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:36px;animation:fadeSlideUp 0.7s 0.15s ease both}
.hero-v2-metrics{display:flex;gap:32px;padding-top:12px;border-top:1px solid var(--border);animation:fadeSlideUp 0.7s 0.2s ease both}
.hero-v2-metric{display:flex;flex-direction:column;gap:1px}
.hero-v2-metric-num{font-size:16px;font-weight:600;font-family:var(--mono);color:var(--text)}
.hero-v2-metric-label{font-size:11px;color:var(--text4);text-transform:uppercase;letter-spacing:0.5px}

/* Network Sphere - Enhanced */
.hero-v2-sphere{position:relative;width:320px;height:320px;margin:0 auto;display:flex;align-items:center;justify-content:center;animation:fadeSlideUp 0.7s 0.25s ease both}
.hero-v2-sphere:hover{animation:fadeSlideUp 0.7s 0.25s ease both,sphereWobble 6s ease-in-out infinite}
@keyframes sphereWobble{0%,100%{transform:rotate(0deg)}25%{transform:rotate(1.5deg)}50%{transform:rotate(-1deg)}75%{transform:rotate(0.5deg)}}
.sphere-ring{position:absolute;border-radius:50%;border:1px solid;backface-visibility:visible}
.sphere-ring.r1{width:320px;height:320px;border-color:rgba(120,120,220,0.06);animation:sphereSpin 22s linear infinite}
.sphere-ring.r2{width:240px;height:240px;border-color:rgba(120,120,220,0.12);animation:sphereSpin 17s linear infinite reverse}
.sphere-ring.r3{width:160px;height:160px;border-color:rgba(120,120,220,0.18);animation:sphereSpin 13s linear infinite}
.sphere-ring.r4{width:80px;height:80px;border-color:rgba(120,120,220,0.25);animation:sphereSpin 9s linear infinite reverse}
@keyframes sphereSpin{to{transform:rotate(360deg)}}
.sphere-node{position:absolute;width:8px;height:8px;border-radius:50%;background:rgba(120,120,220,0.6);animation:nodePulse 3s ease-in-out infinite;box-shadow:0 0 6px rgba(120,120,220,0.2)}
.sphere-node:nth-child(5){top:10%;left:50%;transform:translateX(-50%);animation-delay:0s}
.sphere-node:nth-child(6){top:50%;right:8%;transform:translateY(-50%);animation-delay:0.5s}
.sphere-node:nth-child(7){bottom:10%;left:50%;transform:translateX(-50%);animation-delay:1s}
.sphere-node:nth-child(8){top:50%;left:8%;transform:translateY(-50%);animation-delay:1.5s}
.sphere-node:nth-child(9){top:22%;right:22%;animation-delay:2s}
.sphere-node:nth-child(10){bottom:22%;left:22%;animation-delay:2.5s}
@keyframes nodePulse{0%,100%{opacity:0.3;transform:scale(1)}30%{opacity:0.8;transform:scale(1.6);box-shadow:0 0 14px rgba(120,120,220,0.5)}60%{opacity:0.5;transform:scale(1.2)}}
.sphere-center{position:absolute;width:64px;height:64px;border-radius:50%;background:var(--card);border:1px solid rgba(120,120,220,0.3);display:flex;align-items:center;justify-content:center;z-index:3;font-size:22px;color:var(--text);box-shadow:0 0 50px rgba(120,120,220,0.12);animation:centerGlow 4s ease-in-out infinite}
@keyframes centerGlow{0%,100%{box-shadow:0 0 30px rgba(120,120,220,0.1)}50%{box-shadow:0 0 60px rgba(120,120,220,0.2);border-color:rgba(120,120,220,0.45)}}
.sphere-card{position:absolute;background:var(--card);border:1px solid var(--border);border-radius:var(--rsm);padding:14px 12px;display:flex;flex-direction:column;align-items:center;gap:6px;transition:var(--tr);cursor:pointer;min-width:68px;z-index:4}
.sphere-card:hover{background:var(--hover);border-color:var(--border2);transform:translateY(-5px) scale(1.06);box-shadow:0 12px 32px rgba(0,0,0,0.35)}
.sphere-card i{font-size:17px;color:var(--text)}
.sphere-card span{font-size:10px;font-weight:500;color:var(--text3);white-space:nowrap}
.sc-top{top:-8px;left:50%;transform:translateX(-50%)}
.sc-right{right:-8px;top:50%;transform:translateY(-50%)}
.sc-bot{bottom:-8px;left:50%;transform:translateX(-50%)}
.sc-left{left:-8px;top:50%;transform:translateY(-50%)}
.sc-top:hover{transform:translateX(-50%) translateY(-5px) scale(1.06)}
.sc-right:hover{transform:translateY(-50%) translateX(5px) scale(1.06)}
.sc-bot:hover{transform:translateX(-50%) translateY(5px) scale(1.06)}
.sc-left:hover{transform:translateY(-50%) translateX(-5px) scale(1.06)}

/* Floating particles behind sphere */
.hero-v2-particles{position:absolute;inset:0;pointer-events:none;overflow:hidden}
.hero-v2-particle{position:absolute;width:3px;height:3px;border-radius:50%;background:rgba(255,255,255,0.15);animation:particleFloat linear infinite}
@keyframes particleFloat{0%{transform:translateY(0) translateX(0);opacity:0}10%{opacity:1}90%{opacity:1}100%{transform:translateY(-100vh) translateX(20px);opacity:0}}

/* Brand Bar – Trusted by / Built with */
.brand-bar{display:flex;align-items:center;justify-content:center;gap:40px;padding:40px 24px;border-top:1px solid var(--border);flex-wrap:wrap;opacity:0.4;font-size:12px;color:var(--text4);text-transform:uppercase;letter-spacing:1px;font-weight:500}
.brand-bar i{font-size:18px;margin:0 8px;color:var(--text3)}
.brand-bar .bb-group{display:flex;align-items:center;gap:24px;flex-wrap:wrap}

/* Sections V2 */
.section-v2{padding:100px 24px;position:relative}
.section-v2-header{text-align:center;margin-bottom:48px}
.section-v2-header h2{font-size:clamp(24px,3.2vw,36px);font-weight:600;margin-bottom:10px;letter-spacing:-0.5px}
.section-v2-header p{font-size:15px;color:var(--text3);max-width:500px;margin:0 auto}
.section-v2-tag{display:inline-block;font-size:10px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--text4);margin-bottom:14px;padding:4px 14px;border:1px solid var(--border);border-radius:20px}

/* Features – Glass cards with gradient accents */
.features-v2{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px}
.feature-v2{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);display:flex;flex-direction:column;transition:var(--tr);overflow:hidden;position:relative}
.feature-v2::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;opacity:0;transition:var(--tr)}
.feature-v2:nth-child(1)::before{background:linear-gradient(90deg,#7c7ce0,#a8a8f0)}
.feature-v2:nth-child(2)::before{background:linear-gradient(90deg,#60a5fa,#93c5fd)}
.feature-v2:nth-child(3)::before{background:linear-gradient(90deg,#a78bfa,#c4b5fd)}
.feature-v2:nth-child(4)::before{background:linear-gradient(90deg,#f472b6,#f9a8d4)}
.feature-v2:hover::before{opacity:1}
.feature-v2:hover{background:var(--hover);border-color:var(--border2);transform:translateY(-5px);box-shadow:0 20px 50px rgba(0,0,0,0.4)}
.feature-v2-icon{padding:28px 24px 0;display:flex;align-items:center;gap:14px}
.feature-v2-icon i{font-size:20px;background:var(--card2);padding:11px;border-radius:var(--rsm);border:1px solid var(--border)}
.feature-v2:nth-child(1) .feature-v2-icon i{color:#a8a8f0}
.feature-v2:nth-child(2) .feature-v2-icon i{color:#93c5fd}
.feature-v2:nth-child(3) .feature-v2-icon i{color:#c4b5fd}
.feature-v2:nth-child(4) .feature-v2-icon i{color:#f9a8d4}
.feature-v2-body{padding:18px 24px 24px;display:flex;flex-direction:column;gap:8px;flex:1}
.feature-v2-body h3{font-size:17px;font-weight:600}
.feature-v2-body p{font-size:13px;color:var(--text3);line-height:1.6;flex:1}
.feature-v2-link{font-size:12px;font-weight:500;color:var(--text);display:flex;align-items:center;gap:6px;margin-top:4px;transition:var(--tr)}
.feature-v2:hover .feature-v2-link{gap:10px}

/* How it works – Connected steps */
.steps-v2{max-width:900px;margin:0 auto;display:flex;gap:0;position:relative}
.steps-v2::before{content:'';position:absolute;top:52px;left:60px;right:60px;height:1px;background:linear-gradient(90deg,var(--border),var(--border2),var(--border));z-index:0}
.step-v2{flex:1;text-align:center;padding:0 12px;position:relative;z-index:1}
.step-v2-num{width:40px;height:40px;border-radius:50%;border:1px solid var(--border);background:var(--bg);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;font-family:var(--mono);color:var(--text4);margin:0 auto 16px;transition:var(--tr)}
.step-v2:hover .step-v2-num{border-color:var(--text3);color:var(--text);background:var(--card)}
.step-v2-icon{font-size:22px;color:var(--text3);margin-bottom:10px}
.step-v2 h3{font-size:15px;font-weight:600;margin-bottom:6px}
.step-v2 p{font-size:12px;color:var(--text3);line-height:1.5;max-width:200px;margin:0 auto}
@media(max-width:768px){
  .steps-v2{flex-direction:column;gap:24px;max-width:400px;margin:0 auto}
  .steps-v2::before{display:none}
  .step-v2{display:flex;align-items:center;gap:16px;text-align:start}
  .step-v2-num{margin:0;flex-shrink:0}
  .step-v2 p{max-width:none}
}

/* Tech Pill */
.tech-v2{max-width:900px;margin:0 auto;display:flex;flex-wrap:wrap;gap:10px;justify-content:center}
.tech-v2-item{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:var(--card);border:1px solid var(--border);border-radius:20px;font-size:13px;font-weight:500;transition:var(--tr)}
.tech-v2-item:hover{background:var(--hover);border-color:var(--border2);transform:translateY(-2px)}
.tech-v2-item i{font-size:12px;color:var(--text4)}
.tech-v2-item .tt{font-weight:600;font-family:var(--mono);color:var(--text2)}
.tech-v2-item .td{color:var(--text4);font-size:12px}

/* Security – Layer cards */
.security-v2-grid{max-width:900px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px}
.security-v2-card{background:var(--card);border:1px solid var(--border);border-radius:var(--rsm);padding:24px 20px;transition:var(--tr);position:relative;overflow:hidden}
.security-v2-card:hover{background:var(--hover);border-color:var(--border2);transform:translateY(-3px)}
.security-v2-card .sec-icon{width:40px;height:40px;border-radius:12px;background:var(--card2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:16px;color:var(--text3);margin-bottom:12px}
.security-v2-card h3{font-size:15px;font-weight:600;margin-bottom:6px}
.security-v2-card p{font-size:13px;color:var(--text3);line-height:1.6}
.security-v2-card .sec-num{position:absolute;top:16px;right:16px;font-size:11px;font-weight:600;font-family:var(--mono);color:var(--text4);opacity:0.3}

/* FAQ V2 – clean compact */
.faq-v2{max-width:700px;margin:0 auto;display:flex;flex-direction:column;gap:8px}

/* CTA V2 */
.cta-v2-card{max-width:700px;margin:0 auto;text-align:center;background:linear-gradient(135deg,rgba(120,120,220,0.04),rgba(80,80,200,0.08));border:1px solid rgba(120,120,220,0.15);border-radius:var(--radius);padding:56px 40px;position:relative;overflow:hidden}
.cta-v2-card::before{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle at 50% 50%,rgba(120,120,220,0.03),transparent 60%);animation:ctaGlow 6s ease-in-out infinite}
@keyframes ctaGlow{0%,100%{opacity:0.5;transform:scale(1)}50%{opacity:1;transform:scale(1.1)}}
.cta-v2-card h2{font-size:clamp(24px,3vw,36px);font-weight:600;margin-bottom:12px;letter-spacing:-0.5px;position:relative;z-index:1}
.cta-v2-card p{font-size:15px;color:var(--text3);margin-bottom:28px;position:relative;z-index:1}
.cta-v2-card .cta-buttons{position:relative;z-index:1}

/* Gradient text animation */
.hero-v2-text h1 .grad{background-size:200% 200%;animation:gradShift 6s ease-in-out infinite}
@keyframes gradShift{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}

/* Sphere center glow on hover */
.hero-v2-sphere:hover .sphere-center{box-shadow:0 0 60px rgba(120,120,220,0.25);border-color:rgba(120,120,220,0.5);transform:scale(1.05);transition:var(--tr)}
.hero-v2-sphere:hover .sphere-ring{border-color:rgba(120,120,220,0.2)}
.hero-v2-sphere:hover .sphere-ring.r2{border-color:rgba(120,120,220,0.25)}
.hero-v2-sphere:hover .sphere-ring.r3{border-color:rgba(120,120,220,0.3)}
.hero-v2-sphere:hover .sphere-ring.r4{border-color:rgba(120,120,220,0.4)}

/* Sphere center pulse ring — smoother continuous wave */
.sphere-center{position:relative}
.sphere-center::after{content:'';position:absolute;inset:-8px;border-radius:50%;border:1px solid rgba(120,120,220,0.15);animation:pulseRing 3s cubic-bezier(0.4,0,0.2,1) infinite}
@keyframes pulseRing{0%{transform:scale(0.6);opacity:0}25%{opacity:0.7}60%{transform:scale(1.8);opacity:0}100%{opacity:0}}

/* Pulsing sphere nodes — delays already set via nth-child(5-10) in base styles */
@keyframes nodePulse{0%,100%{opacity:0.3;transform:scale(1)}50%{opacity:0.9;transform:scale(1.5);box-shadow:0 0 10px rgba(120,120,220,0.4)}}

/* Sphere card subtle orbit — uses animation-composition:add to preserve centering transform */
.sphere-card{animation:cardOrbit 14s ease-in-out infinite;animation-composition:add}
.sphere-card.sc-top{animation-delay:0s}
.sphere-card.sc-right{animation-delay:3.5s}
.sphere-card.sc-bot{animation-delay:7s}
.sphere-card.sc-left{animation-delay:10.5s}
@keyframes cardOrbit{0%,100%{transform:translate(0,0)}25%{transform:translate(6px,-3px)}50%{transform:translate(0,-9px)}75%{transform:translate(-6px,-3px)}}

/* Brand bar pills */
.brand-bar .bb-group{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:6px 16px;font-size:12px;font-weight:500;color:var(--text4);transition:var(--tr)}
.brand-bar .bb-group:hover{background:var(--hover);border-color:var(--border2);color:var(--text2);transform:translateY(-1px)}
.brand-bar .bb-group i{font-size:12px;color:var(--text3);margin-right:6px}
.brand-bar .bb-group.appear-v2:not(.visible){opacity:0;transform:translateY(8px)}
.brand-bar .bb-group.visible{opacity:1;transform:translateY(0)}

/* Step connector line highlight on hover */
.steps-v2:hover::before{background:linear-gradient(90deg,var(--border2),var(--text4),var(--border2))}

/* Floating gradient orbs */
.hero-v2-orb{position:absolute;border-radius:50%;filter:blur(80px);pointer-events:none;opacity:0.4;z-index:0}
.hero-v2-orb.o1{width:400px;height:400px;background:radial-gradient(circle,rgba(120,80,220,0.3),transparent 70%);top:-100px;left:-100px;animation:orbFloat 20s ease-in-out infinite}
.hero-v2-orb.o2{width:350px;height:350px;background:radial-gradient(circle,rgba(80,120,220,0.25),transparent 70%);bottom:-100px;right:-100px;animation:orbFloat 25s ease-in-out infinite reverse}
.hero-v2-orb.o3{width:300px;height:300px;background:radial-gradient(circle,rgba(160,60,200,0.2),transparent 70%);top:40%;left:30%;animation:orbFloat 30s ease-in-out infinite}
@keyframes orbFloat{0%,100%{transform:translate(0,0) scale(1)}25%{transform:translate(30px,-40px) scale(1.1)}50%{transform:translate(-20px,20px) scale(0.9)}75%{transform:translate(40px,30px) scale(1.05)}}

/* Step connector flowing dot */
.step-flow-dot{position:absolute;top:50px;width:6px;height:6px;border-radius:50%;background:var(--text4);z-index:1;opacity:0;animation:flowDot 3s ease-in-out infinite}
@keyframes flowDot{0%{left:60px;opacity:0}15%{opacity:0.7}85%{opacity:0.7}100%{left:calc(100% - 60px);opacity:0}}

/* Who It's For — role cards */
.who-v2{max-width:960px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px}
.who-v2-card{background:var(--card);border:1px solid var(--border);border-radius:var(--rsm);padding:28px 22px;text-align:center;transition:var(--tr);position:relative;overflow:hidden}
.who-v2-card:hover{background:var(--hover);border-color:var(--border2);transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,0.3)}
.who-v2-card .who-icon{width:56px;height:56px;border-radius:50%;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;font-size:20px;border:1px solid var(--border);background:var(--bg);transition:var(--tr)}
.who-v2-card:hover .who-icon{border-color:var(--border2);background:var(--card2);transform:scale(1.08)}
.who-v2-card:nth-child(1) .who-icon{color:#a8a8f0}
.who-v2-card:nth-child(2) .who-icon{color:#93c5fd}
.who-v2-card:nth-child(3) .who-icon{color:#c4b5fd}
.who-v2-card:nth-child(4) .who-icon{color:#f9a8d4}
.who-v2-card h3{font-size:15px;font-weight:600;margin-bottom:8px}
.who-v2-card p{font-size:13px;color:var(--text3);line-height:1.6}
.who-v2-card .who-num{position:absolute;top:14px;right:16px;font-size:10px;font-weight:600;font-family:var(--mono);color:var(--text4);opacity:0.2}
.who-v2-card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;opacity:0;transform:scaleX(0);transition:transform 0.4s ease,opacity 0.4s ease}
.who-v2-card:nth-child(1)::after{background:linear-gradient(90deg,#7c7ce0,#a8a8f0)}
.who-v2-card:nth-child(2)::after{background:linear-gradient(90deg,#60a5fa,#93c5fd)}
.who-v2-card:nth-child(3)::after{background:linear-gradient(90deg,#a78bfa,#c4b5fd)}
.who-v2-card:nth-child(4)::after{background:linear-gradient(90deg,#f472b6,#f9a8d4)}
.who-v2-card:hover::after{opacity:1;transform:scaleX(1)}

/* Sardab Difference — comparison cards */
.diff-v2{max-width:960px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px}
.diff-v2-card{background:var(--card);border:1px solid var(--border);border-radius:var(--rsm);padding:24px 20px;transition:var(--tr);position:relative}
.diff-v2-card:hover{background:var(--hover);border-color:var(--border2);transform:translateY(-3px);box-shadow:0 14px 36px rgba(0,0,0,0.25)}
.diff-v2-card .diff-header{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.diff-v2-card .diff-icon{width:36px;height:36px;border-radius:10px;background:var(--card2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
.diff-v2-card:nth-child(1) .diff-icon{color:#7c7ce0}
.diff-v2-card:nth-child(2) .diff-icon{color:#60a5fa}
.diff-v2-card:nth-child(3) .diff-icon{color:#a78bfa}
.diff-v2-card:nth-child(4) .diff-icon{color:#f472b6}
.diff-v2-card h3{font-size:14px;font-weight:600}
.diff-v2-card p{font-size:13px;color:var(--text3);line-height:1.6}
.diff-v2-card .diff-vs{display:flex;align-items:center;gap:8px;margin-top:12px;padding-top:12px;border-top:1px solid var(--border)}
.diff-v2-card .diff-vs span{font-size:11px;font-weight:500;padding:2px 8px;border-radius:4px}
.diff-v2-card .diff-vs .diff-yes{background:rgba(34,197,94,0.1);color:#22c55e}
.diff-v2-card .diff-vs .diff-no{background:rgba(239,68,68,0.1);color:#ef4444}

/* Circuit grid overlay */
.sec-circuit-bg{position:absolute;inset:0;background-image:linear-gradient(rgba(120,120,220,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(120,120,220,0.03) 1px,transparent 1px);background-size:40px 40px;pointer-events:none;z-index:0}

/* Hero scroll arrow accent */
#heroScroll{transition:opacity 0.6s}

/* Scroll animations */
.appear-v2{transition:0.7s cubic-bezier(0.22,1,0.36,1);transition-delay:var(--d,0s)}
.feature-v2.appear-v2:not(.visible){opacity:0;transform:translateY(30px)}
.feature-v2.visible{opacity:1;transform:translateY(0)}
.step-v2.appear-v2:not(.visible){opacity:0;transform:translateY(20px)}
.step-v2.visible{opacity:1;transform:translateY(0)}
.security-v2-card.appear-v2:not(.visible){opacity:0;transform:translateY(20px)}
.security-v2-card.visible{opacity:1;transform:translateY(0)}
.tech-v2-item.appear-v2:not(.visible){opacity:0;transform:translateY(10px)}
.tech-v2-item.visible{opacity:1;transform:translateY(0)}
.faq-item.appear-v2:not(.visible){opacity:0;transform:translateY(16px)}
.faq-item.visible{opacity:1;transform:translateY(0)}

/* Legal modal */
.footer-legal{margin-top:8px}
.footer-legal a{font-size:11px;color:var(--text4);text-decoration:none;transition:var(--tr)}
.footer-legal a:hover{color:var(--text2)}
#legalModal{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity 0.3s ease}
#legalModal.open{opacity:1;pointer-events:auto}
#legalModal .modal-overlay{position:absolute;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(8px)}
#legalModal .modal-wrap{position:relative;width:92%;max-width:780px;max-height:85vh;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);display:flex;flex-direction:column;overflow:hidden;box-shadow:0 32px 80px rgba(0,0,0,0.6);transform:translateY(24px) scale(0.97);transition:transform 0.35s cubic-bezier(0.22,1,0.36,1)}
#legalModal.open .modal-wrap{transform:translateY(0) scale(1)}
#legalModal .modal-header{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border);flex-shrink:0}
#legalModal .modal-header h3{font-size:16px;font-weight:600}
#legalModal .modal-close{padding:8px 14px;border-radius:10px;border:1px solid var(--border);background:var(--card);color:var(--text3);cursor:pointer;display:flex;align-items:center;gap:6px;transition:var(--tr);font-size:12px;font-weight:500}
#legalModal .modal-close:hover{background:var(--hover);border-color:var(--border2);color:var(--text);box-shadow:0 2px 12px rgba(0,0,0,0.2)}
#legalModal .modal-close i{font-size:14px}
#legalModal .modal-tabs{display:flex;gap:8px;padding:14px 24px;border-bottom:1px solid var(--border);flex-shrink:0}
#legalModal .modal-tab{padding:9px 20px;font-size:12px;font-weight:600;color:var(--text4);cursor:pointer;transition:var(--tr);background:var(--card);border:1px solid var(--border);border-radius:20px;letter-spacing:0.3px}
#legalModal .modal-tab:hover{color:var(--text2);border-color:var(--border2);background:var(--hover)}
#legalModal .modal-tab.active{color:var(--text);background:var(--card2);border-color:var(--text4)}
#legalModal .modal-body{overflow-y:auto;padding:28px 24px 16px;flex:1}
#legalModal .modal-body::-webkit-scrollbar{width:6px}
#legalModal .modal-body::-webkit-scrollbar-track{background:transparent}
#legalModal .modal-body::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
#legalModal .modal-body::-webkit-scrollbar-thumb:hover{background:var(--text4)}
#legalModal .modal-pane{display:none}
#legalModal .modal-pane.active{display:block}
#legalModal .modal-pane h4{font-size:14px;font-weight:600;margin:28px 0 8px;color:var(--text2);padding-bottom:4px;border-bottom:1px solid var(--border)}
#legalModal .modal-pane h4:first-child{margin-top:0}
#legalModal .modal-pane p{font-size:13px;color:var(--text3);line-height:1.8;margin-bottom:14px}
#legalModal .modal-pane p:last-child{margin-bottom:0}
#legalModal .modal-footer{display:flex;align-items:center;justify-content:center;padding:14px 24px 18px;border-top:1px solid var(--border);flex-shrink:0;gap:12px}
#legalModal .modal-footer button{padding:8px 24px;font-size:12px;font-weight:600;border-radius:20px;border:1px solid var(--border);background:var(--card);color:var(--text3);cursor:pointer;transition:var(--tr)}
#legalModal .modal-footer button:hover{background:var(--hover);border-color:var(--border2);color:var(--text)}

/* ===== CREATIVE FLOURISHES ===== */

/* Animated wave section dividers */
.section-v2{position:relative}
.section-divider{height:1px;background:linear-gradient(90deg,transparent,var(--border),var(--border2),var(--border),transparent);margin-bottom:48px;position:relative;overflow:hidden}
.section-divider::after{content:'';position:absolute;top:-4px;left:-100%;width:200%;height:9px;background:repeating-linear-gradient(90deg,transparent 0,transparent 20px,rgba(120,120,220,0.08) 20px,rgba(120,120,220,0.08) 40px);animation:dividerSweep 8s linear infinite;opacity:0;transition:opacity 0.5s}
.section-v2:hover .section-divider::after{opacity:1}
@keyframes dividerSweep{0%{transform:translateX(0)}100%{transform:translateX(50%)}}

/* Cursor glow follower — subtle radial gradient */
.cursor-glow{position:fixed;width:300px;height:300px;border-radius:50%;background:radial-gradient(circle,rgba(120,120,220,0.06),transparent 70%);pointer-events:none;z-index:9998;transform:translate(-50%,-50%);transition:opacity 0.3s;opacity:0}
html:hover .cursor-glow{opacity:1}

/* Section staggered gradient accent bars */
.features-v2,.who-v2,.security-v2-grid,.diff-v2{position:relative}

/* ===== MEET THE TEAM ===== */
.team-v2{max-width:820px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px}
.team-v2-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:32px 24px 24px;text-align:center;transition:var(--tr);position:relative;overflow:hidden}
.team-v2-card:hover{background:var(--hover);border-color:var(--border2);transform:translateY(-5px);box-shadow:0 20px 50px rgba(0,0,0,0.35)}
.team-v2-card .team-avatar{width:72px;height:72px;border-radius:50%;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:600;font-family:var(--mono);border:2px solid var(--border);background:var(--bg);transition:var(--tr);color:var(--text2);overflow:hidden}
.team-v2-card .team-avatar img{width:100%;height:100%;object-fit:cover;display:block}
.team-v2-card:hover .team-avatar img{transform:scale(1.06)}
.team-v2-card:hover .team-avatar{border-color:var(--border2);background:var(--card2);transform:scale(1.06)}
.team-v2-card:nth-child(1) .team-avatar{border-color:rgba(120,120,220,0.3);color:#a8a8f0}
.team-v2-card:nth-child(2) .team-avatar{border-color:rgba(96,165,250,0.3);color:#93c5fd}
.team-v2-card:nth-child(3) .team-avatar{border-color:rgba(167,139,250,0.3);color:#c4b5fd}
.team-v2-card h3{font-size:16px;font-weight:600;margin-bottom:2px}
.team-v2-card .team-role{font-size:11px;color:var(--text4);font-weight:500;margin-bottom:10px;text-transform:uppercase;letter-spacing:0.5px}
.team-v2-card p{font-size:13px;color:var(--text3);line-height:1.6}
.team-v2-card .team-links{display:flex;justify-content:center;gap:10px;margin-top:14px}
.team-v2-card .team-links a{width:32px;height:32px;border-radius:8px;border:1px solid var(--border);background:var(--bg);display:flex;align-items:center;justify-content:center;color:var(--text4);font-size:13px;transition:var(--tr)}
.team-v2-card .team-links a:hover{background:var(--hover);border-color:var(--border2);color:var(--text);transform:translateY(-2px)}
.team-supporter{margin-top:40px;text-align:center}
.team-supporter h4{font-size:12px;font-weight:500;color:var(--text4);text-transform:uppercase;letter-spacing:1px;margin-bottom:16px}
.supporter-v2{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
.supporter-card{display:flex;align-items:center;gap:12px;padding:12px 20px;border-radius:var(--radius);border:1px solid var(--border);background:var(--card);transition:var(--tr);text-decoration:none;min-width:200px}
.supporter-card:hover{background:var(--hover);border-color:var(--border2);transform:translateY(-3px);box-shadow:0 12px 30px rgba(0,0,0,0.2)}
.supporter-logo{width:42px;height:42px;border-radius:10px;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.supporter-logo img,.supporter-logo svg{width:100%;height:100%;object-fit:contain;display:block}
.supporter-info{text-align:left}
[dir="rtl"] .supporter-info{text-align:right}
.supporter-info strong{display:block;font-size:14px;color:var(--text);font-weight:600}
.supporter-info span{display:block;font-size:11px;color:var(--text4);margin-top:1px}

/* ===== ROADMAP ===== */
.roadmap-v2{max-width:780px;margin:0 auto;display:flex;flex-direction:column;gap:0;position:relative;padding-left:32px}
.roadmap-v2::before{content:'';position:absolute;left:11px;top:8px;bottom:8px;width:2px;background:linear-gradient(180deg,var(--border2),var(--text4),var(--border2));border-radius:1px}
.roadmap-v2-item{position:relative;padding:0 0 32px 20px;transition:var(--tr)}
.roadmap-v2-item:last-child{padding-bottom:0}
.roadmap-v2-item .rm-dot{position:absolute;left:-32px;top:6px;width:24px;height:24px;border-radius:50%;border:2px solid var(--border);background:var(--bg);display:flex;align-items:center;justify-content:center;z-index:1;transition:var(--tr)}
.roadmap-v2-item:hover .rm-dot{border-color:var(--text4);background:var(--card);transform:scale(1.1)}
.roadmap-v2-item .rm-dot i{font-size:10px;color:var(--text4)}
.roadmap-v2-item:nth-child(1) .rm-dot{border-color:rgba(120,120,220,0.4);color:#a8a8f0}
.roadmap-v2-item:nth-child(1) .rm-dot i{color:#a8a8f0}
.roadmap-v2-item:nth-child(2) .rm-dot{border-color:rgba(96,165,250,0.4)}
.roadmap-v2-item:nth-child(2) .rm-dot i{color:#93c5fd}
.roadmap-v2-item:nth-child(3) .rm-dot{border-color:rgba(167,139,250,0.4)}
.roadmap-v2-item:nth-child(3) .rm-dot i{color:#c4b5fd}
.roadmap-v2-item .rm-tag{display:inline-block;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:6px;padding:2px 10px;border-radius:10px;border:1px solid var(--border)}
.roadmap-v2-item:nth-child(1) .rm-tag{color:#a8a8f0;border-color:rgba(120,120,220,0.2);background:rgba(120,120,220,0.04)}
.roadmap-v2-item:nth-child(2) .rm-tag{color:#93c5fd;border-color:rgba(96,165,250,0.2);background:rgba(96,165,250,0.04)}
.roadmap-v2-item:nth-child(3) .rm-tag{color:#c4b5fd;border-color:rgba(167,139,250,0.2);background:rgba(167,139,250,0.04)}
.roadmap-v2-item h4{font-size:15px;font-weight:600;margin-bottom:6px}
.roadmap-v2-item p{font-size:13px;color:var(--text3);line-height:1.6}
.roadmap-v2-item ul{list-style:none;padding:0;margin:8px 0 0;display:flex;flex-wrap:wrap;gap:6px}
.roadmap-v2-item ul li{font-size:11px;padding:3px 10px;border-radius:12px;border:1px solid var(--border);background:var(--card);color:var(--text4);transition:var(--tr)}
.roadmap-v2-item ul li:hover{background:var(--hover);border-color:var(--border2);color:var(--text3)}

/* Responsive */
@media(max-width:900px){
  .hero-v2-content{grid-template-columns:1fr;gap:48px;text-align:center}
  .hero-v2-text p{margin-left:auto;margin-right:auto}
  .hero-v2-actions{justify-content:center}
  .hero-v2-metrics{justify-content:center}
  .hero-v2-sphere{order:-1;transform:scale(0.85)}
}
@media(max-width:600px){
  .section-v2{padding:60px 16px}
  .hero-v2{padding:80px 16px 40px}
  .hero-v2-text h1{font-size:28px}
  .hero-v2-actions{flex-direction:column;align-items:stretch}
  .hero-v2-actions .btn-primary,.hero-v2-actions .btn-secondary{text-align:center}
  .hero-v2-metrics{gap:20px}
  .hero-v2-sphere{transform:scale(0.7)}
  .features-v2{grid-template-columns:1fr}
  .security-v2-grid{grid-template-columns:1fr}
  .cta-v2-card{padding:36px 24px}
  .brand-bar{gap:24px;padding:24px 16px}
  .brand-bar .bb-group{gap:16px}
}
</style>
</head>
<body>
<div class="cursor-glow" id="cursorGlow"></div>
<div id="app">

<?php include __DIR__.'/includes/nav.php'; ?>

<!-- ========== HERO V2 ========== -->
<section class="hero-v2" id="hero">
  <div class="hero-v2-bg">
    <div class="hero-v2-glow g1"></div>
    <div class="hero-v2-glow g2"></div>
    <div class="hero-v2-glow g3"></div>
    <div class="hero-v2-orb o1"></div>
    <div class="hero-v2-orb o2"></div>
    <div class="hero-v2-orb o3"></div>
  </div>
  <div class="hero-v2-particles" id="heroParticles"></div>
  <canvas class="hero-v2-canvas" id="heroCanvas"></canvas>
  <div class="hero-v2-content">
    <div class="hero-v2-text">
      <span class="hero-v2-tag"><i class="fa-solid fa-shield-halved"></i> <span data-i18n="hero.badge">End-to-End Encrypted</span></span>
      <h1><span class="grad" data-i18n="hero.line1">Absolute Privacy</span><br><span data-i18n="hero.line2">for Every Conversation</span></h1>
      <p data-i18n="hero.desc">A decentralized P2P communication platform where your messages, calls, and meetings are fully encrypted — zero server access, zero compromise.</p>
      <div class="hero-v2-actions">
        <a href="<?=$basePath?>/app/" class="btn-primary" data-i18n="hero.cta">Get Started <i class="fa-solid fa-arrow-right"></i></a>
        <a href="#how" class="btn-secondary" data-i18n="hero.learn">How It Works</a>
      </div>
      <div class="hero-v2-metrics">
        <div class="hero-v2-metric"><span class="hero-v2-metric-num">100%</span><span class="hero-v2-metric-label" data-i18n="stat.encryption">Encrypted</span></div>
        <div class="hero-v2-metric"><span class="hero-v2-metric-num">Zero</span><span class="hero-v2-metric-label" data-i18n="stat.knowledge">Server Knowledge</span></div>
        <div class="hero-v2-metric"><span class="hero-v2-metric-num">Direct</span><span class="hero-v2-metric-label" data-i18n="stat.direct">P2P Connection</span></div>
      </div>
    </div>
    <div class="hero-v2-sphere">
      <div class="sphere-ring r1"></div>
      <div class="sphere-ring r2"></div>
      <div class="sphere-ring r3"></div>
      <div class="sphere-ring r4"></div>
      <div class="sphere-node" style="top:10%;left:50%"></div>
      <div class="sphere-node" style="top:50%;right:8%"></div>
      <div class="sphere-node" style="bottom:10%;left:50%"></div>
      <div class="sphere-node" style="top:50%;left:8%"></div>
      <div class="sphere-node" style="top:22%;right:22%"></div>
      <div class="sphere-node" style="bottom:22%;left:22%"></div>
      <div class="sphere-center"><i class="fa-solid fa-shield-halved"></i></div>
      <a href="<?=$basePath?>/app/chat/" class="sphere-card sc-top"><i class="fa-solid fa-comments"></i><span data-i18n="feature.chat">Chat</span></a>
      <a href="<?=$basePath?>/app/voice/" class="sphere-card sc-right"><i class="fa-solid fa-phone"></i><span data-i18n="feature.voice">Voice</span></a>
      <a href="<?=$basePath?>/app/video/" class="sphere-card sc-bot"><i class="fa-solid fa-video"></i><span data-i18n="feature.video">Video</span></a>
      <a href="<?=$basePath?>/app/meet/" class="sphere-card sc-left"><i class="fa-solid fa-people-group"></i><span data-i18n="feature.meet">Meet</span></a>
    </div>
  </div>
  <div class="hero-scroll" id="heroScroll">
    <span data-i18n="hero.scroll">Scroll to explore</span>
    <i class="fa-solid fa-chevron-down"></i>
  </div>
</section>

<!-- ========== BRAND BAR ========== -->
<div class="brand-bar">
  <div class="bb-group appear-v2" style="--d:0s"><i class="fa-solid fa-lock"></i> <span data-i18n="brand.aes">AES-256-GCM</span></div>
  <div class="bb-group appear-v2" style="--d:0.12s"><i class="fa-solid fa-bolt"></i> <span data-i18n="brand.webrtc">WebRTC P2P</span></div>
  <div class="bb-group appear-v2" style="--d:0.24s"><i class="fa-solid fa-shield"></i> <span data-i18n="brand.zero">Zero Knowledge</span></div>
  <div class="bb-group appear-v2" style="--d:0.36s"><i class="fa-solid fa-globe"></i> <span data-i18n="brand.open">Open Protocol</span></div>
</div>

<!-- ========== FEATURES ========== -->
<section id="features" class="section-v2 features" style="background:var(--bg2)">
  <div class="section-divider"></div>
  <div class="section-v2-header">
    <span class="section-v2-tag" data-i18n="features.tag">Four Apps, One Platform</span>
    <h2 data-i18n="features.title">Everything You Need to Communicate Securely</h2>
    <p data-i18n="features.sub">Fully decentralized applications with end-to-end encryption built into every message, call, and meeting.</p>
  </div>
  <div class="features-v2">
    <a href="<?=$basePath?>/app/chat/" class="feature-v2 appear-v2" style="--d:0.05s">
      <div class="feature-v2-icon"><i class="fa-solid fa-comments"></i></div>
      <div class="feature-v2-body">
        <h3 data-i18n="features.chat.title">P2P Chat</h3>
        <p data-i18n="features.chat.desc">End-to-end encrypted messaging over WebRTC DataChannels. Send text, files, images, and reactions. The server never sees your messages.</p>
        <span class="feature-v2-link" data-i18n="features.chat.link">Open Chat <i class="fa-solid fa-arrow-right"></i></span>
      </div>
    </a>
    <a href="<?=$basePath?>/app/voice/" class="feature-v2 appear-v2" style="--d:0.1s">
      <div class="feature-v2-icon"><i class="fa-solid fa-phone"></i></div>
      <div class="feature-v2-body">
        <h3 data-i18n="features.voice.title">Voice Calls</h3>
        <p data-i18n="features.voice.desc">High-quality encrypted voice calls. One-to-one calls with SRTP media encryption. Screen sharing included.</p>
        <span class="feature-v2-link" data-i18n="features.voice.link">Start Call <i class="fa-solid fa-arrow-right"></i></span>
      </div>
    </a>
    <a href="<?=$basePath?>/app/video/" class="feature-v2 appear-v2" style="--d:0.15s">
      <div class="feature-v2-icon"><i class="fa-solid fa-video"></i></div>
      <div class="feature-v2-body">
        <h3 data-i18n="features.video.title">Video Calls</h3>
        <p data-i18n="features.video.desc">HD encrypted video calls with camera switching and screen sharing. DTLS-SRTP encryption ensures only you and the recipient see each other.</p>
        <span class="feature-v2-link" data-i18n="features.video.link">Open Camera <i class="fa-solid fa-arrow-right"></i></span>
      </div>
    </a>
    <a href="<?=$basePath?>/app/meet/" class="feature-v2 appear-v2" style="--d:0.2s">
      <div class="feature-v2-icon"><i class="fa-solid fa-people-group"></i></div>
      <div class="feature-v2-body">
        <h3 data-i18n="features.meet.title">Meetings</h3>
        <p data-i18n="features.meet.desc">Multi-participant mesh P2P meetings with video, audio, chat, and screen sharing. No server relays your media — pure peer-to-peer.</p>
        <span class="feature-v2-link" data-i18n="features.meet.link">Create Meeting <i class="fa-solid fa-arrow-right"></i></span>
      </div>
    </a>
  </div>
</section>

<!-- ========== HOW IT WORKS ========== -->
<section id="how" class="section-v2" style="background:var(--bg)">
  <div class="section-divider"></div>
  <div class="section-v2-header">
    <span class="section-v2-tag" data-i18n="how.tag">Simple Flow</span>
    <h2 data-i18n="how.title">How It Works in 4 Steps</h2>
    <p data-i18n="how.sub">From room creation to encrypted communication — every step is designed for privacy, simplicity, and speed.</p>
  </div>
  <div class="steps-v2">
    <div class="step-flow-dot"></div>
    <div class="step-v2 appear-v2" style="--d:0.05s">
      <div class="step-v2-icon"><i class="fa-solid fa-door-open"></i></div>
      <div class="step-v2-num">01</div>
      <h3 data-i18n="security.1.title">Create a Room</h3>
      <p data-i18n="security.1.desc">Share the room code with who you want to talk to. The code is hashed — we never see the original.</p>
    </div>
    <div class="step-v2 appear-v2" style="--d:0.1s">
      <div class="step-v2-icon"><i class="fa-solid fa-key"></i></div>
      <div class="step-v2-num">02</div>
      <h3 data-i18n="security.2.title">Encryption Key Derivation</h3>
      <p data-i18n="security.2.desc">Your browser derives an AES-256-GCM key from the room code using PBKDF2 with 200K SHA-512 iterations. The key never leaves your device.</p>
    </div>
    <div class="step-v2 appear-v2" style="--d:0.15s">
      <div class="step-v2-icon"><i class="fa-solid fa-plug"></i></div>
      <div class="step-v2-num">03</div>
      <h3 data-i18n="security.3.title">Direct P2P Connection</h3>
      <p data-i18n="security.3.desc">WebRTC establishes a direct encrypted channel. Our signaling server steps away — your media never passes through us.</p>
    </div>
    <div class="step-v2 appear-v2" style="--d:0.2s">
      <div class="step-v2-icon"><i class="fa-solid fa-shield-halved"></i></div>
      <div class="step-v2-num">04</div>
      <h3 data-i18n="security.4.title">E2E Encrypted Communication</h3>
      <p data-i18n="security.4.desc">Messages encrypted with AES-256-GCM. Media secured with DTLS & SRTP. Zero data is stored.</p>
    </div>
  </div>
</section>

<!-- ========== WHO IT'S FOR ========== -->
<section id="who" class="section-v2" style="background:var(--bg2)">
  <div class="section-divider"></div>
  <div class="section-v2-header">
    <span class="section-v2-tag" data-i18n="who.tag">Use Cases</span>
    <h2 data-i18n="who.title">Who Sardab Is For</h2>
    <p data-i18n="who.sub">From journalists to developers — Sardab serves everyone who values privacy.</p>
  </div>
  <div class="who-v2">
    <div class="who-v2-card appear-v2" style="--d:0.05s">
      <span class="who-num">01</span>
      <div class="who-icon"><i class="fa-solid fa-newspaper"></i></div>
      <h3 data-i18n="who.1.title">Journalists & Whistleblowers</h3>
      <p data-i18n="who.1.desc">Secure, anonymous communication with zero traces. Publish without fear.</p>
    </div>
    <div class="who-v2-card appear-v2" style="--d:0.1s">
      <span class="who-num">02</span>
      <div class="who-icon"><i class="fa-solid fa-terminal"></i></div>
      <h3 data-i18n="who.2.title">Developers & Engineers</h3>
      <p data-i18n="who.2.desc">Open protocol, inspectable code, P2P architecture. No black boxes.</p>
    </div>
    <div class="who-v2-card appear-v2" style="--d:0.15s">
      <span class="who-num">03</span>
      <div class="who-icon"><i class="fa-solid fa-shield-halved"></i></div>
      <h3 data-i18n="who.3.title">Privacy Advocates</h3>
      <p data-i18n="who.3.desc">Zero-knowledge by design. No accounts, no logs, no data collection.</p>
    </div>
    <div class="who-v2-card appear-v2" style="--d:0.2s">
      <span class="who-num">04</span>
      <div class="who-icon"><i class="fa-solid fa-comments"></i></div>
      <h3 data-i18n="who.4.title">Everyday Conversations</h3>
      <p data-i18n="who.4.desc">Simple, fast, and private. Just share a room code and talk.</p>
    </div>
  </div>
</section>

<!-- ========== SECURITY LAYERS ========== -->
<section id="security" class="section-v2" style="background:var(--bg);position:relative">
  <div class="section-divider"></div>
  <div class="sec-circuit-bg"></div>
  <div class="section-v2-header">
    <span class="section-v2-tag" data-i18n="security.tag">Zero-Knowledge Architecture</span>
    <h2 data-i18n="sec2.title">Every Layer, Secured</h2>
    <p data-i18n="sec2.sub">From room creation to data transmission, every component is designed with privacy as the default.</p>
  </div>
  <div class="security-v2-grid">
    <div class="security-v2-card appear-v2" style="--d:0.05s">
      <span class="sec-num">01</span>
      <div class="sec-icon"><i class="fa-solid fa-hashtag"></i></div>
      <h3 data-i18n="sec2.1.title">Hashed Room Codes</h3>
      <p data-i18n="sec2.1.desc">Room codes are one-way hashed before reaching our server. The original code is never stored or logged.</p>
    </div>
    <div class="security-v2-card appear-v2" style="--d:0.1s">
      <span class="sec-num">02</span>
      <div class="sec-icon"><i class="fa-solid fa-key"></i></div>
      <h3 data-i18n="sec2.2.title">AES-256-GCM Encryption</h3>
      <p data-i18n="sec2.2.desc">All messages are encrypted with industry-standard AES-256-GCM. Keys derived client-side using PBKDF2 with 200,000 iterations.</p>
    </div>
    <div class="security-v2-card appear-v2" style="--d:0.15s">
      <span class="sec-num">03</span>
      <div class="sec-icon"><i class="fa-solid fa-road"></i></div>
      <h3 data-i18n="sec2.3.title">No Data Persistence</h3>
      <p data-i18n="sec2.3.desc">Our servers store nothing — messages, files, and media streams flow directly between peers. No databases. No logs. No history.</p>
    </div>
    <div class="security-v2-card appear-v2" style="--d:0.2s">
      <span class="sec-num">04</span>
      <div class="sec-icon"><i class="fa-solid fa-shield"></i></div>
      <h3 data-i18n="sec2.4.title">DTLS / SRTP Media</h3>
      <p data-i18n="sec2.4.desc">Voice and video streams are secured with DTLS-SRTP encryption. Even the signaling relay cannot decrypt your media.</p>
    </div>
    <div class="security-v2-card appear-v2" style="--d:0.25s">
      <span class="sec-num">05</span>
      <div class="sec-icon"><i class="fa-solid fa-user-secret"></i></div>
      <h3 data-i18n="sec2.5.title">No Account Required</h3>
      <p data-i18n="sec2.5.desc">No email, no phone number, no password. Complete anonymity — just share a room code and you're connected.</p>
    </div>
    <div class="security-v2-card appear-v2" style="--d:0.3s">
      <span class="sec-num">06</span>
      <div class="sec-icon"><i class="fa-solid fa-cubes"></i></div>
      <h3 data-i18n="sec2.6.title">Mesh P2P Topology</h3>
      <p data-i18n="sec2.6.desc">Multi-party calls use a mesh topology where each peer connects directly to every other peer. No central server relays media.</p>
    </div>
  </div>
</section>

<!-- ========== SARBAB DIFFERENCE ========== -->
<section id="why" class="section-v2" style="background:var(--bg2)">
  <div class="section-divider"></div>
  <div class="section-v2-header">
    <span class="section-v2-tag" data-i18n="diff.tag">Why Sardab</span>
    <h2 data-i18n="diff.title">Built Different — On Purpose</h2>
    <p data-i18n="diff.sub">Every design choice starts with one question: does this protect the user?</p>
  </div>
  <div class="diff-v2">
    <div class="diff-v2-card appear-v2" style="--d:0.05s">
      <div class="diff-header">
        <div class="diff-icon"><i class="fa-solid fa-lock"></i></div>
        <h3 data-i18n="diff.1.title">End-to-End Encrypted</h3>
      </div>
      <p data-i18n="diff.1.desc">Messages encrypted with AES-256-GCM. Keys derived client-side. Even we can't read them.</p>
      <div class="diff-vs"><span class="diff-yes"><span data-i18n="diff.sardab">Sardab</span> <i class="fa-solid fa-check"></i></span><span class="diff-no"><span data-i18n="diff.others">Others</span> <i class="fa-solid fa-xmark"></i></span></div>
    </div>
    <div class="diff-v2-card appear-v2" style="--d:0.1s">
      <div class="diff-header">
        <div class="diff-icon"><i class="fa-solid fa-database"></i></div>
        <h3 data-i18n="diff.2.title">Zero Server Knowledge</h3>
      </div>
      <p data-i18n="diff.2.desc">Our servers relay connection signals only. Messages, files, and media never touch disk.</p>
      <div class="diff-vs"><span class="diff-yes"><span data-i18n="diff.sardab">Sardab</span> <i class="fa-solid fa-check"></i></span><span class="diff-no"><span data-i18n="diff.others">Others</span> <i class="fa-solid fa-xmark"></i></span></div>
    </div>
    <div class="diff-v2-card appear-v2" style="--d:0.15s">
      <div class="diff-header">
        <div class="diff-icon"><i class="fa-solid fa-user-secret"></i></div>
        <h3 data-i18n="diff.3.title">No Account Required</h3>
      </div>
      <p data-i18n="diff.3.desc">No email, no phone, no password. Complete anonymity from the first click.</p>
      <div class="diff-vs"><span class="diff-yes"><span data-i18n="diff.sardab">Sardab</span> <i class="fa-solid fa-check"></i></span><span class="diff-no"><span data-i18n="diff.others">Others</span> <i class="fa-solid fa-xmark"></i></span></div>
    </div>
    <div class="diff-v2-card appear-v2" style="--d:0.2s">
      <div class="diff-header">
        <div class="diff-icon"><i class="fa-solid fa-bolt"></i></div>
        <h3 data-i18n="diff.4.title">Direct P2P Connections</h3>
      </div>
      <p data-i18n="diff.4.desc">Media flows directly between peers via WebRTC. No server relays your voice or video.</p>
      <div class="diff-vs"><span class="diff-yes"><span data-i18n="diff.sardab">Sardab</span> <i class="fa-solid fa-check"></i></span><span class="diff-no"><span data-i18n="diff.others">Others</span> <i class="fa-solid fa-xmark"></i></span></div>
    </div>
  </div>
</section>

<!-- ========== TECH ========== -->
<section id="tech" class="section-v2" style="background:var(--bg)">
  <div class="section-divider"></div>
  <div class="section-v2-header">
    <span class="section-v2-tag" data-i18n="tech.tag">Built on Proven Technology</span>
    <h2 data-i18n="tech.title">Enterprise-Grade Encryption Stack</h2>
  </div>
  <div class="tech-v2">
    <div class="tech-v2-item appear-v2" style="--d:0s"><span class="tt">WebRTC</span><span class="td" data-i18n="tech.webrtc">Peer-to-peer real-time communication</span></div>
    <div class="tech-v2-item appear-v2" style="--d:0.03s"><span class="tt">AES-256-GCM</span><span class="td" data-i18n="tech.aes">Message encryption standard</span></div>
    <div class="tech-v2-item appear-v2" style="--d:0.06s"><span class="tt">PBKDF2</span><span class="td" data-i18n="tech.pbkdf2">Key derivation with 200K iterations</span></div>
    <div class="tech-v2-item appear-v2" style="--d:0.09s"><span class="tt">SHA-512</span><span class="td" data-i18n="tech.sha512">Cryptographic hashing</span></div>
    <div class="tech-v2-item appear-v2" style="--d:0.12s"><span class="tt">DTLS/SRTP</span><span class="td" data-i18n="tech.dtls">Media stream encryption</span></div>
    <div class="tech-v2-item appear-v2" style="--d:0.15s"><span class="tt">STUN/TURN</span><span class="td" data-i18n="tech.stun">NAT traversal for reliable connections</span></div>
  </div>
</section>

<!-- ========== MEET THE TEAM ========== -->
<section id="team" class="section-v2" style="background:var(--bg2)">
  <div class="section-divider"></div>
  <div class="section-v2-header">
    <span class="section-v2-tag" data-i18n="team.tag">The Team</span>
    <h2 data-i18n="team.title">Built by People Who Care About Privacy</h2>
    <p data-i18n="team.sub">Three developers, one mission — make private communication accessible to everyone.</p>
  </div>
  <div class="team-v2">
    <div class="team-v2-card appear-v2" style="--d:0.05s">
      <div class="team-avatar"><img src="https://avatars.githubusercontent.com/u/184833715?v=4" alt="Nacer Eddine Bouars"></div>
      <h3 data-i18n="team.1.name">Nacer Eddine Bouars</h3>
      <div class="team-role" data-i18n="team.1.role">Founder, Developer &amp; Designer</div>
      <p data-i18n="team.1.desc">KumoCoders founder. Full-stack developer and designer behind Sardab's architecture, WebRTC signaling, encryption layer, and visual identity.</p>
      <div class="team-links">
        <a href="https://github.com/nacer0s" target="_blank" rel="noopener" title="GitHub @nacer0s"><i class="fa-brands fa-github"></i></a>
        <a href="https://linkedin.com/in/nacer0s" target="_blank" rel="noopener" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
        <a href="https://kumocoders.gt.tc" target="_blank" rel="noopener" title="KumoCoders"><i class="fa-solid fa-globe"></i></a>
      </div>
    </div>
    <div class="team-v2-card appear-v2" style="--d:0.1s">
      <div class="team-avatar"><img src="https://avatars.githubusercontent.com/u/199616564?v=4" alt="Sara Chihab"></div>
      <h3 data-i18n="team.2.name">Sara Chihab</h3>
      <div class="team-role" data-i18n="team.2.role">Co-Founder &amp; Software Engineer</div>
      <p data-i18n="team.2.desc">KumoCoders co-founder. Software engineer specializing in full-stack development, user interfaces, and real-time communication systems.</p>
      <div class="team-links">
        <a href="https://github.com/Sara-chihab" target="_blank" rel="noopener" title="GitHub @Sara-chihab"><i class="fa-brands fa-github"></i></a>
        <a href="https://linkedin.com/in/sara-c-63697b324" target="_blank" rel="noopener" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
        <a href="https://kumocoders.gt.tc" target="_blank" rel="noopener" title="KumoCoders"><i class="fa-solid fa-globe"></i></a>
      </div>
    </div>
    <div class="team-v2-card appear-v2" style="--d:0.15s">
      <div class="team-avatar"><img src="https://avatars.githubusercontent.com/u/219295944?v=4" alt="Douae Manar"></div>
      <h3 data-i18n="team.3.name">Douae Manar</h3>
      <div class="team-role" data-i18n="team.3.role">Software Engineer</div>
      <p data-i18n="team.3.desc">Software engineer contributing to Sardab's development, testing, and quality assurance with a focus on reliable communication features.</p>
      <div class="team-links">
        <a href="https://github.com/Douae-Manar" target="_blank" rel="noopener" title="GitHub @Douae-Manar"><i class="fa-brands fa-github"></i></a>
        <a href="https://linkedin.com/in/douae-manar-il" target="_blank" rel="noopener" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
        <a href="https://kumocoders.gt.tc" target="_blank" rel="noopener" title="KumoCoders"><i class="fa-solid fa-globe"></i></a>
      </div>
    </div>
  </div>
  <div class="team-supporter">
    <h4 data-i18n="team.supported">Supported By</h4>
    <div class="supporter-v2">
      <a href="https://qabilah.com/hackathon/255665101472799432/projects/256995479982706688" target="_blank" rel="noopener" class="supporter-card appear-v2" style="--d:0.2s">
        <span class="supporter-logo">
          <img src="https://qabilah.com/assets/logos/SVGs/outlined-lg.svg" alt="Qabila">
        </span>
        <div class="supporter-info">
          <strong data-i18n="team.qabila.name">Qabilah</strong>
          <span data-i18n="team.qabila.sub">Hackathon Project</span>
        </div>
      </a>
      <a href="https://www.mortakaz.com/projects/6a28e09dc100ecc87a9da743" target="_blank" rel="noopener" class="supporter-card appear-v2" style="--d:0.25s">
        <span class="supporter-logo">
          <svg viewBox="73 116 58 58" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg">
            <rect x="75.34" y="118.62" width="52.92" height="52.92" rx="8" fill="#2196f3"/>
            <g transform="matrix(0.47908,0,0,0.47908,89.55374,107.44011)" fill="#fff">
              <path d="M -1.05846,93.0787 21.1153,43.352 c 1.77703,-3.9878 7.09307,-3.9878 8.87312,0 l 22.17374,49.72677 c 2.00896,4.50288 -2.4517,9.17737 -6.63224,6.94553 l -17.78237,-9.49361 c -1.38247,-0.73766 -3.01192,-0.73766 -4.39138,0 L 5.57379,100.0243 c -4.18054,2.22877 -8.64421,-2.43966 -6.63225,-6.94553"/>
              <path d="m -12.32007,111.8357 a 4.94005,4.94005 0 0 1 4.94005,-4.94011 H 58.48733 a 4.94005,4.94005 0 0 1 0,9.8801 H -7.38002 a 4.94005,4.94005 0 0 1 -4.94005,-4.93999"/>
            </g>
          </svg>
        </span>
        <div class="supporter-info">
          <strong data-i18n="team.mortakaz.name">Mortakaz</strong>
          <span data-i18n="team.mortakaz.sub">Featured Project</span>
        </div>
      </a>
    </div>
  </div>
</section>

<!-- ========== ROADMAP ========== -->
<section id="roadmap" class="section-v2" style="background:var(--bg)">
  <div class="section-divider"></div>
  <div class="section-v2-header">
    <span class="section-v2-tag" data-i18n="road.tag">Coming Soon</span>
    <h2 data-i18n="road.title">What's Next for Sardab</h2>
    <p data-i18n="road.sub">We're just getting started. Here's what we're building next.</p>
  </div>
  <div class="roadmap-v2">
    <div class="roadmap-v2-item appear-v2" style="--d:0.05s">
      <div class="rm-dot"><i class="fa-solid fa-check"></i></div>
      <span class="rm-tag" data-i18n="road.now">Now</span>
      <h4 data-i18n="road.1.title">Core Platform Stabilization</h4>
      <p data-i18n="road.1.desc">WebRTC mesh signaling, E2EE messaging, voice/video calls, screen sharing, file transfer, multi-party meetings, and the full landing page experience.</p>
      <ul>
        <li data-i18n="road.1.b1">P2P Mesh Topology</li>
        <li data-i18n="road.1.b2">AES-256-GCM Encryption</li>
        <li data-i18n="road.1.b3">Screen Sharing</li>
        <li data-i18n="road.1.b4">Multi-party Meetings</li>
      </ul>
    </div>
    <div class="roadmap-v2-item appear-v2" style="--d:0.1s">
      <div class="rm-dot"><i class="fa-solid fa-arrow-up"></i></div>
      <span class="rm-tag" data-i18n="road.next">Next</span>
      <h4 data-i18n="road.2.title">Mobile &amp; Cross-Platform</h4>
      <p data-i18n="road.2.desc">Native mobile applications, progressive web app enhancements, push notifications, dark mode system integration, and keyboard shortcut refinement.</p>
      <ul>
        <li data-i18n="road.2.b1">PWA Installation</li>
        <li data-i18n="road.2.b2">Push Notifications</li>
        <li data-i18n="road.2.b3">Offline Mode</li>
        <li data-i18n="road.2.b4">Desktop App (Electron)</li>
      </ul>
    </div>
    <div class="roadmap-v2-item appear-v2" style="--d:0.15s">
      <div class="rm-dot"><i class="fa-solid fa-star"></i></div>
      <span class="rm-tag" data-i18n="road.future">Future</span>
      <h4 data-i18n="road.3.title">Advanced Features &amp; Ecosystem</h4>
      <p data-i18n="road.3.desc">End-to-end encrypted file sharing with preview, voice messages, ephemeral messages, custom room backgrounds, bot API, federation protocol, and decentralized identity.</p>
      <ul>
        <li data-i18n="road.3.b1">Ephemeral Messages</li>
        <li data-i18n="road.3.b2">Voice Messages</li>
        <li data-i18n="road.3.b3">Bot API</li>
        <li data-i18n="road.3.b4">Federation Protocol</li>
      </ul>
    </div>
  </div>
</section>

<!-- ========== FAQ ========== -->
<section id="faq" class="section-v2 faq" style="background:var(--bg2)">
  <div class="section-divider"></div>
  <div class="section-v2-header">
    <span class="section-v2-tag" data-i18n="faq.tag">Common Questions</span>
    <h2 data-i18n="faq.title">Frequently Asked Questions</h2>
  </div>
  <div class="faq-v2" id="faqList">
    <div class="faq-item appear-v2" style="--d:0.05s">
      <button class="faq-question"><span data-i18n="faq.q1">Is Sardab really private?</span><i class="fa-solid fa-plus"></i></button>
      <div class="faq-answer"><p data-i18n="faq.a1">Yes. Sardab uses zero-knowledge architecture. We never store messages, calls, or files. The encryption key stays in your browser. Even if someone seized our server, they would find nothing but connection signals that reveal no content.</p></div>
    </div>
    <div class="faq-item appear-v2" style="--d:0.1s">
      <button class="faq-question"><span data-i18n="faq.q2">Do I need to create an account?</span><i class="fa-solid fa-plus"></i></button>
      <div class="faq-answer"><p data-i18n="faq.a2">No. Sardab requires no account, email, or phone number. Just share a room code with your recipient and you are connected. Complete anonymity is built in.</p></div>
    </div>
    <div class="faq-item appear-v2" style="--d:0.15s">
      <button class="faq-question"><span data-i18n="faq.q3">What happens if my connection drops?</span><i class="fa-solid fa-plus"></i></button>
      <div class="faq-answer"><p data-i18n="faq.a3">WebRTC handles network changes gracefully. If you lose connection, simply re-enter the same room code to resume. Since nothing is stored on our server, there is no message history — only what is on your device.</p></div>
    </div>
    <div class="faq-item appear-v2" style="--d:0.2s">
      <button class="faq-question"><span data-i18n="faq.q4">Can I use Sardab on mobile?</span><i class="fa-solid fa-plus"></i></button>
      <div class="faq-answer"><p data-i18n="faq.a4">Yes. Sardab is a progressive web app (PWA). Open it in your mobile browser and add it to your home screen for an app-like experience. Works on iOS and Android.</p></div>
    </div>
    <div class="faq-item appear-v2" style="--d:0.25s">
      <button class="faq-question"><span data-i18n="faq.q5">Is Sardab open source?</span><i class="fa-solid fa-plus"></i></button>
      <div class="faq-answer"><p data-i18n="faq.a5">Sardab is built by KumoCoders. The source is available for review and contribution. Contact us for access to the repository.</p></div>
    </div>
    <div class="faq-item appear-v2" style="--d:0.3s">
      <button class="faq-question"><span data-i18n="faq.q6">How does Sardab compare to Signal or WhatsApp?</span><i class="fa-solid fa-plus"></i></button>
      <div class="faq-answer"><p data-i18n="faq.a6">Unlike Signal and WhatsApp, Sardab requires no phone number, no account, and no personal identifier. Our servers store zero data — no contacts, no messages, no metadata. While Signal uses centralized servers for message delivery, Sardab uses direct peer-to-peer WebRTC connections. The trade-off is that both parties must be online simultaneously, and there is no message history when you leave the room. Sardab is designed for ephemeral, private sessions rather than persistent message archives.</p></div>
    </div>
    <div class="faq-item appear-v2" style="--d:0.35s">
      <button class="faq-question"><span data-i18n="faq.q7">Can I use Sardab for business or team communication?</span><i class="fa-solid fa-plus"></i></button>
      <div class="faq-answer"><p data-i18n="faq.a7">Absolutely. Sardab is free and requires no licensing, no server setup, and no user management. Teams can use it for encrypted meetings, file sharing, and instant messaging without any of their data passing through corporate servers. There are no user limits, no storage quotas, and no data retention policies to worry about. For organizations that handle sensitive information, Sardab provides a zero-trust communication layer where even the platform provider cannot access your communications.</p></div>
    </div>
    <div class="faq-item appear-v2" style="--d:0.4s">
      <button class="faq-question"><span data-i18n="faq.q8">What encryption algorithms does Sardab use?</span><i class="fa-solid fa-plus"></i></button>
      <div class="faq-answer"><p data-i18n="faq.a8">Sardab uses multiple encryption layers for different data types. Text messages and files are encrypted with AES-256-GCM (Galois/Counter Mode), the industry standard for symmetric encryption. The encryption key is derived from the room code using PBKDF2 with 200,000 iterations of SHA-512 — this key derivation process makes brute-force attacks computationally infeasible. Audio and video streams are secured with DTLS (Datagram Transport Layer Security) and SRTP (Secure Real-time Transport Protocol), which are the standard WebRTC encryption protocols. All encryption happens client-side — your browser does the work, not our servers.</p></div>
    </div>
    <div class="faq-item appear-v2" style="--d:0.45s">
      <button class="faq-question"><span data-i18n="faq.q9">Is there a file size limit for transfers?</span><i class="fa-solid fa-plus"></i></button>
      <div class="faq-answer"><p data-i18n="faq.a9">Sardab has no server-imposed file size limit because files are transferred directly between peers via WebRTC DataChannels — they never pass through our server. The practical limit depends on your browser's memory and the WebRTC implementation. Most modern browsers handle files up to 1-2 GB reliably, though very large transfers may be affected by network stability and available RAM. Files are encrypted before transfer and decrypted only on the recipient's device. We recommend compressing large files before sending for optimal performance.</p></div>
    </div>
    <div class="faq-item appear-v2" style="--d:0.5s">
      <button class="faq-question"><span data-i18n="faq.q10">How do room codes work and are they secure?</span><i class="fa-solid fa-plus"></i></button>
      <div class="faq-answer"><p data-i18n="faq.a10">Room codes are randomly generated strings that serve as both the meeting identifier and the basis for the encryption key. When you create a room, the code is generated in your browser and then one-way hashed with SHA-512 before being sent to our signaling server — the original code is never visible to us. The hash is used only to route connection signals. The original code stays in your browser and is used to derive the AES-256-GCM encryption key via PBKDF2. This means that even if someone compromised our server, they would only see hashed room codes that cannot be reversed, and they would still be unable to decrypt any communications without the original code.</p></div>
    </div>
  </div>
</section>

<!-- ========== CTA ========== -->
<section class="section-v2" style="background:var(--bg)">
  <div class="section-divider"></div>
  <div class="cta-v2-card">
    <h2 data-i18n="cta.title">Start Communicating Privately</h2>
    <p data-i18n="cta.desc">No account. No setup. No tracking. Just pure encrypted communication.</p>
    <div class="cta-buttons">
      <a href="<?=$basePath?>/app/" class="btn-primary btn-lg" data-i18n="cta.btn">Launch Sardab <i class="fa-solid fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<?php include __DIR__.'/includes/footer.php'; ?>
</div>

<script src="<?=$basePath?>/assets/js/i18n.js?v=1"></script>
<script>
i18n.setTranslations({
  en: {
    'page.title': 'Sardab — Encrypted P2P Communication',
    'nav.features': 'Features',
    'nav.how': 'How It Works',
    'nav.why': 'Why Sardab',
    'nav.security': 'Security',
    'nav.team': 'Team',
    'nav.roadmap': 'Roadmap',
    'nav.faq': 'FAQ',
    'nav.app': 'Launch App',
    'nav.apps': 'Apps',
    'hero.badge': 'End-to-End Encrypted',
    'hero.line1': 'Absolute Privacy',
    'hero.line2': 'for Every Conversation',
    'hero.desc': 'A decentralized P2P communication platform where your messages, calls, and meetings are fully encrypted — zero server access, zero compromise.',
    'hero.cta': 'Get Started',
    'hero.learn': 'How It Works',
    'hero.scroll': 'Scroll to explore',
    'stat.encryption': 'Encrypted',
    'stat.knowledge': 'Server Knowledge',
    'stat.direct': 'Direct Connection',
    'feature.chat': 'Chat',
    'feature.voice': 'Voice',
    'feature.video': 'Video',
    'feature.meet': 'Meet',
    'features.tag': 'Four Apps, One Platform',
    'features.title': 'Everything You Need to Communicate Securely',
    'features.sub': 'Fully decentralized applications with end-to-end encryption built into every message, call, and meeting.',
    'features.chat.title': 'P2P Chat',
    'features.chat.desc': 'End-to-end encrypted messaging over WebRTC DataChannels. Send text, files, images, and reactions. The server never sees your messages.',
    'features.chat.link': 'Open Chat',
    'features.voice.title': 'Voice Calls',
    'features.voice.desc': 'High-quality encrypted voice calls. One-to-one calls with SRTP media encryption. Screen sharing included.',
    'features.voice.link': 'Start Call',
    'features.video.title': 'Video Calls',
    'features.video.desc': 'HD encrypted video calls with camera switching and screen sharing. DTLS-SRTP encryption ensures only you and the recipient see each other.',
    'features.video.link': 'Open Camera',
    'features.meet.title': 'Meetings',
    'features.meet.desc': 'Multi-participant mesh P2P meetings with video, audio, chat, and screen sharing. No server relays your media — pure peer-to-peer.',
    'features.meet.link': 'Create Meeting',
    'security.tag': 'Zero-Knowledge Architecture',
    'security.title': 'Your Privacy Is the Foundation',
    'security.sub': 'We designed Sardab so that even we cannot access your data. Every layer is built with privacy first.',
    'security.1.title': 'Create a Room',
    'security.1.desc': 'Share the room code with who you want to talk to. The code is hashed — we never see the original.',
    'security.2.title': 'Encryption Key Derivation',
    'security.2.desc': 'Your browser derives an AES-256-GCM key from the room code using PBKDF2 with 200K SHA-512 iterations. The key never leaves your device.',
    'security.3.title': 'Direct P2P Connection',
    'security.3.desc': 'WebRTC establishes a direct encrypted channel. Our signaling server steps away — your media never passes through us.',
    'security.4.title': 'E2E Encrypted Communication',
    'security.4.desc': 'Messages encrypted with AES-256-GCM. Media secured with DTLS & SRTP. Zero data is stored.',
    'tech.tag': 'Built on Proven Technology',
    'tech.title': 'Enterprise-Grade Encryption Stack',
    'tech.webrtc': 'Peer-to-peer real-time communication',
    'tech.aes': 'Message encryption standard',
    'tech.pbkdf2': 'Key derivation with 200K iterations',
    'tech.sha512': 'Cryptographic hashing',
    'tech.dtls': 'Media stream encryption',
    'tech.stun': 'NAT traversal for reliable connections',
    'faq.tag': 'Common Questions',
    'faq.title': 'Frequently Asked Questions',
    'faq.q1': 'Is Sardab really private?',
    'faq.a1': 'Yes. Sardab uses zero-knowledge architecture. We never store messages, calls, or files. The encryption key stays in your browser. Even if someone seized our server, they would find nothing but connection signals that reveal no content.',
    'faq.q2': 'Do I need to create an account?',
    'faq.a2': 'No. Sardab requires no account, email, or phone number. Just share a room code with your recipient and you are connected. Complete anonymity is built in.',
    'faq.q3': 'What happens if my connection drops?',
    'faq.a3': 'WebRTC handles network changes gracefully. If you lose connection, simply re-enter the same room code to resume. Since nothing is stored on our server, there is no message history — only what is on your device.',
    'faq.q4': 'Can I use Sardab on mobile?',
    'faq.a4': 'Yes. Sardab is a progressive web app (PWA). Open it in your mobile browser and add it to your home screen for an app-like experience. Works on iOS and Android.',
    'faq.q5': 'Is Sardab open source?',
    'faq.a5': 'Sardab is built by KumoCoders. The source is available for review and contribution. Contact us for access to the repository.',
    'faq.q6': 'How does Sardab compare to Signal or WhatsApp?',
    'faq.a6': 'Unlike Signal and WhatsApp, Sardab requires no phone number, no account, and no personal identifier. Our servers store zero data — no contacts, no messages, no metadata. While Signal uses centralized servers for message delivery, Sardab uses direct peer-to-peer WebRTC connections. The trade-off is that both parties must be online simultaneously, and there is no message history when you leave the room. Sardab is designed for ephemeral, private sessions rather than persistent message archives.',
    'faq.q7': 'Can I use Sardab for business or team communication?',
    'faq.a7': 'Absolutely. Sardab is free and requires no licensing, no server setup, and no user management. Teams can use it for encrypted meetings, file sharing, and instant messaging without any of their data passing through corporate servers. There are no user limits, no storage quotas, and no data retention policies to worry about. For organizations that handle sensitive information, Sardab provides a zero-trust communication layer where even the platform provider cannot access your communications.',
    'faq.q8': 'What encryption algorithms does Sardab use?',
    'faq.a8': 'Sardab uses multiple encryption layers for different data types. Text messages and files are encrypted with AES-256-GCM (Galois/Counter Mode), the industry standard for symmetric encryption. The encryption key is derived from the room code using PBKDF2 with 200,000 iterations of SHA-512 — this key derivation process makes brute-force attacks computationally infeasible. Audio and video streams are secured with DTLS (Datagram Transport Layer Security) and SRTP (Secure Real-time Transport Protocol), which are the standard WebRTC encryption protocols. All encryption happens client-side — your browser does the work, not our servers.',
    'faq.q9': 'Is there a file size limit for transfers?',
    'faq.a9': 'Sardab has no server-imposed file size limit because files are transferred directly between peers via WebRTC DataChannels — they never pass through our server. The practical limit depends on your browser\'s memory and the WebRTC implementation. Most modern browsers handle files up to 1-2 GB reliably, though very large transfers may be affected by network stability and available RAM. Files are encrypted before transfer and decrypted only on the recipient\'s device. We recommend compressing large files before sending for optimal performance.',
    'faq.q10': 'How do room codes work and are they secure?',
    'faq.a10': 'Room codes are randomly generated strings that serve as both the meeting identifier and the basis for the encryption key. When you create a room, the code is generated in your browser and then one-way hashed with SHA-512 before being sent to our signaling server — the original code is never visible to us. The hash is used only to route connection signals. The original code stays in your browser and is used to derive the AES-256-GCM encryption key via PBKDF2. This means that even if someone compromised our server, they would only see hashed room codes that cannot be reversed, and they would still be unable to decrypt any communications without the original code.',
    'who.tag': 'Use Cases',
    'who.title': 'Who Sardab Is For',
    'who.sub': 'From journalists to developers — Sardab serves everyone who values privacy.',
    'who.1.title': 'Journalists & Whistleblowers',
    'who.1.desc': 'Secure, anonymous communication with zero traces. Publish without fear.',
    'who.2.title': 'Developers & Engineers',
    'who.2.desc': 'Open protocol, inspectable code, P2P architecture. No black boxes.',
    'who.3.title': 'Privacy Advocates',
    'who.3.desc': 'Zero-knowledge by design. No accounts, no logs, no data collection.',
    'who.4.title': 'Everyday Conversations',
    'who.4.desc': 'Simple, fast, and private. Just share a room code and talk.',
    'diff.tag': 'Why Sardab',
    'diff.title': 'Built Different — On Purpose',
    'diff.sub': 'Every design choice starts with one question: does this protect the user?',
    'diff.1.title': 'End-to-End Encrypted',
    'diff.1.desc': 'Messages encrypted with AES-256-GCM. Keys derived client-side. Even we can\'t read them.',
    'diff.2.title': 'Zero Server Knowledge',
    'diff.2.desc': 'Our servers relay connection signals only. Messages, files, and media never touch disk.',
    'diff.3.title': 'No Account Required',
    'diff.3.desc': 'No email, no phone, no password. Complete anonymity from the first click.',
    'diff.4.title': 'Direct P2P Connections',
    'diff.4.desc': 'Media flows directly between peers via WebRTC. No server relays your voice or video.',
    'diff.sardab': 'Sardab',
    'diff.others': 'Others',
    'footer.dev1': 'Nacer Eddine Bouars',
    'footer.dev2': 'Sara Chihab',
    'footer.dev3': 'Douae Manar',
    'footer.legal': 'Terms &amp; Privacy',
    'legal.title': 'Terms &amp; Privacy',
    'legal.terms': 'Terms of Service',
    'legal.privacy': 'Privacy Policy',
    'legal.close': 'Close',
    'team.tag': 'The Team',
    'team.title': 'Built by People Who Care About Privacy',
    'team.sub': 'Three developers, one mission — make private communication accessible to everyone.',
    'team.1.name': 'Nacer Eddine Bouars',
    'team.1.role': 'Founder, Developer &amp; Designer',
    'team.1.desc': 'KumoCoders founder. Full-stack developer and designer behind Sardab\'s architecture, WebRTC signaling, encryption layer, and visual identity.',
    'team.2.name': 'Sara Chihab',
    'team.2.role': 'Co-Founder &amp; Software Engineer',
    'team.2.desc': 'KumoCoders co-founder. Software engineer specializing in full-stack development, user interfaces, and real-time communication systems.',
    'team.3.name': 'Douae Manar',
    'team.3.role': 'Software Engineer',
    'team.3.desc': 'Software engineer contributing to Sardab\'s development, testing, and quality assurance with a focus on reliable communication features.',
    'team.supported': 'Support Us',
    'team.qabila.name': 'Qabilah',
    'team.qabila.sub': 'Thanks for the hackathon',
    'team.mortakaz.name': 'Mortakaz',
    'team.mortakaz.sub': 'Thanks for featuring our project',
    'road.tag': 'Coming Soon',
    'road.title': 'What\'s Next for Sardab',
    'road.sub': 'We\'re just getting started. Here\'s what we\'re building next.',
    'road.now': 'Now',
    'road.next': 'Next',
    'road.future': 'Future',
    'road.1.title': 'Core Platform Stabilization',
    'road.1.desc': 'WebRTC mesh signaling, E2EE messaging, voice/video calls, screen sharing, file transfer, multi-party meetings, and the full landing page experience.',
    'road.2.title': 'Mobile &amp; Cross-Platform',
    'road.2.desc': 'Native mobile applications, progressive web app enhancements, push notifications, dark mode system integration, and keyboard shortcut refinement.',
    'road.3.title': 'Advanced Features &amp; Ecosystem',
    'road.3.desc': 'End-to-end encrypted file sharing with preview, voice messages, ephemeral messages, custom room backgrounds, bot API, federation protocol, and decentralized identity.',
    'road.1.b1': 'P2P Mesh Topology',
    'road.1.b2': 'AES-256-GCM Encryption',
    'road.1.b3': 'Screen Sharing',
    'road.1.b4': 'Multi-party Meetings',
    'road.2.b1': 'PWA Installation',
    'road.2.b2': 'Push Notifications',
    'road.2.b3': 'Offline Mode',
    'road.2.b4': 'Desktop App (Electron)',
    'road.3.b1': 'Ephemeral Messages',
    'road.3.b2': 'Voice Messages',
    'road.3.b3': 'Bot API',
    'road.3.b4': 'Federation Protocol',
    'terms.1.title': '1. Acceptance of Terms',
    'terms.1.body': 'By accessing or using Sardab ("the Service"), you agree to be bound by these Terms of Service. If you do not agree, do not use the Service. Continued use constitutes acceptance of any future modifications.',
    'terms.2.title': '2. Service Description',
    'terms.2.body': 'Sardab is a peer-to-peer encrypted communication platform that facilitates direct connections between users via WebRTC. The Service offers chat, voice, video, and meeting capabilities. All media and messages are encrypted end-to-end and are not stored on our servers. Sardab does not provide, operate, or maintain any infrastructure for relaying or recording user communications beyond transient signaling data required to establish peer connections.',
    'terms.3.title': '3. User Eligibility &amp; Registration',
    'terms.3.body': 'The Service is available to anyone without registration. No account, email address, phone number, or personal identifier is required. Users must be at least 13 years of age. By using the Service, you represent that you meet this requirement. Users under 13 may not use the Service under any circumstances.',
    'terms.4.title': '4. User Conduct &amp; Prohibited Activities',
    'terms.4.body': 'You agree not to use Sardab for any unlawful purpose or in violation of any applicable laws. Prohibited activities include but are not limited to: transmitting malware, viruses, or harmful code; engaging in harassment, threats, or abuse; distributing illegal content; attempting to reverse-engineer, crack, or compromise the encryption; interfering with other users\' connections; using automated bots or scripts to interact with the Service; conducting network attacks including but not limited to DDoS, flooding, or signaling abuse. Sardab reserves the right to terminate access for users engaging in prohibited activities.',
    'terms.5.title': '5. Intellectual Property',
    'terms.5.body': 'The Sardab name, logo, brand, and visual design are the intellectual property of KumoCoders and its developers. The underlying source code is available for review and contribution under a shared-source model. Users retain all rights to their own communications. Sardab does not claim ownership of any content transmitted through the Service. You may not reproduce, distribute, or create derivative works of the Sardab brand assets without explicit written permission.',
    'terms.6.title': '6. Privacy &amp; Data',
    'terms.6.body': 'Sardab is designed as a zero-knowledge system. We do not collect, store, or process personal data. Communications are encrypted client-side and transmitted directly between peers. Signaling data (limited to WebRTC handshake information) is relayed ephemerally and is not persisted. No message content, media streams, file contents, metadata about communications, IP addresses beyond connection duration, or any user-identifying information is stored. For complete details, refer to our Privacy Policy.',
    'terms.7.title': '7. Encryption &amp; Security',
    'terms.7.body': 'Messages are encrypted using AES-256-GCM with keys derived client-side via PBKDF2 with 200,000 iterations of SHA-512. Media streams are secured with DTLS-SRTP encryption. The encryption key is derived from the room code and never transmitted to any server. Sardab makes no warranty that the encryption implementation is impervious to all forms of attack. Users acknowledge that perfect security is unattainable and assume the inherent risks of digital communication.',
    'terms.8.title': '8. Disclaimer of Warranties',
    'terms.8.body': 'THE SERVICE IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, NON-INFRINGEMENT, OR AVAILABILITY. Sardab does not guarantee that the Service will be uninterrupted, secure, error-free, or free of harmful components. The entire risk arising out of use or performance of the Service remains with the user. Sardab is provided without any warranty regarding the accuracy, reliability, or completeness of the Service.',
    'terms.9.title': '9. Limitation of Liability',
    'terms.9.body': 'TO THE MAXIMUM EXTENT PERMITTED BY LAW, KumoCoders, its developers (Nacer Eddine Bouars, Sara Chihab, and Douae Manar), and contributors shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including but not limited to loss of data, loss of profits, service interruption, computer damage, or any other damages arising from the use or inability to use the Service, even if advised of the possibility of such damages. In no event shall total liability exceed the amount paid by the user for the Service, which is zero.',
    'terms.10.title': '10. Indemnification',
    'terms.10.body': 'You agree to indemnify, defend, and hold harmless KumoCoders, its developers, affiliates, and contributors from any claims, liabilities, damages, losses, or expenses, including reasonable legal fees, arising out of or in any way connected with your use of the Service, your violation of these Terms, or your violation of any rights of another user or third party.',
    'terms.11.title': '11. Termination',
    'terms.11.body': 'Sardab reserves the right to modify, suspend, or discontinue the Service, or any part thereof, at any time without notice. We may terminate or restrict your access to the Service for violation of these Terms, without liability. Upon termination, all provisions of these Terms that by their nature should survive termination shall survive, including but not limited to limitation of liability, disclaimer of warranties, and indemnification.',
    'terms.12.title': '12. Modifications to Terms',
    'terms.12.body': 'These Terms may be updated at any time. Changes will be posted on this page. Your continued use of the Service after any modifications constitutes acceptance of the revised Terms. It is your responsibility to review these Terms periodically. Material changes will be communicated via the Service.',
    'terms.13.title': '13. Governing Law &amp; Jurisdiction',
    'terms.13.body': 'These Terms shall be governed by and construed in accordance with applicable international laws. Any disputes arising from these Terms or the Service shall be resolved through binding arbitration in accordance with the rules of the relevant jurisdiction. Users agree that any legal proceedings shall be conducted in English or Arabic at the discretion of the Service.',
    'terms.14.title': '14. Contact Information',
    'terms.14.body': 'For questions, concerns, or legal inquiries regarding these Terms, please contact the development team: KumoCoders, represented by Nacer Eddine Bouars, Sara Chihab, and Douae Manar. The project repository and contact channels are available through the official KumoCoders website.',
    'privacy.1.title': '1. Introduction &amp; Scope',
    'privacy.1.body': 'This Privacy Policy explains how Sardab handles information when you use the Service. Sardab is designed from the ground up as a zero-knowledge system. Our fundamental principle is that we should not — and technically cannot — access your communications. This policy describes what limited data we handle, how it is protected, and what we do not collect. By using Sardab, you acknowledge and agree to the practices described in this policy.',
    'privacy.2.title': '2. Information We Do NOT Collect',
    'privacy.2.body': 'Sardab is intentionally designed to collect no personal information. We do NOT collect: names, email addresses, phone numbers, physical addresses, government IDs, payment information, biometric data, device identifiers, contact lists, location data, browsing history, behavioral data, or any other personally identifiable information. We do NOT use tracking pixels, analytics scripts, fingerprinting techniques, or any form of user surveillance. No account creation means no profile data is ever stored.',
    'privacy.3.title': '3. Information We Handle (and How)',
    'privacy.3.body': 'The only data our servers process is ephemeral WebRTC signaling information necessary to establish peer-to-peer connections. This includes Session Description Protocol (SDP) offers and answers, ICE candidates for NAT traversal, and room code hashes. This data is transmitted in memory only and is not logged, stored, or persisted to disk. Room codes are one-way hashed with SHA-512 before reaching our server — the original code is never visible to us. Signaling data is discarded immediately after the peer connection is established or when the room session ends.',
    'privacy.4.title': '4. Encryption Architecture',
    'privacy.4.body': 'All communications on Sardab are encrypted by default at every layer. Text messages, files, and chat data are encrypted with AES-256-GCM symmetric encryption. The encryption key is derived client-side from the room code using PBKDF2 with 200,000 iterations of SHA-512 — this key never leaves your browser. Audio and video streams are encrypted with DTLS-SRTP, the standard WebRTC media encryption protocol. Even if a third party intercepted the signaling relay, they would only see encrypted data they cannot decrypt. Sardab has no ability to decrypt, monitor, or access the content of any user communication.',
    'privacy.5.title': '5. Data Retention &amp; Deletion',
    'privacy.5.body': 'Sardab does not retain any user data. Signaling information is ephemeral and exists only in server memory for the duration of the signaling process. There are no databases, log files, backups, or archives containing user communications. Room codes are hashed and used only for routing signaling messages; the hash is not stored after the room is destroyed. If you close your browser or leave a room, no record of your session exists anywhere. There is nothing to delete because there is nothing stored.',
    'privacy.6.title': '6. Third-Party Services',
    'privacy.6.body': 'Sardab does not integrate with any third-party analytics, advertising, tracking, or social media services. The Service does not load external scripts, embed third-party content, or send data to external servers beyond the signaling relay. STUN/TURN servers used for NAT traversal receive only the minimum network information required to establish connections (IP addresses and ports) and do not log or persist this information. Users are encouraged to review the privacy practices of any third-party browser extensions or network infrastructure they may use.',
    'privacy.7.title': '7. Network &amp; Connection Data',
    'privacy.7.body': 'When you use Sardab, your IP address is visible to the signaling server for the duration of the signaling handshake and to any peers you connect to directly (necessary for WebRTC peer-to-peer communication). IP addresses are not logged, stored, or associated with any user identifier. If you use a TURN relay (required for some restrictive network environments), the TURN server will see your IP address and port during the connection. We recommend using a VPN for an additional layer of network privacy if desired.',
    'privacy.8.title': '8. Cookies &amp; Local Storage',
    'privacy.8.body': 'Sardab does not use cookies for tracking, analytics, or advertising. The only local storage used is your language preference (sardab-lang), which is stored in the browser\'s localStorage and never transmitted to any server. This is used solely to remember your interface language choice between sessions. You can clear this at any time through your browser settings. No other data is stored on your device by Sardab.',
    'privacy.9.title': '9. Children\'s Privacy',
    'privacy.9.body': 'Sardab is not directed at children under the age of 13. We do not knowingly collect any information from children. If we become aware that a user under 13 is using the Service, we will terminate their access. Parents and guardians are advised to monitor their children\'s online activities. Users under 13 are prohibited from using Sardab under any circumstances. We encourage parents to educate their children about safe online communication practices.',
    'privacy.10.title': '10. Your Rights &amp; Choices',
    'privacy.10.body': 'Because Sardab collects no personal data, traditional data subject rights (access, rectification, erasure, portability) are inherently fulfilled — there is nothing to access, correct, or delete. You have the right to use the Service anonymously without providing any personal information. You have the right to stop using the Service at any time, which immediately terminates all data processing. You have the right to verify our zero-knowledge claims by inspecting the source code. Under applicable privacy laws including but not limited to GDPR and CCPA, no data processing of personal information occurs.',
    'privacy.11.title': '11. Security Measures',
    'privacy.11.body': 'Sardab employs industry-standard encryption protocols to protect communications. The signaling server runs with minimal privileges and no persistent storage. All server communications use HTTPS/WSS (WebSocket Secure). The source code is available for independent security audit and review. Despite these measures, no system is perfectly secure. Users are responsible for protecting their own devices, maintaining secure network connections, and keeping their room codes private. Sardab recommends using strong, unique room codes for each communication session.',
    'privacy.12.title': '12. International Data Transfers',
    'privacy.12.body': 'Sardab\'s signaling server may be located in any jurisdiction. Since no personal data is collected or stored, international data transfer regulations have no practical effect — there is no user data to transfer across borders. Peer-to-peer connections occur directly between users\' devices regardless of geographic location. Sardab does not route communications through specific jurisdictions or apply geographic restrictions.',
    'privacy.13.title': '13. Changes to This Policy',
    'privacy.13.body': 'This Privacy Policy may be updated from time to time. Changes will be posted on this page with an updated effective date. Material changes will be communicated through the Service. Your continued use of Sardab after policy changes constitutes acceptance of the updated terms. We encourage users to review this policy periodically. The current version was last updated June 2026.',
    'privacy.14.title': '14. Contact &amp; Data Controller',
    'privacy.14.body': 'Sardab is developed and maintained by KumoCoders, a collective of developers focused on privacy-preserving technology. The development team consists of Nacer Eddine Bouars, Sara Chihab, and Douae Manar. For privacy-related inquiries or security disclosures, please contact the team through the official KumoCoders repository or website. As Sardab collects no personal data, there is no data controller or data protection officer — there is no data to control or protect on our end.',
    'cta.title': 'Start Communicating Privately',
    'cta.desc': 'No account. No setup. No tracking. Just pure encrypted communication.',
    'cta.btn': 'Launch Sardab',
    'footer.tag': 'Full Encryption',
    'footer.zero': 'Zero Knowledge',
    'footer.direct': 'Direct Connection',
    'footer.rights': 'All rights reserved.',
    'footer.by': 'Built by',
    'brand.name': 'Sardab',
    'brand.aes': 'AES-256-GCM',
    'brand.webrtc': 'WebRTC P2P',
    'brand.zero': 'Zero Knowledge',
    'brand.open': 'Open Protocol',
    'how.tag': 'Simple Flow',
    'how.title': 'How It Works in 4 Steps',
    'how.sub': 'From room creation to encrypted communication — every step is designed for privacy, simplicity, and speed.',
    'sec2.title': 'Every Layer, Secured',
    'sec2.sub': 'From room creation to data transmission, every component is designed with privacy as the default.',
    'sec2.1.title': 'Hashed Room Codes',
    'sec2.1.desc': 'Room codes are one-way hashed before reaching our server. The original code is never stored or logged.',
    'sec2.2.title': 'AES-256-GCM Encryption',
    'sec2.2.desc': 'All messages are encrypted with industry-standard AES-256-GCM. Keys derived client-side using PBKDF2 with 200,000 iterations.',
    'sec2.3.title': 'No Data Persistence',
    'sec2.3.desc': 'Our servers store nothing — messages, files, and media streams flow directly between peers. No databases. No logs. No history.',
    'sec2.4.title': 'DTLS / SRTP Media',
    'sec2.4.desc': 'Voice and video streams are secured with DTLS-SRTP encryption. Even the signaling relay cannot decrypt your media.',
    'sec2.5.title': 'No Account Required',
    'sec2.5.desc': 'No email, no phone number, no password. Complete anonymity — just share a room code and you\'re connected.',
    'sec2.6.title': 'Mesh P2P Topology',
    'sec2.6.desc': 'Multi-party calls use a mesh topology where each peer connects directly to every other peer. No central server relays media.'
  },
  ar: {
    'page.title': 'سرداب — تواصل مشفر بالكامل',
    'nav.features': 'المميزات',
    'nav.how': 'كيف يعمل',
    'nav.why': 'لماذا سرداب',
    'nav.security': 'الأمان',
    'nav.team': 'الفريق',
    'nav.roadmap': 'الخريطة',
    'nav.faq': 'الأسئلة',
    'nav.app': 'الدخول للتطبيق',
    'nav.apps': 'التطبيقات',
    'hero.badge': 'تشفير من طرف لطرف',
    'hero.line1': 'خصوصية مطلقة',
    'hero.line2': 'لكل محادثة',
    'hero.desc': 'منصة تواصل P2P لا مركزية حيث رسائلك ومكالماتك واجتماعاتك مشفرة بالكامل — لا وصول للخادم، لا تنازلات.',
    'hero.cta': 'ابدأ الآن',
    'hero.learn': 'كيف تعمل',
    'hero.scroll': 'اسحب لاستكشاف',
    'stat.encryption': 'مشفر',
    'stat.knowledge': 'معرفة الخادم',
    'stat.direct': 'اتصال مباشر',
    'feature.chat': 'دردشة',
    'feature.voice': 'صوت',
    'feature.video': 'فيديو',
    'feature.meet': 'اجتماعات',
    'features.tag': 'أربع تطبيقات، منصة واحدة',
    'features.title': 'كل ما تحتاجه للتواصل بآمان',
    'features.sub': 'تطبيقات لا مركزية بالكامل مع تشفير شامل في كل رسالة ومكالمة واجتماع.',
    'features.chat.title': 'دردشة P2P',
    'features.chat.desc': 'رسائل مشفرة من طرف لطرف عبر WebRTC DataChannels. أرسل نصوصاً وملفات وصوراً. الخادم لا يرى رسائلك.',
    'features.chat.link': 'افتح الدردشة',
    'features.voice.title': 'مكالمات صوتية',
    'features.voice.desc': 'مكالمات صوتية نقية مشفرة. تشفير SRTP للوسائط. مشاركة الشاشة مضمنة.',
    'features.voice.link': 'ابدأ مكالمة',
    'features.video.title': 'مكالمات فيديو',
    'features.video.desc': 'فيديو عالي الوضوح مشفر مع تبديل الكاميرا. تشفير DTLS-SRTP يضمن أن فقط أنت والمتلقي ترون بعضكم.',
    'features.video.link': 'افتح الكاميرا',
    'features.meet.title': 'اجتماعات',
    'features.meet.desc': 'اجتماعات P2P متعددة المشاركين مع فيديو وصوت ودردشة وشاشة. وسائطك لا تمر عبر أي خادم.',
    'features.meet.link': 'أنشئ اجتماعاً',
    'security.tag': 'هندسة صفر معرفة',
    'security.title': 'خصوصيتك هي الأساس',
    'security.sub': 'صممنا سرداب بحيث حتى نحن لا نستطيع الوصول لبياناتك. كل طبقة مبنية على الخصوصية.',
    'security.1.title': 'أنشئ غرفة',
    'security.1.desc': 'شارك رمز الغرفة مع من تريد التحدث معه. الرمز يُهشم — لا نرى الأصل أبداً.',
    'security.2.title': 'اشتقاق مفتاح التشفير',
    'security.2.desc': 'متصفحك يشتق مفتاح AES-256-GCM من رمز الغرفة باستخدام PBKDF2 مع 200 ألف تكرار SHA-512. المفتاح لا يغادر جهازك.',
    'security.3.title': 'اتصال مباشر P2P',
    'security.3.desc': 'WebRTC ينشئ قناة مشفرة مباشرة. خادم الإشارات يبتعد — وسائطك لا تمر عبرنا.',
    'security.4.title': 'اتصال مشفر من طرف لطرف',
    'security.4.desc': 'الرسائل تُشفر بـ AES-256-GCM. الوسائط مؤمنة بـ DTLS و SRTP. لا شيء يُخزن.',
    'tech.tag': 'مبني على تقنيات مثبتة',
    'tech.title': 'مجموعة تشفير بمستوى مؤسسات',
    'tech.webrtc': 'اتصال فوري من نظير لنظير',
    'tech.aes': 'معيار تشفير الرسائل',
    'tech.pbkdf2': 'اشتقاق المفاتيح بـ 200 ألف تكرار',
    'tech.sha512': 'تجزئة تشفيرية',
    'tech.dtls': 'تشفير تدفقات الوسائط',
    'tech.stun': 'عبور NAT لاتصالات موثوقة',
    'faq.tag': 'أسئلة شائعة',
    'faq.title': 'الأسئلة المتكررة',
    'faq.q1': 'هل سرداب خاصة حقاً؟',
    'faq.a1': 'نعم. سرداب يستخدم هندسة صفر معرفة. لا نخزن رسائل أو مكالمات أو ملفات. مفتاح التشفير يبقى في متصفحك.',
    'faq.q2': 'هل أحتاج لإنشاء حساب؟',
    'faq.a2': 'لا. سرداب لا يتطلب حساباً أو بريداً إلكترونياً أو رقم هاتف. فقط شارك رمز الغرفة وستتصل. الإخفاء الكامل مضمون.',
    'faq.q3': 'ماذا يحدث إذا انقطع الاتصال؟',
    'faq.a3': 'إذا انقطع الاتصال، أعد إدخال نفس رمز الغرفة لاستئناف المحادثة. لا شيء يُخزن على الخادم.',
    'faq.q4': 'هل يمكنني استخدام سرداب على الجوال؟',
    'faq.a4': 'نعم. سرداب تطبيق ويب تدريجي (PWA). افتحه في متصفح جوالك وأضفه للشاشة الرئيسية. يعمل على iOS و Android.',
    'faq.q5': 'هل سرداب مفتوح المصدر؟',
    'faq.a5': 'سرداب مبني من قبل KumoCoders. الكود المصدري متاح للمراجعة والمساهمة. اتصل بنا للوصول.',
    'faq.q6': 'كيف يقارن سرداب مع Signal أو WhatsApp؟',
    'faq.a6': 'على عكس Signal و WhatsApp، سرداب لا يتطلب رقم هاتف أو حساب أو معرف شخصي. خوادمنا تخزن صفر بيانات — لا جهات اتصال، لا رسائل، لا بيانات وصفية. بينما يستخدم Signal خوادم مركزية لتوصيل الرسائل، سرداب يستخدم اتصالات ند للند المباشرة عبر WebRTC. المقابل هو أن كلا الطرفين يجب أن يكونا متصلين في نفس الوقت، ولا يوجد سجل رسائل عند مغادرة الغرفة. سرداب مصمم للجلسات الخاصة المؤقتة بدلاً من أرشفة الرسائل الدائمة.',
    'faq.q7': 'هل يمكنني استخدام سرداب للتواصل التجاري أو الجماعي؟',
    'faq.a7': 'بالتأكيد. سرداب مجاني ولا يتطلب ترخيصاً أو إعداد خادم أو إدارة مستخدمين. يمكن للفرق استخدامه للاجتماعات المشفرة ومشاركة الملفات والمراسلة الفورية دون أن تمر بياناتهم عبر خوادم الشركات. لا حدود للمستخدمين، لا حصص تخزين، ولا سياسات احتفاظ بالبيانات. للمؤسسات التي تتعامل مع معلومات حساسة، يوفر سرداب طبقة اتصال عدمية الثقة حيث حتى مزود المنصة لا يمكنه الوصول إلى اتصالاتكم.',
    'faq.q8': 'ما خوارزميات التشفير التي يستخدمها سرداب؟',
    'faq.a8': 'يستخدم سرداب طبقات تشفير متعددة. الرسائل النصية والملفات مشفرة بـ AES-256-GCM، المعيار الصناعي للتشفير المتماثل. يُشتق مفتاح التشفير من رمز الغرفة باستخدام PBKDF2 مع 200,000 تكرار من SHA-512. الصوت والفيديو مؤمنان بـ DTLS و SRTP، بروتوكولات التشفير القياسية لـ WebRTC. كل التشفير يحدث في المتصفح — المتصفح يقوم بالعمل، وليس خوادمنا.',
    'faq.q9': 'هل هناك حد لحجم الملفات المنقولة؟',
    'faq.a9': 'سرداب لا يفرض حداً لحجم الملفات لأن الملفات تُنقل مباشرة بين الأجهزة عبر WebRTC DataChannels — لا تمر عبر خادمنا. الحد العملي يعتمد على ذاكرة المتصفح وتنفيذ WebRTC. معظم المتصفحات الحديثة تتعامل مع ملفات حتى 1-2 جيجابايت بشكل موثوق. الملفات تشفر قبل النقل وتفك تشفيرها فقط على جهاز المستلم. نوصي بضغط الملفات الكبيرة قبل الإرسال للأداء الأمثل.',
    'faq.q10': 'كيف تعمل رموز الغرف وهل هي آمنة؟',
    'faq.a10': 'رموز الغرف هي سلاسل عشوائية تعمل كمعرف للغرفة وأساس لمفتاح التشفير. عند إنشاء غرفة، يُولد الرمز في متصفحك ثم يُهشم باتجاه واحد بـ SHA-512 قبل إرساله لخادم الإشارات — الرمز الأصلي لا يظهر لنا أبداً. التجزئة تستخدم فقط لتوجيه إشارات الاتصال. الرمز الأصلي يبقى في متصفحك ويستخدم لاشتقاق مفتاح تشفير AES-256-GCM عبر PBKDF2. هذا يعني أنه حتى لو اخترق أحد خادمنا، سيرى فقط رموز غرف مهشمة لا يمكن عكسها، ولن يستطيع فك تشفير أي اتصالات بدون الرمز الأصلي.',
    'who.tag': 'حالات الاستخدام',
    'who.title': 'لمن سرداب؟',
    'who.sub': 'من الصحفيين إلى المطورين — سرداب يخدم كل من يقدر الخصوصية.',
    'who.1.title': 'صحفيون ومبلغون',
    'who.1.desc': 'اتصال آمن ومجهول دون أي أثر. انشر دون خوف.',
    'who.2.title': 'مطورون ومهندسون',
    'who.2.desc': 'بروتوكول مفتوح، كود قابل للفحص، بنية ند للند. لا صناديق سوداء.',
    'who.3.title': 'دعاة الخصوصية',
    'who.3.desc': 'صفر معرفة بالتصميم. لا حسابات، لا سجلات، لا جمع بيانات.',
    'who.4.title': 'محادثات يومية',
    'who.4.desc': 'بسيط وسريع وخاص. فقط شارك رمز الغرفة وتحدث.',
    'diff.tag': 'لماذا سرداب',
    'diff.title': 'مبني بطريقة مختلفة — عن قصد',
    'diff.sub': 'كل خيار تصميمي يبدأ بسؤال واحد: هل هذا يحمي المستخدم؟',
    'diff.1.title': 'تشفير من طرف إلى طرف',
    'diff.1.desc': 'الرسائل مشفرة بـ AES-256-GCM. المفاتيح تُشتق من جهة العميل. حتى نحن لا نستطيع قراءتها.',
    'diff.2.title': 'صفر معرفة للخادم',
    'diff.2.desc': 'خوادمنا ترسل إشارات الاتصال فقط. الرسائل والملفات والوسائط لا تلمس القرص أبداً.',
    'diff.3.title': 'لا حساب مطلوب',
    'diff.3.desc': 'لا بريد إلكتروني، لا هاتف، لا كلمة مرور. إخفاء كامل من أول نقرة.',
    'diff.4.title': 'اتصالات ند للند مباشرة',
    'diff.4.desc': 'الوسائط تتدفق مباشرة بين الأقران عبر WebRTC. لا خادم يمرر صوتك أو فيديوك.',
    'diff.sardab': 'سرداب',
    'diff.others': 'آخرون',
    'footer.dev1': 'نصر الدين براس',
    'footer.dev2': 'سارة شهاب',
    'footer.dev3': 'دعاء منار',
    'footer.legal': 'الشروط والخصوصية',
    'legal.title': 'الشروط والخصوصية',
    'legal.terms': 'شروط الخدمة',
    'legal.privacy': 'سياسة الخصوصية',
    'legal.close': 'إغلاق',
    'team.tag': 'الفريق',
    'team.title': 'بُني بواسطة أشخاص يهتمون بالخصوصية',
    'team.sub': 'ثلاثة مطورين، مهمة واحدة — جعل الاتصال الخاص متاحاً للجميع.',
    'team.1.name': 'نصر الدين براس',
    'team.1.role': 'مؤسس ومطور ومصمم',
    'team.1.desc': 'مؤسس KumoCoders. مطور ومصمم شامل وراء بنية سرداب وإشارات WebRTC وطبقة التشفير والهوية البصرية.',
    'team.2.name': 'سارة شهاب',
    'team.2.role': 'مؤسسة مشاركة ومهندسة برمجيات',
    'team.2.desc': 'مؤسسة مشاركة في KumoCoders. مهندسة برمجيات متخصصة في التطوير الكامل وواجهات المستخدم وأنظمة الاتصال الفوري.',
    'team.3.name': 'دعاء منار',
    'team.3.role': 'مهندسة برمجيات',
    'team.3.desc': 'مهندسة برمجيات تساهم في تطوير سرداب واختباره وضمان جودته مع التركيز على ميزات الاتصال الموثوقة.',
    'team.supported': 'ادعمنا',
    'team.qabila.name': 'قبيلة',
    'team.qabila.sub': 'شكراً للهاكاثون',
    'team.mortakaz.name': 'مرتكز',
    'team.mortakaz.sub': 'شكراً لعرض مشروعنا',
    'road.tag': 'قريباً',
    'road.title': 'ماذا بعد لسرداب',
    'road.sub': 'لقد بدأنا للتو. إليك ما نبنيه بعد ذلك.',
    'road.now': 'الآن',
    'road.next': 'التالي',
    'road.future': 'المستقبل',
    'road.1.title': 'تثبيت المنصة الأساسية',
    'road.1.desc': 'إشارات WebRTC الشبكية، الرسائل المشفرة، مكالمات الصوت والفيديو، مشاركة الشاشة، نقل الملفات، الاجتماعات متعددة الأطراف، وتجربة الصفحة الرئيسية الكاملة.',
    'road.2.title': 'النقال وعبر المنصات',
    'road.2.desc': 'تطبيقات النقال الأصلية، تحسينات تطبيق الويب التقدمي، الإشعارات الفورية، تكامل الوضع الليلي، وتحسين اختصارات لوحة المفاتيح.',
    'road.3.title': 'ميزات متقدمة ونظام بيئي',
    'road.3.desc': 'مشاركة ملفات مشفرة مع معاينة، رسائل صوتية، رسائل مؤقتة، خلفيات غرف مخصصة، واجهة برمجة بوتات، بروتوكول اتحاد، وهوية لا مركزية.',
    'road.1.b1': 'طوبولوجيا P2P الشبكية',
    'road.1.b2': 'تشفير AES-256-GCM',
    'road.1.b3': 'مشاركة الشاشة',
    'road.1.b4': 'اجتماعات متعددة الأطراف',
    'road.2.b1': 'تثبيت PWA',
    'road.2.b2': 'إشعارات فورية',
    'road.2.b3': 'وضع غير متصل',
    'road.2.b4': 'تطبيق سطح المكتب (Electron)',
    'road.3.b1': 'رسائل مؤقتة',
    'road.3.b2': 'رسائل صوتية',
    'road.3.b3': 'واجهة برمجة بوتات',
    'road.3.b4': 'بروتوكول اتحاد',
    'terms.1.title': '١. قبول الشروط',
    'terms.1.body': 'باستخدامك لسرداب ("الخدمة")، أنت توافق على الالتزام بشروط الخدمة هذه. إذا كنت لا توافق، فلا تستخدم الخدمة. الاستمرار في الاستخدام يشكل قبولاً لأي تعديلات مستقبلية.',
    'terms.2.title': '٢. وصف الخدمة',
    'terms.2.body': 'سرداب هي منصة اتصال مشفرة من نظير إلى نظير تسهل الاتصالات المباشرة بين المستخدمين عبر WebRTC. تقدم الخدمة إمكانيات الدردشة والصوت والفيديو والاجتماعات. جميع الوسائط والرسائل مشفرة من طرف إلى طرف ولا تُخزن على خوادمنا. سرداب لا توفر أو تشغل أو تحتفظ بأي بنية تحتية لترحيل أو تسجيل اتصالات المستخدمين تتجاوز بيانات الإشارات المؤقتة اللازمة لإنشاء اتصالات النظير.',
    'terms.3.title': '٣. أهلية المستخدم والتسجيل',
    'terms.3.body': 'الخدمة متاحة للجميع دون تسجيل. لا حاجة لحساب أو بريد إلكتروني أو رقم هاتف أو معرف شخصي. يجب أن يكون المستخدمون على الأقل ١٣ عاماً. باستخدامك للخدمة، أنت تقر باستيفائك لهذا الشرط. يُمنع المستخدمون تحت ١٣ عاماً من استخدام الخدمة تحت أي ظرف.',
    'terms.4.title': '٤. سلوك المستخدم والأنشطة المحظورة',
    'terms.4.body': 'أنت توافق على عدم استخدام سرداب لأي غرض غير قانوني أو مخالف للقوانين السارية. الأنشطة المحظورة تشمل على سبيل المثال لا الحصر: نقل البرمجيات الخبيثة أو الفيروسات أو الأكواد الضارة؛ الانخراط في التحرش أو التهديد أو الإساءة؛ توزيع المحتوى غير القانوني؛ محاولة الهندسة العكسية أو اختراق التشفير؛ التدخل في اتصالات المستخدمين الآخرين؛ استخدام البوتات أو البرامج النصية الآلية للتفاعل مع الخدمة؛ شن هجمات شبكية بما في ذلك DDoS أو الفيض أو إساءة استخدام الإشارات. تحتفظ سرداب بالحق في إنهاء وصول المستخدمين الذين يمارسون أنشطة محظورة.',
    'terms.5.title': '٥. الملكية الفكرية',
    'terms.5.body': 'اسم سرداب وشعاره وعلامته التجارية وتصميمه البصري هم ملكية فكرية لـ KumoCoders ومطوريها. الكود المصدري الأساسي متاح للمراجعة والمساهمة بنموذج المصدر المشترك. يحتفظ المستخدمون بجميع الحقوق في اتصالاتهم الخاصة. سرداب لا تدعي ملكية أي محتوى يُنقل عبر الخدمة. لا يجوز لك إعادة إنتاج أو توزيع أو إنشاء أعمال مشتقة من أصول سرداب التجارية دون إذن كتابي صريح.',
    'terms.6.title': '٦. الخصوصية والبيانات',
    'terms.6.body': 'سرداب مصمم كنظام صفر معرفة. نحن لا نجمع أو نخزن أو نعالج البيانات الشخصية. الاتصالات مشفرة من جهة العميل وتُنقل مباشرة بين الأقران. بيانات الإشارات (المحدودة بمعلومات مصافحة WebRTC) تُرحل بشكل مؤقت ولا تُحفظ. لا يُخزن أي محتوى رسائل أو تدفقات وسائط أو محتويات ملفات أو بيانات وصفية عن الاتصالات أو عناوين IP بعد انتهاء مدة الاتصال أو أي معلومات تحدد هوية المستخدم. للتفاصيل الكاملة، راجع سياسة الخصوصية.',
    'terms.7.title': '٧. التشفير والأمان',
    'terms.7.body': 'الرسائل مشفرة باستخدام AES-256-GCM بمفاتيح تُشتق من جهة العميل عبر PBKDF2 مع ٢٠٠,٠٠٠ تكرار من SHA-512. تدفقات الوسائط مؤمنة بتشفير DTLS-SRTP. مفتاح التشفير يُشتق من رمز الغرفة ولا يُنقل أبداً إلى أي خادم. سرداب لا تقدم ضماناً بأن تنفيذ التشفير محصن ضد جميع أشكال الهجوم. يقر المستخدمون بأن الأمان المطلق غير قابل للتحقيق ويتحملون المخاطر الكامنة في الاتصال الرقمي.',
    'terms.8.title': '٨. إخلاء المسؤولية عن الضمانات',
    'terms.8.body': 'الخدمة مُقدمة "كما هي" و"كما هي متاحة" دون أي ضمانات من أي نوع، صريحة أو ضمنية، بما في ذلك على سبيل المثال لا الحصر ضمانات القابلية للتسويق والملاءمة لغرض معين وعدم الانتهاك أو التوفر. سرداب لا تضمن أن الخدمة ستكون دون انقطاع أو آمنة أو خالية من الأخطاء أو المكونات الضارة. يتحمل المستخدم كامل المخاطر الناشئة عن استخدام الخدمة أو أدائها. سرداب مُقدمة دون أي ضمان فيما يتعلق بدقة أو موثوقية أو اكتمال الخدمة.',
    'terms.9.title': '٩. الحد من المسؤولية',
    'terms.9.body': 'إلى أقصى حد يسمح به القانون، لن تكون KumoCoders أو مطوريها (نصر الدين براس، سارة شهاب، ودعاء منار) أو المساهمين مسؤولين عن أي أضرار غير مباشرة أو عرضية أو خاصة أو تبعية أو عقابية، بما في ذلك على سبيل المثال لا الحصر فقدان البيانات أو فقدان الأرباح أو انقطاع الخدمة أو تلف الكمبيوتر أو أي أضرار أخرى ناشئة عن استخدام أو عدم القدرة على استخدام الخدمة، حتى لو تم الإعلام بإمكانية حدوث هذه الأضرار. لا تتجاوز المسؤولية الإجمالية بأي حال المبلغ المدفوع من قبل المستخدم مقابل الخدمة، وهو صفر.',
    'terms.10.title': '١٠. التعويض',
    'terms.10.body': 'أنت توافق على تعويض والدفاع عن وحماية KumoCoders ومطوريها والشركات التابعة لها والمساهمين من أي مطالبات أو مسؤوليات أو أضرار أو خسائر أو نفقات، بما في ذلك الأتعاب القانونية المعقولة، الناشئة عن أو المرتبطة بأي شكل باستخدامك للخدمة أو انتهاكك لهذه الشروط أو انتهاكك لحقوق أي مستخدم آخر أو طرف ثالث.',
    'terms.11.title': '١١. إنهاء الخدمة',
    'terms.11.body': 'تحتفظ سرداب بالحق في تعديل أو تعليق أو إيقاف الخدمة أو أي جزء منها في أي وقت دون إشعار. قد ننهي أو نقيد وصولك إلى الخدمة بسبب انتهاك هذه الشروط، دون مسؤولية. عند الإنهاء، تظل جميع أحكام هذه الشروط التي بطبيعتها يجب أن تستمر بعد الإنهاء سارية، بما في ذلك على سبيل المثال لا الحصر الحد من المسؤولية وإخلاء المسؤولية عن الضمانات والتعويض.',
    'terms.12.title': '١٢. تعديلات الشروط',
    'terms.12.body': 'يجوز تحديث هذه الشروط في أي وقت. سيتم نشر التغييرات في هذه الصفحة. استمرارك في استخدام الخدمة بعد أي تعديلات يشكل قبولاً للشروط المنقحة. من مسؤوليتك مراجعة هذه الشروط دورياً. سيتم الإبلاغ عن التغييرات الجوهرية عبر الخدمة.',
    'terms.13.title': '١٣. القانون الحاكم والاختصاص القضائي',
    'terms.13.body': 'تخضع هذه الشروط وتُفسر وفقاً للقوانين الدولية المعمول بها. أي نزاعات ناشئة عن هذه الشروط أو الخدمة تُحل عن طريق التحكيم الملزم وفقاً لقواعد الاختصاص القضائي ذي الصلة. يوافق المستخدمون على أن أي إجراءات قانونية تُجرى باللغة الإنجليزية أو العربية حسب تقدير الخدمة.',
    'terms.14.title': '١٤. معلومات الاتصال',
    'terms.14.body': 'للاستفسارات أو الاستفسارات القانونية بخصوص هذه الشروط، يرجى الاتصال بفريق التطوير: KumoCoders، ممثلة بنصر الدين براس وسارة شهاب ودعاء منار. مستودع المشروع وقنوات الاتصال متاحة عبر موقع KumoCoders الرسمي.',
    'privacy.1.title': '١. المقدمة والنطاق',
    'privacy.1.body': 'توضح سياسة الخصوصية هذه كيفية تعامل سرداب مع المعلومات عند استخدامك للخدمة. سرداب مصممة من الألف إلى الياء كنظام صفر معرفة. مبدأنا الأساسي هو أنه لا ينبغي لنا — ولا يمكننا تقنياً — الوصول إلى اتصالاتك. تصف هذه السياسة البيانات المحدودة التي نتعامل معها وكيفية حمايتها وما لا نجمعه. باستخدامك لسرداب، أنت تقر وتوافق على الممارسات الموصوفة في هذه السياسة.',
    'privacy.2.title': '٢. المعلومات التي لا نجمعها',
    'privacy.2.body': 'سرداب مصممة عمداً لعدم جمع أي معلومات شخصية. نحن لا نجمع: الأسماء أو عناوين البريد الإلكتروني أو أرقام الهواتف أو العناوين الفعلية أو أرقام الهوية الحكومية أو معلومات الدفع أو البيانات البيومترية أو معرفات الأجهزة أو قوائم جهات الاتصال أو بيانات الموقع أو سجل التصفح أو البيانات السلوكية أو أي معلومات أخرى تحدد الهوية الشخصية. نحن لا نستخدم بكسلات التتبع أو نصوص التحليلات أو تقنيات البصمات أو أي شكل من أشكال مراقبة المستخدمين. عدم وجود حساب يعني عدم تخزين أي بيانات ملف تعريف على الإطلاق.',
    'privacy.3.title': '٣. المعلومات التي نتعامل معها (وكيف)',
    'privacy.3.body': 'البيانات الوحيدة التي تعالجها خوادمنا هي معلومات إشارات WebRTC المؤقتة اللازمة لإنشاء اتصالات الند للند. يشمل ذلك عروض وإجابات بروتوكول وصف الجلسة (SDP) ومرشحي ICE لعبور NAT وتجزئات رموز الغرف. هذه البيانات تُنقل في الذاكرة فقط ولا تُسجل أو تُخزن أو تُحفظ على القرص. رموز الغرف تُهشم باتجاه واحد باستخدام SHA-512 قبل الوصول إلى خادمنا — الرمز الأصلي غير مرئي لنا أبداً. بيانات الإشارات تُتخلص منها فوراً بعد إنشاء اتصال النظير أو عند انتهاء جلسة الغرفة.',
    'privacy.4.title': '٤. بنية التشفير',
    'privacy.4.body': 'جميع الاتصالات على سرداب مشفرة افتراضياً في كل طبقة. الرسائل النصية والملفات وبيانات الدردشة مشفرة بتشفير AES-256-GCM المتماثل. مفتاح التشفير يُشتق من جهة العميل من رمز الغرفة باستخدام PBKDF2 مع ٢٠٠,٠٠٠ تكرار من SHA-512 — هذا المفتاح لا يغادر متصفحك أبداً. تدفقات الصوت والفيديو مشفرة بـ DTLS-SRTP، بروتوكول تشفير وسائط WebRTC القياسي. حتى لو اعترض طرف ثالث مرحل الإشارات، سيرون فقط بيانات مشفرة لا يمكنهم فك تشفيرها. سرداب ليس لديها قدرة على فك تشفير أو مراقبة أو الوصول إلى محتوى أي اتصال مستخدم.',
    'privacy.5.title': '٥. الاحتفاظ بالبيانات وحذفها',
    'privacy.5.body': 'سرداب لا تحتفظ بأي بيانات مستخدم. معلومات الإشارات مؤقتة وتوجد فقط في ذاكرة الخادم طوال مدة عملية الإشارات. لا توجد قواعد بيانات أو ملفات سجلات أو نسخ احتياطية أو أرشيفات تحتوي على اتصالات المستخدمين. رموز الغرف مهشمة وتُستخدم فقط لتوجيه رسائل الإشارات؛ التجزئة لا تُخزن بعد تدمير الغرفة. إذا أغلقت متصفحك أو غادرت الغرفة، لا يوجد سجل لجلساتك في أي مكان. لا يوجد شيء لحذفه لأنه لا يوجد شيء مخزن.',
    'privacy.6.title': '٦. خدمات الطرف الثالث',
    'privacy.6.body': 'سرداب لا تتكامل مع أي خدمات تحليلات أو إعلانات أو تتبع أو وسائط اجتماعية تابعة لطرف ثالث. الخدمة لا تحمل نصوصاً خارجية أو تدمج محتوى من طرف ثالث أو ترسل بيانات إلى خوادم خارجية تتجاوز مرحل الإشارات. خوادم STUN/TURN المستخدمة لعبور NAT تتلقى فقط الحد الأدنى من معلومات الشبكة اللازمة لإنشاء الاتصالات (عناوين IP والمنافذ) ولا تسجل أو تحتفظ بهذه المعلومات. يُشجع المستخدمون على مراجعة ممارسات الخصوصية لأي إضافات متصفح طرف ثالث أو بنية تحتية للشبكة قد يستخدمونها.',
    'privacy.7.title': '٧. بيانات الشبكة والاتصال',
    'privacy.7.body': 'عند استخدامك لسرداب، عنوان IP الخاص بك مرئي لخادم الإشارات طوال مدة مصافحة الإشارات ولأي أقران تتصل بهم مباشرة (ضروري لاتصال WebRTC من نظير إلى نظير). عناوين IP لا تُسجل أو تُخزن أو تُربط بأي معرف مستخدم. إذا كنت تستخدم مرحل TURN (مطلوب لبعض بيئات الشبكة المقيدة)، سيرى خادم TURN عنوان IP والمنفذ الخاص بك أثناء الاتصال. نوصي باستخدام VPN لطبقة إضافية من خصوصية الشبكة إذا رغبت.',
    'privacy.8.title': '٨. ملفات تعريف الارتباط والتخزين المحلي',
    'privacy.8.body': 'سرداب لا تستخدم ملفات تعريف الارتباط للتتبع أو التحليلات أو الإعلانات. التخزين المحلي الوحيد المستخدم هو تفضيل اللغة الخاص بك (sardab-lang)، المُخزن في localStorage للمتصفح ولا يُنقل أبداً إلى أي خادم. يُستخدم هذا فقط لتذكر اختيار لغة الواجهة بين الجلسات. يمكنك مسح هذا في أي وقت من خلال إعدادات المتصفح. لا توجد بيانات أخرى مخزنة على جهازك بواسطة سرداب.',
    'privacy.9.title': '٩. خصوصية الأطفال',
    'privacy.9.body': 'سرداب ليست موجهة للأطفال دون سن ١٣ عاماً. نحن لا نجمع عن قصد أي معلومات من الأطفال. إذا أصبحنا على علم بأن مستخدماً تحت ١٣ عاماً يستخدم الخدمة، سننهي وصوله. يُنصح الآباء والأوصياء بمراقبة أنشطة أطفالهم عبر الإنترنت. يُمنع المستخدمون تحت ١٣ عاماً من استخدام سرداب تحت أي ظرف. نشجع الآباء على تعليم أطفالهم ممارسات الاتصال الآمنة عبر الإنترنت.',
    'privacy.10.title': '١٠. حقوقك وخياراتك',
    'privacy.10.body': 'لأن سرداب لا تجمع بيانات شخصية، حقوق أصحاب البيانات التقليدية (الوصول والتصحيح والمحو وقابلية النقل) مستوفاة أصلاً — لا يوجد شيء للوصول إليه أو تصحيحه أو حذفه. لديك الحق في استخدام الخدمة بشكل مجهول دون تقديم أي معلومات شخصية. لديك الحق في التوقف عن استخدام الخدمة في أي وقت، مما ينهي فوراً جميع معالجة البيانات. لديك الحق في التحقق من ادعاءاتنا بانعدام المعرفة من خلال فحص الكود المصدري. بموجب قوانين الخصوصية المعمول بها بما في ذلك على سبيل المثال لا الحصر GDPR و CCPA، لا تحدث أي معالجة بيانات للمعلومات الشخصية.',
    'privacy.11.title': '١١. تدابير الأمان',
    'privacy.11.body': 'تستخدم سرداب بروتوكولات تشفير بمستوى صناعي لحماية الاتصالات. خادم الإشارات يعمل بامتيازات دنيا وبدون تخزين دائم. جميع اتصالات الخادم تستخدم HTTPS/WSS (WebSocket آمن). الكود المصدري متاح للتدقيق والمراجعة الأمنية المستقلة. على الرغم من هذه التدابير، لا يوجد نظام آمن تماماً. المستخدمون مسؤولون عن حماية أجهزتهم والحفاظ على اتصالات شبكة آمنة والحفاظ على خصوصية رموز غرفهم. توصي سرداب باستخدام رموز غرف قوية وفريدة لكل جلسة اتصال.',
    'privacy.12.title': '١٢. نقل البيانات الدولي',
    'privacy.12.body': 'خادم إشارات سرداب قد يكون موجوداً في أي ولاية قضائية. نظراً لعدم جمع أو تخزين أي بيانات شخصية، لوائح نقل البيانات الدولية ليس لها تأثير عملي — لا توجد بيانات مستخدم لنقلها عبر الحدود. اتصالات الند للند تحدث مباشرة بين أجهزة المستخدمين بغض النظر عن الموقع الجغرافي. سرداب لا توجه الاتصالات عبر ولايات قضائية محددة أو تطبق قيوداً جغرافية.',
    'privacy.13.title': '١٣. التغييرات في هذه السياسة',
    'privacy.13.body': 'يجوز تحديث سياسة الخصوصية هذه من وقت لآخر. سيتم نشر التغييرات في هذه الصفحة مع تاريخ سريان محدث. سيتم الإبلاغ عن التغييرات الجوهرية عبر الخدمة. استمرار استخدامك لسرداب بعد تغييرات السياسة يشكل قبولاً للشروط المحدثة. نشجع المستخدمين على مراجعة هذه السياسة دورياً. تم تحديث النسخة الحالية آخر مرة في يونيو ٢٠٢٦.',
    'privacy.14.title': '١٤. الاتصال والمسؤول عن البيانات',
    'privacy.14.body': 'سرداب مُطورة ومُحافظة من قبل KumoCoders، مجموعة من المطورين تركز على تكنولوجيا الحفاظ على الخصوصية. فريق التطوير يتكون من نصر الدين براس وسارة شهاب ودعاء منار. للاستفسارات المتعلقة بالخصوصية أو الإفصاحات الأمنية، يرجى الاتصال بالفريق عبر مستودع أو موقع KumoCoders الرسمي. نظراً لأن سرداب لا تجمع بيانات شخصية، لا يوجد مسؤول عن البيانات أو مسؤول حماية بيانات — لا توجد بيانات للتحكم أو الحماية من جهتنا.',
    'cta.title': 'ابدأ التواصل بخصوصية',
    'cta.desc': 'لا حساب. لا إعداد. لا تتبع. فقط تواصل مشفر نقي.',
    'cta.btn': 'افتح سرداب',
    'footer.tag': 'تشفير كامل',
    'footer.zero': 'صفر معرفة',
    'footer.direct': 'اتصال مباشر',
    'footer.rights': 'جميع الحقوق محفوظة.',
    'footer.by': 'ببناء',
    'brand.name': 'سرداب',
    'brand.aes': 'AES-256-GCM',
    'brand.webrtc': 'WebRTC P2P',
    'brand.zero': 'صفر معرفة',
    'brand.open': 'بروتوكول مفتوح',
    'how.tag': 'خطوات بسيطة',
    'how.title': 'كيف تعمل في ٤ خطوات',
    'how.sub': 'من إنشاء الغرفة إلى الاتصال المشفر — كل خطوة صممت للخصوصية والسهولة والسرعة.',
    'sec2.title': 'كل طبقة، مؤمنة',
    'sec2.sub': 'من إنشاء الغرفة إلى نقل البيانات، كل مكون صمم مع الخصوصية كخيار افتراضي.',
    'sec2.1.title': 'رموز غرف مهشمة',
    'sec2.1.desc': 'رموز الغرف تُهشّم باتجاه واحد قبل الوصول لخادمنا. الرمز الأصلي لا يُخزن أو يُسجل أبداً.',
    'sec2.2.title': 'تشفير AES-256-GCM',
    'sec2.2.desc': 'جميع الرسائل مشفرة بمعيار AES-256-GCM. المفاتيح تُشتق من جهة العميل باستخدام PBKDF2 مع 200,000 تكرار.',
    'sec2.3.title': 'لا تخزين بيانات',
    'sec2.3.desc': 'خوادمنا لا تخزن شيئاً — الرسائل والملفات والوسائط تتدفق مباشرة بين الأقران. لا قواعد بيانات. لا سجلات. لا تاريخ.',
    'sec2.4.title': 'وسائط DTLS / SRTP',
    'sec2.4.desc': 'تدفقات الصوت والفيديو مؤمنة بتشفير DTLS-SRTP. حتى مرحل الإشارات لا يستطيع فك تشفير وسائطك.',
    'sec2.5.title': 'لا حساب مطلوب',
    'sec2.5.desc': 'لا بريد إلكتروني، لا رقم هاتف، لا كلمة مرور. إخفاء كامل — فقط شارك رمز الغرفة وستتصل.',
    'sec2.6.title': 'طوبولوجيا Mesh P2P',
    'sec2.6.desc': 'المكالمات الجماعية تستخدم طوبولوجيا شبكية حيث يتصل كل نظير مباشرة بكل نظير آخر. لا خادم مركزي لترحيل الوسائط.'
  }
});
</script>
<script src="<?=$basePath?>/assets/js/main.js?v=3"></script>
<script>
(function initLandingV2(){
  // --- Hero Canvas with mouse interaction ---
  var c=document.getElementById('heroCanvas');
  if(c){
    var x=c.getContext('2d');
    var w,h,p=[],mx=null,my=null;
    function resize(){
      w=c.width=window.innerWidth;
      h=c.height=window.innerHeight;
      p=[];var n=Math.min(Math.floor((w*h)/12000),55);
      for(var i=0;i<n;i++)p.push({x:Math.random()*w,y:Math.random()*h,vx:(Math.random()-0.5)*0.25,vy:(Math.random()-0.5)*0.25});
    }
    resize();
    c.addEventListener('mousemove',function(e){mx=e.clientX;my=e.clientY});
    c.addEventListener('mouseleave',function(){mx=null;my=null});
    function draw(){
      x.clearRect(0,0,w,h);
      for(var i=0;i<p.length;i++){
        var a=p[i];a.x+=a.vx;a.y+=a.vy;
        if(a.x<0||a.x>w)a.vx*=-1;if(a.y<0||a.y>h)a.vy*=-1;
        var near=false;
        if(mx!==null){
          var dx=mx-a.x,dy=my-a.y;
          if(dx*dx+dy*dy<50000){
            near=true;
            x.beginPath();x.moveTo(a.x,a.y);x.lineTo(mx,my);
            x.strokeStyle='rgba(140,120,240,0.06)';x.lineWidth=1;x.stroke();
          }
        }
        for(var j=i+1;j<p.length;j++){
          var b=p[j],dx2=a.x-b.x,dy2=a.y-b.y,d=dx2*dx2+dy2*dy2;
          if(d<25000){
            x.beginPath();x.moveTo(a.x,a.y);x.lineTo(b.x,b.y);
            x.strokeStyle='rgba(120,120,220,'+(0.012+Math.random()*0.02)+')';x.lineWidth=0.5;x.stroke();
          }
        }
        x.beginPath();x.arc(a.x,a.y,near?2.5:1.2,0,Math.PI*2);
        x.fillStyle=near?'rgba(160,140,255,0.5)':'rgba(120,120,220,0.25)';x.fill();
      }
      requestAnimationFrame(draw);
    }
    draw();
    window.addEventListener('resize',resize);
  }

  // --- Floating particles ---
  var hp=document.getElementById('heroParticles');
  if(hp){
    for(var i=0;i<25;i++){
      var el=document.createElement('div');el.className='hero-v2-particle';
      el.style.left=(Math.random()*100)+'%';el.style.bottom='0';
      el.style.animationDuration=(6+Math.random()*14)+'s';el.style.animationDelay=(Math.random()*10)+'s';
      el.style.width=el.style.height=(1+Math.random()*4)+'px';
      hp.appendChild(el);
    }
  }

  // --- Scroll-based appear animations ---
  var observer=new IntersectionObserver(function(entries){
    entries.forEach(function(e){
      if(e.isIntersecting){e.target.classList.add('visible');observer.unobserve(e.target)}
    })
  },{threshold:0.1});
  document.querySelectorAll('.appear-v2').forEach(function(el){observer.observe(el)});

  // --- Count-up animation on metrics ---
  var counted=false;
  var metObs=new IntersectionObserver(function(entries){
    entries.forEach(function(e){
      if(e.isIntersecting&&!counted){
        counted=true;
        var nums=e.target.querySelectorAll('.hero-v2-metric-num');
        nums.forEach(function(el){
          var raw=el.textContent.trim(),target=parseFloat(raw),suffix=raw.replace(/[\d.]/g,'');
          if(isNaN(target))return;
          var current=0,step=Math.ceil(target/50);
          var t=setInterval(function(){
            current+=step;
            if(current>=target){current=target;clearInterval(t)}
            el.textContent=Math.floor(current)+suffix;
          },20);
        });
        metObs.unobserve(e.target);
      }
    });
  },{threshold:0.5});
  var met=document.querySelector('.hero-v2-metrics');
  if(met)metObs.observe(met);

  // --- Cursor glow follower ---
  var cg=document.getElementById('cursorGlow');
  if(cg){
    document.addEventListener('mousemove',function(e){cg.style.left=e.clientX+'px';cg.style.top=e.clientY+'px'});
    document.addEventListener('mouseleave',function(){cg.style.opacity='0'});
    document.addEventListener('mouseenter',function(){cg.style.opacity='1'});
  }

  // --- Hero scroll arrow ---
  var hs=document.getElementById('heroScroll');
  if(hs){
    var toggler=function(){hs.style.opacity=Math.max(0,1-window.scrollY/300)};
    window.addEventListener('scroll',toggler,{passive:true});toggler();
  }
})();
</script>
<div id="legalModal">
  <div class="modal-overlay" onclick="document.getElementById('legalModal').classList.remove('open')"></div>
  <div class="modal-wrap">
    <div class="modal-header">
      <h3 data-i18n="legal.title">Terms &amp; Privacy</h3>
      <button class="modal-close" onclick="document.getElementById('legalModal').classList.remove('open')"><span data-i18n="legal.close">Close</span> <i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-tabs">
      <button class="modal-tab active" data-tab="terms" onclick="switchLegalTab('terms')" data-i18n="legal.terms">Terms of Service</button>
      <button class="modal-tab" data-tab="privacy" onclick="switchLegalTab('privacy')" data-i18n="legal.privacy">Privacy Policy</button>
    </div>
    <div class="modal-body">
      <!-- TERMS PANE -->
      <div class="modal-pane active" id="pane-terms">
        <h4 data-i18n="terms.1.title">1. Acceptance of Terms</h4>
        <p data-i18n="terms.1.body">By accessing or using Sardab ("the Service"), you agree to be bound by these Terms of Service. If you do not agree, do not use the Service. Continued use constitutes acceptance of any future modifications.</p>

        <h4 data-i18n="terms.2.title">2. Service Description</h4>
        <p data-i18n="terms.2.body">Sardab is a peer-to-peer encrypted communication platform that facilitates direct connections between users via WebRTC. The Service offers chat, voice, video, and meeting capabilities. All media and messages are encrypted end-to-end and are not stored on our servers. Sardab does not provide, operate, or maintain any infrastructure for relaying or recording user communications beyond transient signaling data required to establish peer connections.</p>

        <h4 data-i18n="terms.3.title">3. User Eligibility &amp; Registration</h4>
        <p data-i18n="terms.3.body">The Service is available to anyone without registration. No account, email address, phone number, or personal identifier is required. Users must be at least 13 years of age. By using the Service, you represent that you meet this requirement. Users under 13 may not use the Service under any circumstances.</p>

        <h4 data-i18n="terms.4.title">4. User Conduct &amp; Prohibited Activities</h4>
        <p data-i18n="terms.4.body">You agree not to use Sardab for any unlawful purpose or in violation of any applicable laws. Prohibited activities include but are not limited to: transmitting malware, viruses, or harmful code; engaging in harassment, threats, or abuse; distributing illegal content; attempting to reverse-engineer, crack, or compromise the encryption; interfering with other users' connections; using automated bots or scripts to interact with the Service; conducting network attacks including but not limited to DDoS, flooding, or signaling abuse. Sardab reserves the right to terminate access for users engaging in prohibited activities.</p>

        <h4 data-i18n="terms.5.title">5. Intellectual Property</h4>
        <p data-i18n="terms.5.body">The Sardab name, logo, brand, and visual design are the intellectual property of KumoCoders and its developers. The underlying source code is available for review and contribution under a shared-source model. Users retain all rights to their own communications. Sardab does not claim ownership of any content transmitted through the Service. You may not reproduce, distribute, or create derivative works of the Sardab brand assets without explicit written permission.</p>

        <h4 data-i18n="terms.6.title">6. Privacy &amp; Data</h4>
        <p data-i18n="terms.6.body">Sardab is designed as a zero-knowledge system. We do not collect, store, or process personal data. Communications are encrypted client-side and transmitted directly between peers. Signaling data (limited to WebRTC handshake information) is relayed ephemerally and is not persisted. No message content, media streams, file contents, metadata about communications, IP addresses beyond connection duration, or any user-identifying information is stored. For complete details, refer to our Privacy Policy.</p>

        <h4 data-i18n="terms.7.title">7. Encryption &amp; Security</h4>
        <p data-i18n="terms.7.body">Messages are encrypted using AES-256-GCM with keys derived client-side via PBKDF2 with 200,000 iterations of SHA-512. Media streams are secured with DTLS-SRTP encryption. The encryption key is derived from the room code and never transmitted to any server. Sardab makes no warranty that the encryption implementation is impervious to all forms of attack. Users acknowledge that perfect security is unattainable and assume the inherent risks of digital communication.</p>

        <h4 data-i18n="terms.8.title">8. Disclaimer of Warranties</h4>
        <p data-i18n="terms.8.body">THE SERVICE IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, NON-INFRINGEMENT, OR AVAILABILITY. Sardab does not guarantee that the Service will be uninterrupted, secure, error-free, or free of harmful components. The entire risk arising out of use or performance of the Service remains with the user. Sardab is provided without any warranty regarding the accuracy, reliability, or completeness of the Service.</p>

        <h4 data-i18n="terms.9.title">9. Limitation of Liability</h4>
        <p data-i18n="terms.9.body">TO THE MAXIMUM EXTENT PERMITTED BY LAW, KumoCoders, its developers (Nacer Eddine Bouars, Sara Chihab, and Douae Manar), and contributors shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including but not limited to loss of data, loss of profits, service interruption, computer damage, or any other damages arising from the use or inability to use the Service, even if advised of the possibility of such damages. In no event shall total liability exceed the amount paid by the user for the Service, which is zero.</p>

        <h4 data-i18n="terms.10.title">10. Indemnification</h4>
        <p data-i18n="terms.10.body">You agree to indemnify, defend, and hold harmless KumoCoders, its developers, affiliates, and contributors from any claims, liabilities, damages, losses, or expenses, including reasonable legal fees, arising out of or in any way connected with your use of the Service, your violation of these Terms, or your violation of any rights of another user or third party.</p>

        <h4 data-i18n="terms.11.title">11. Termination</h4>
        <p data-i18n="terms.11.body">Sardab reserves the right to modify, suspend, or discontinue the Service, or any part thereof, at any time without notice. We may terminate or restrict your access to the Service for violation of these Terms, without liability. Upon termination, all provisions of these Terms that by their nature should survive termination shall survive, including but not limited to limitation of liability, disclaimer of warranties, and indemnification.</p>

        <h4 data-i18n="terms.12.title">12. Modifications to Terms</h4>
        <p data-i18n="terms.12.body">These Terms may be updated at any time. Changes will be posted on this page. Your continued use of the Service after any modifications constitutes acceptance of the revised Terms. It is your responsibility to review these Terms periodically. Material changes will be communicated via the Service.</p>

        <h4 data-i18n="terms.13.title">13. Governing Law &amp; Jurisdiction</h4>
        <p data-i18n="terms.13.body">These Terms shall be governed by and construed in accordance with applicable international laws. Any disputes arising from these Terms or the Service shall be resolved through binding arbitration in accordance with the rules of the relevant jurisdiction. Users agree that any legal proceedings shall be conducted in English or Arabic at the discretion of the Service.</p>

        <h4 data-i18n="terms.14.title">14. Contact Information</h4>
        <p data-i18n="terms.14.body">For questions, concerns, or legal inquiries regarding these Terms, please contact the development team: KumoCoders, represented by Nacer Eddine Bouars, Sara Chihab, and Douae Manar. The project repository and contact channels are available through the official KumoCoders website.</p>
      </div>

      <!-- PRIVACY PANE -->
      <div class="modal-pane" id="pane-privacy">
        <h4 data-i18n="privacy.1.title">1. Introduction &amp; Scope</h4>
        <p data-i18n="privacy.1.body">This Privacy Policy explains how Sardab handles information when you use the Service. Sardab is designed from the ground up as a zero-knowledge system. Our fundamental principle is that we should not — and technically cannot — access your communications. This policy describes what limited data we handle, how it is protected, and what we do not collect. By using Sardab, you acknowledge and agree to the practices described in this policy.</p>

        <h4 data-i18n="privacy.2.title">2. Information We Do NOT Collect</h4>
        <p data-i18n="privacy.2.body">Sardab is intentionally designed to collect no personal information. We do NOT collect: names, email addresses, phone numbers, physical addresses, government IDs, payment information, biometric data, device identifiers, contact lists, location data, browsing history, behavioral data, or any other personally identifiable information. We do NOT use tracking pixels, analytics scripts, fingerprinting techniques, or any form of user surveillance. No account creation means no profile data is ever stored.</p>

        <h4 data-i18n="privacy.3.title">3. Information We Handle (and How)</h4>
        <p data-i18n="privacy.3.body">The only data our servers process is ephemeral WebRTC signaling information necessary to establish peer-to-peer connections. This includes Session Description Protocol (SDP) offers and answers, ICE candidates for NAT traversal, and room code hashes. This data is transmitted in memory only and is not logged, stored, or persisted to disk. Room codes are one-way hashed with SHA-512 before reaching our server — the original code is never visible to us. Signaling data is discarded immediately after the peer connection is established or when the room session ends.</p>

        <h4 data-i18n="privacy.4.title">4. Encryption Architecture</h4>
        <p data-i18n="privacy.4.body">All communications on Sardab are encrypted by default at every layer. Text messages, files, and chat data are encrypted with AES-256-GCM symmetric encryption. The encryption key is derived client-side from the room code using PBKDF2 with 200,000 iterations of SHA-512 — this key never leaves your browser. Audio and video streams are encrypted with DTLS-SRTP, the standard WebRTC media encryption protocol. Even if a third party intercepted the signaling relay, they would only see encrypted data they cannot decrypt. Sardab has no ability to decrypt, monitor, or access the content of any user communication.</p>

        <h4 data-i18n="privacy.5.title">5. Data Retention &amp; Deletion</h4>
        <p data-i18n="privacy.5.body">Sardab does not retain any user data. Signaling information is ephemeral and exists only in server memory for the duration of the signaling process. There are no databases, log files, backups, or archives containing user communications. Room codes are hashed and used only for routing signaling messages; the hash is not stored after the room is destroyed. If you close your browser or leave a room, no record of your session exists anywhere. There is nothing to delete because there is nothing stored.</p>

        <h4 data-i18n="privacy.6.title">6. Third-Party Services</h4>
        <p data-i18n="privacy.6.body">Sardab does not integrate with any third-party analytics, advertising, tracking, or social media services. The Service does not load external scripts, embed third-party content, or send data to external servers beyond the signaling relay. STUN/TURN servers used for NAT traversal receive only the minimum network information required to establish connections (IP addresses and ports) and do not log or persist this information. Users are encouraged to review the privacy practices of any third-party browser extensions or network infrastructure they may use.</p>

        <h4 data-i18n="privacy.7.title">7. Network &amp; Connection Data</h4>
        <p data-i18n="privacy.7.body">When you use Sardab, your IP address is visible to the signaling server for the duration of the signaling handshake and to any peers you connect to directly (necessary for WebRTC peer-to-peer communication). IP addresses are not logged, stored, or associated with any user identifier. If you use a TURN relay (required for some restrictive network environments), the TURN server will see your IP address and port during the connection. We recommend using a VPN for an additional layer of network privacy if desired.</p>

        <h4 data-i18n="privacy.8.title">8. Cookies &amp; Local Storage</h4>
        <p data-i18n="privacy.8.body">Sardab does not use cookies for tracking, analytics, or advertising. The only local storage used is your language preference (sardab-lang), which is stored in the browser's localStorage and never transmitted to any server. This is used solely to remember your interface language choice between sessions. You can clear this at any time through your browser settings. No other data is stored on your device by Sardab.</p>

        <h4 data-i18n="privacy.9.title">9. Children's Privacy</h4>
        <p data-i18n="privacy.9.body">Sardab is not directed at children under the age of 13. We do not knowingly collect any information from children. If we become aware that a user under 13 is using the Service, we will terminate their access. Parents and guardians are advised to monitor their children's online activities. Users under 13 are prohibited from using Sardab under any circumstances. We encourage parents to educate their children about safe online communication practices.</p>

        <h4 data-i18n="privacy.10.title">10. Your Rights &amp; Choices</h4>
        <p data-i18n="privacy.10.body">Because Sardab collects no personal data, traditional data subject rights (access, rectification, erasure, portability) are inherently fulfilled — there is nothing to access, correct, or delete. You have the right to use the Service anonymously without providing any personal information. You have the right to stop using the Service at any time, which immediately terminates all data processing. You have the right to verify our zero-knowledge claims by inspecting the source code. Under applicable privacy laws including but not limited to GDPR and CCPA, no data processing of personal information occurs.</p>

        <h4 data-i18n="privacy.11.title">11. Security Measures</h4>
        <p data-i18n="privacy.11.body">Sardab employs industry-standard encryption protocols to protect communications. The signaling server runs with minimal privileges and no persistent storage. All server communications use HTTPS/WSS (WebSocket Secure). The source code is available for independent security audit and review. Despite these measures, no system is perfectly secure. Users are responsible for protecting their own devices, maintaining secure network connections, and keeping their room codes private. Sardab recommends using strong, unique room codes for each communication session.</p>

        <h4 data-i18n="privacy.12.title">12. International Data Transfers</h4>
        <p data-i18n="privacy.12.body">Sardab's signaling server may be located in any jurisdiction. Since no personal data is collected or stored, international data transfer regulations have no practical effect — there is no user data to transfer across borders. Peer-to-peer connections occur directly between users' devices regardless of geographic location. Sardab does not route communications through specific jurisdictions or apply geographic restrictions.</p>

        <h4 data-i18n="privacy.13.title">13. Changes to This Policy</h4>
        <p data-i18n="privacy.13.body">This Privacy Policy may be updated from time to time. Changes will be posted on this page with an updated effective date. Material changes will be communicated through the Service. Your continued use of Sardab after policy changes constitutes acceptance of the updated terms. We encourage users to review this policy periodically. The current version was last updated June 2026.</p>

        <h4 data-i18n="privacy.14.title">14. Contact &amp; Data Controller</h4>
        <p data-i18n="privacy.14.body">Sardab is developed and maintained by KumoCoders, a collective of developers focused on privacy-preserving technology. The development team consists of Nacer Eddine Bouars, Sara Chihab, and Douae Manar. For privacy-related inquiries or security disclosures, please contact the team through the official KumoCoders repository or website. As Sardab collects no personal data, there is no data controller or data protection officer — there is no data to control or protect on our end.</p>
      </div>
    </div>
    <div class="modal-footer">
      <button onclick="document.getElementById('legalModal').classList.remove('open')" data-i18n="legal.close">Close</button>
    </div>
  </div>
</div>
<script>
function switchLegalTab(name){
  document.querySelectorAll('#legalModal .modal-tab').forEach(function(t){t.classList.toggle('active',t.dataset.tab===name)});
  document.querySelectorAll('#legalModal .modal-pane').forEach(function(p){p.classList.toggle('active',p.id==='pane-'+name)});
}
</script>
<script>if('serviceWorker' in navigator)navigator.serviceWorker.register('<?=$basePath?>/sw.js').catch(function(){})</script>
</body>
</html>
