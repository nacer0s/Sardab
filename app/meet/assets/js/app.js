(function(){'use strict';
console.log('[SardabMeet] app.js v24 loaded');
const APP='meet';
const BASE=window.location.pathname.replace(/\/app\/.*$/,'');
const S={
  sid:sessionStorage.getItem('sardab-meet-sid')||Math.random().toString(36).substring(2,10),
  room:window.ROOM_CODE||'',
  name:sessionStorage.getItem('sardab-meet-name')||'',
  users:{},pcs:{},dcs:{},streams:{},localStream:null,screenStream:null,
  micOn:true,camOn:true,sharing:false,screenOn:false,inCall:false,
  peerConnected:false,isCreator:false,
  transport:null,encKey:null,
  makingOffer:{},pendingCandidates:{},ignoreOffer:{},negoDone:{},
  timer:0,timerInterval:null,msgs:[],chatOpen:false,participantsOpen:false,
  remoteScreenTracks:[],_expectingScreenTrack:{},_expectedScreenInfo:{},
  _lastRemoteVideoId:{},_lastRemoteAudioId:{},_layoutScreen:false,_maximizedTile:null,
  reactions:{},raisedHands:{},fileBufs:{},_endingCall:false,connectionStatus:{},
  gridId:'meetGrid',
  _handRaised:false,_reactionPickerOpen:false,_unreadCount:0,_setupFromSignal:null,
  speaking:{},_speakingInterval:null,_screenTrackId:null,_audioAnalysers:{}
};
const STUN={iceServers:[{urls:'stun:stun.l.google.com:19302'},{urls:'stun:stun1.l.google.com:19302'},{urls:'turn:openrelay.metered.ca:80',username:'openrelayproject',credential:'openrelayproject'}]};
const REACTIONS=['👍','❤️','😮','😂','🎉','👏'];
var audioCtx=null;

function $(id){return document.getElementById(id)}
function esc(s){
  if(typeof s!=='string')return '';
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function waitForIceGathering(pc){
  return new Promise(function(resolve){
    if(pc.iceGatheringState==='complete'){resolve();return}
    var check=function(){if(pc.iceGatheringState==='complete'){pc.removeEventListener('icegatheringstatechange',check);resolve()}};
    pc.addEventListener('icegatheringstatechange',check);
    setTimeout(function(){pc.removeEventListener('icegatheringstatechange',check);resolve()},3000);
  });
}

function showToast(msg){
  var t=$('toast');
  if(!t)return;
  t.textContent=msg;t.classList.add('visible');
  if(t._hide)clearTimeout(t._hide);
  t._hide=setTimeout(function(){t.classList.remove('visible')},3000);
}

function playSound(type){
  try{
    var AC=window.AudioContext||window.webkitAudioContext;
    if(!AC)return;
    if(!audioCtx)audioCtx=new AC();
    if(audioCtx.state==='suspended')audioCtx.resume();
    var osc=audioCtx.createOscillator(),gain=audioCtx.createGain();
    osc.connect(gain);gain.connect(audioCtx.destination);
    var now=audioCtx.currentTime;
    if(type==='join'){osc.frequency.setValueAtTime(600,now);osc.frequency.setValueAtTime(800,now+0.1);gain.gain.setValueAtTime(0.15,now);gain.gain.exponentialRampToValueAtTime(0.001,now+0.2);osc.start(now);osc.stop(now+0.2)}
    else if(type==='leave'){osc.frequency.setValueAtTime(800,now);osc.frequency.setValueAtTime(600,now+0.1);gain.gain.setValueAtTime(0.15,now);gain.gain.exponentialRampToValueAtTime(0.001,now+0.2);osc.start(now);osc.stop(now+0.2)}
    else if(type==='message'){osc.frequency.setValueAtTime(1000,now);gain.gain.setValueAtTime(0.08,now);gain.gain.exponentialRampToValueAtTime(0.001,now+0.08);osc.start(now);osc.stop(now+0.08)}
    else if(type==='reaction'){osc.frequency.setValueAtTime(1200,now);osc.frequency.exponentialRampToValueAtTime(600,now+0.15);gain.gain.setValueAtTime(0.1,now);gain.gain.exponentialRampToValueAtTime(0.001,now+0.15);osc.start(now);osc.stop(now+0.15)}
    else if(type==='hand'){osc.type='square';osc.frequency.setValueAtTime(440,now);osc.frequency.setValueAtTime(660,now+0.12);gain.gain.setValueAtTime(0.06,now);gain.gain.exponentialRampToValueAtTime(0.001,now+0.2);osc.start(now);osc.stop(now+0.2)}
  }catch(e){}
}

function toggleFullscreen(){
  if(!document.fullscreenElement){document.documentElement.requestFullscreen()['catch'](function(){})}
  else{document.exitFullscreen()['catch'](function(){})}
}

function relTime(ts){
  var diff=Date.now()-ts;
  if(diff<60000)return'just now';
  var m=Math.floor(diff/60000);
  if(m<60)return m+'m ago';
  var h=Math.floor(m/60);
  if(h<24)return h+'h ago';
  var d=Math.floor(h/24);
  return d+'d ago';
}

function showReaction(emoji){
  var overlay=$('reactionsOverlay');
  if(!overlay)return;
  var el=document.createElement('div');el.className='reaction-pop';el.textContent=emoji;
  el.style.left=(Math.random()*60+20)+'%';
  el.style.top=(Math.random()*30+35)+'%';
  overlay.appendChild(el);
  setTimeout(function(){el.remove()},1500);
}

function clearMaximize(){
  if(!S._maximizedTile)return;
  var grid=$(S.gridId);
  if(grid){grid.classList.remove('has-maximized');S._maximizedTile.classList.remove('maximized')}
  S._maximizedTile=null;
}

function toggleMaximize(tile){
  if(!tile)return;
  if(tile.classList.contains('maximized')){clearMaximize();return}
  clearMaximize();
  var grid=$(S.gridId);
  if(!grid)return;
  S._maximizedTile=tile;tile.classList.add('maximized');grid.classList.add('has-maximized');
}

function updateScreenLayout(){
  clearMaximize();
  var grid=$(S.gridId);
  if(!grid)return;
  var hasScreen=S.sharing||S.remoteScreenTracks.length>0;
  if(hasScreen===S._layoutScreen){
    if(hasScreen){
      var ma=$('screenMainArea'),bb=$('bottomBar');
      [].slice.call(grid.children).forEach(function(t){
        if(t.id==='screenMainArea'||t.id==='bottomBar')return;
        var target=t.getAttribute('data-screen')==='true'?ma:bb;
        if(target&&t.parentNode===grid)target.appendChild(t);
      });
    }
    return;
  }
  S._layoutScreen=hasScreen;
  if(hasScreen){
    grid.classList.add('screen-active');
    var ma=document.createElement('div');ma.className='screen-main-area';ma.id='screenMainArea';
    var bb=document.createElement('div');bb.className='bottom-bar';bb.id='bottomBar';
    var kids=[].slice.call(grid.children);
    kids.forEach(function(t){
      if(t.getAttribute('data-screen')==='true')ma.appendChild(t);else bb.appendChild(t)
    });
    if(ma.children.length>0)grid.appendChild(ma);
    if(bb.children.length>0)grid.appendChild(bb);
  }else{
    grid.classList.remove('screen-active');
    var ma=$('screenMainArea'),bb=$('bottomBar');
    [].concat(ma?[].slice.call(ma.children):[],bb?[].slice.call(bb.children):[]).forEach(function(t){grid.appendChild(t)});
    if(ma)ma.remove();if(bb)bb.remove();
  }
}

function createTile(stream,sid,name,isSelf){
  var tile=document.createElement('div');tile.className='video-tile';tile.dataset.sid=sid;
  if(isSelf){
    tile.id='selfTile';
    var vid=$('selfVideo'),ph=$('selfPlaceholder');
    if(vid&&ph){
      ph.style.display=S.camOn?'none':'flex';
      if(stream&&S.camOn){vid.srcObject=stream;vid.style.display=''}else vid.style.display='none'
    }
    var lbl=document.createElement('div');lbl.className='tile-label';
    lbl.innerHTML='<span class="name">'+esc(name||'You')+'</span><span class="mic-icon" id="selfMicIcon">'+(S.micOn?'<i class="fa-solid fa-microphone"></i>':'<i class="fa-solid fa-microphone-slash" style="color:#ff6b6b"></i>')+'</span>';
    tile.appendChild(lbl);
    return tile;
  }
  var hasVideo=stream&&stream.getVideoTracks().length>0&&(sid===S.sid?S.camOn:S.users[sid]?S.users[sid].camOn!==false:true);
  if(hasVideo){
    var vidEl=document.createElement('video');
    vidEl.srcObject=stream;vidEl.autoplay=true;vidEl.playsinline=true;vidEl.className='video-el';
    tile.appendChild(vidEl);
  }
  var av=document.createElement('div');av.className='avatar-placeholder';av.style.display=hasVideo?'none':'flex';
  av.innerHTML='<i class="fa-solid fa-video-slash"></i><span class="avatar-letter">'+esc(name.charAt(0).toUpperCase()||'?')+'</span><span class="avatar-label">'+esc(name)+'</span>';
  tile.appendChild(av);
  var lbl=document.createElement('div');lbl.className='tile-label';
  var micOn=name!==S.name?S.users[sid]?S.users[sid].micOn!==false:true:S.micOn;
  lbl.innerHTML='<span class="name">'+esc(name)+'</span><span class="mic-icon'+(micOn?'':' muted')+'"><i class="fa-solid fa-'+(micOn?'microphone':'microphone-slash')+'"></i></span>';
  tile.appendChild(lbl);
  if(S.raisedHands[sid]){
    var badge=document.createElement('div');badge.className='tile-badge';
    badge.innerHTML='<span class="badge-icon hand"><i class="fa-solid fa-hand"></i></span>';
    tile.appendChild(badge);
  }
  if(S.speaking[sid])tile.classList.add('speaking');
  return tile;
}

function getAllTiles(root){
  var tiles=[];
  [].slice.call(root.children).forEach(function(t){
    if(t.classList&&t.classList.contains('video-tile'))tiles.push(t);
    if(t.children&&t.children.length)tiles=tiles.concat(getAllTiles(t));
  });
  return tiles;
}

function renderGrid(){
  var grid=$(S.gridId);if(!grid)return;
  // Self tile — always update regardless of parent
  var st=$('selfTile');
  if(st){
    var mi=st.querySelector('#selfMicIcon');
    if(mi)mi.innerHTML=S.micOn?'<i class="fa-solid fa-microphone"></i>':'<i class="fa-solid fa-microphone-slash" style="color:#ff6b6b"></i>';
    var sv=$('selfVideo'),sp=$('selfPlaceholder');
    if(sv)sv.style.display=(S.localStream&&S.camOn)?'':'none';
    if(sp)sp.style.display=(!S.localStream||!S.camOn)?'flex':'none';
    st.classList.toggle('speaking',!!S.speaking[S.sid]);
  }else{
    var t0=createTile(S.localStream,S.sid,S.name||'You',true);
    if(t0)grid.insertBefore(t0,grid.firstChild);
  }
  var sa=$('selfAvatar');if(sa)sa.textContent=(S.name||'Y').charAt(0).toUpperCase();
  var sn=$('selfName');if(sn)sn.textContent=S.name||'You';
  var snl=$('selfNameLabel');if(snl)snl.textContent=S.name||'You';
  // Self screen tile
  if(S.sharing&&S.screenStream){
    var ssTile=grid.querySelector('[data-sid="'+S.sid+'"][data-screen="true"]');
    if(!ssTile){
      ssTile=document.createElement('div');ssTile.className='video-tile';ssTile.setAttribute('data-screen','true');ssTile.dataset.sid=S.sid;
      var ssVideo=document.createElement('video');ssVideo.autoplay=true;ssVideo.playsinline=true;ssVideo.muted=true;ssVideo.className='video-el screen-el';
      ssVideo.srcObject=S.screenStream;ssTile.appendChild(ssVideo);
      var sslbl=document.createElement('div');sslbl.className='tile-label';
      sslbl.innerHTML='<span class="name">'+esc(S.name||'You')+"'s Screen</span>";
      ssTile.appendChild(sslbl);
      var target=S._layoutScreen?$('screenMainArea')||grid:grid;
      target.appendChild(ssTile);
      updateScreenLayout();
    }
  }else{
    var ssTile=grid.querySelector('[data-sid="'+S.sid+'"][data-screen="true"]');
    if(ssTile)ssTile.remove();
  }
  // Collect existing remote non-screen tiles
  var existing={};
  getAllTiles(grid).forEach(function(t){
    if(t.dataset.sid&&t.dataset.sid!==S.sid&&t.getAttribute('data-screen')!=='true')existing[t.dataset.sid]=t;
  });
  // Update or create remote tiles
  Object.keys(S.users).forEach(function(sid){
    if(sid===S.sid)return;
    var user=S.users[sid],name=user?user.name:sid,stream=S.streams[sid];
    if(!stream)return;
    var tile=existing[sid];
    if(tile){
      var hasVideo=stream.getVideoTracks().length>0&&(user?user.camOn!==false:true);
      var ve=tile.querySelector('video');
      if(ve){if(ve.srcObject!==stream)ve.srcObject=stream;ve.style.display=hasVideo?'':'none'}else if(hasVideo){ve=document.createElement('video');ve.srcObject=stream;ve.autoplay=true;ve.playsinline=true;ve.className='video-el';tile.insertBefore(ve,tile.firstChild)}
      var ap=tile.querySelector('.avatar-placeholder');
      if(ap)ap.style.display=hasVideo?'none':'flex';
      var ln=tile.querySelector('.tile-label .name');
      if(ln)ln.textContent=name;
      var me=tile.querySelector('.tile-label .mic-icon');
      var mon=user?user.micOn!==false:true;
      if(me){me.className='mic-icon'+(mon?'':' muted');me.innerHTML='<i class="fa-solid fa-'+(mon?'microphone':'microphone-slash')+'"></i>'}
      tile.classList.toggle('speaking',!!S.speaking[sid]);
      if(S.raisedHands[sid]){if(!tile.querySelector('.badge-icon.hand')){var b=tile.querySelector('.tile-badge');if(!b){b=document.createElement('div');b.className='tile-badge';tile.appendChild(b)}b.innerHTML='<span class="badge-icon hand"><i class="fa-solid fa-hand"></i></span>'}}
      else{var hb=tile.querySelector('.badge-icon.hand');if(hb)hb.parentElement.remove()}
    }else{
      var t2=createTile(stream,sid,name,false);
      if(t2){
        var bb=S._layoutScreen?$('bottomBar'):null;
        if(bb)bb.appendChild(t2);else grid.appendChild(t2);
      }
    }
  });
  // Remove tiles for users who left
  var all=getAllTiles(grid);
  all.forEach(function(t){
    if(t.id==='selfTile'||t.getAttribute('data-screen')==='true')return;
    var s=t.dataset.sid;
    if(s&&s!==S.sid&&!S.streams[s])t.remove();
  });
  // Orphaned screen tiles
  all.forEach(function(t){
    if(t.id==='selfTile'||t.dataset.sid===S.sid)return;
    if(t.getAttribute('data-screen')==='true'&&t.dataset.sid){
      var found=false;
      for(var i=0;i<S.remoteScreenTracks.length;i++){if(S.remoteScreenTracks[i].track&&t.dataset.sid===S.remoteScreenTracks[i].sid){found=true;break}}
      if(!found)t.remove();
    }
  });
  // Re-apply maximized state
  if(S._maximizedTile&&S._maximizedTile.parentNode){}else{S._maximizedTile=null;if(grid)grid.classList.remove('has-maximized')}
  // Toggle single class for solo display
  var pc=Object.keys(S.users).length;
  grid.classList.toggle('single',pc===0);
}

// ---------- DataChannel ----------

function dcSendRaw(dc,plain,encrypted){
  try{
    if(encrypted)dc.send(encrypted);
    else dc.send(plain);
  }catch(e){
    try{dc.send(plain)}catch(e2){}
  }
}

function setupDC(sid,dc){
  dc.binaryType='arraybuffer';
  dc.onmessage=async function(e){
    try{
      var decrypted;
      try{
        if(S.encKey)decrypted=await Crypto.decrypt(S.encKey,e.data);
        else decrypted=e.data;
      }catch(cryptoErr){decrypted=e.data}
      if(typeof decrypted==='string'){try{decrypted=JSON.parse(decrypted)}catch(pe){return}}
      var data=decrypted;
      if(typeof data!=='object'||!data.type)return;
      if(data.type==='chat'){
        S.msgs.push(data);
        if(data.sid!==S.sid&&!S.chatOpen){S._unreadCount++;updateUI()}
        playSound('message');renderChat()
      }
      else if(data.type==='file'){handleFileChunk(data);return}
      else if(data.type==='file_meta'){S.msgs.push(data);if(!S.chatOpen){S._unreadCount++;updateUI()}renderChat()}
      else if(data.type==='file_ready'){S.msgs.push(data);renderChat()}
      else if(data.type==='screen_state'){
        if(data.action==='start'){
          S._expectingScreenTrack[data.sender]=true;
          S._expectedScreenInfo[data.sender]={name:data.name};
        }else if(data.action==='stop'){
          S._expectingScreenTrack[data.sender]=false;
          var idx=-1;
          for(var i=0;i<S.remoteScreenTracks.length;i++){
            if(S.remoteScreenTracks[i].sid===data.sender){idx=i;break}
          }
          if(idx>=0){
            var entry=S.remoteScreenTracks[idx];
            if(entry.track){entry.track.onended=null}
            if(entry.tile&&entry.tile.parentNode)entry.tile.parentNode.removeChild(entry.tile);
            if(entry.video)entry.video.srcObject=null;
            S.remoteScreenTracks.splice(idx,1);
          }
          updateUI();updateScreenLayout();renderGrid();
        }
      }
      else if(data.type==='reaction'){playSound('reaction');showReaction(data.emoji)}
      else if(data.type==='hand'){
        if(data.raised){S.raisedHands[data.sender]=true;playSound('hand')}
        else delete S.raisedHands[data.sender];
        renderGrid();renderParticipants();
      }
      else if(data.type==='status'){
        if(!S.users[data.sender])S.users[data.sender]={};
        S.users[data.sender].micOn=data.micOn;
        S.users[data.sender].camOn=data.camOn;
        renderGrid();
      }
    }catch(err){}
  };
  dc.onopen=function(){
    var ss=JSON.stringify({type:'status',micOn:S.micOn,camOn:S.camOn,sender:S.sid});
    if(S.encKey)Crypto.encrypt(S.encKey,ss).then(function(e){dcSendRaw(dc,ss,e)}).catch(function(){dcSendRaw(dc,ss)});
    else dcSendRaw(dc,ss);
    if(S._handRaised){
      var hs=JSON.stringify({type:'hand',raised:true,sender:S.sid});
      if(S.encKey)Crypto.encrypt(S.encKey,hs).then(function(e){dcSendRaw(dc,hs,e)}).catch(function(){dcSendRaw(dc,hs)});
      else dcSendRaw(dc,hs);
    }
    if(S.sharing){
      var sc=JSON.stringify({type:'screen_state',action:'start',sender:S.sid,name:S.name});
      if(S.encKey)Crypto.encrypt(S.encKey,sc).then(function(e){dcSendRaw(dc,sc,e)}).catch(function(){dcSendRaw(dc,sc)});
      else dcSendRaw(dc,sc);
    }
  };
}

// ---------- PeerConnection ----------

function setupPC(sid){
  try{if(S.pcs[sid]){S.pcs[sid].close();delete S.pcs[sid]}}catch(e){}
  var pc=new RTCPeerConnection(STUN);
  S.pcs[sid]=pc;
  S.makingOffer[sid]=!!S._setupFromSignal;
  S.ignoreOffer[sid]=false;
  S.pendingCandidates[sid]=[];
  S.negoDone[sid]=false;
  S.connectionStatus[sid]='connecting';
  console.log('[SardabMeet] setupPC sid='+sid+' fromSignal='+!!S._setupFromSignal+' make='+S.makingOffer[sid]);
  if(S.localStream)S.localStream.getTracks().forEach(function(t){pc.addTrack(t,S.localStream)});
  if(S.screenStream&&S.sharing)S.screenStream.getTracks().forEach(function(t){try{pc.addTrack(t,S.screenStream)}catch(e){}});
  var dc=pc.createDataChannel('meet-'+sid,{ordered:true});
  S.dcs[sid]=dc;setupDC(sid,dc);
  pc.ondatachannel=function(e){
    if(!S.dcs[sid]||S.dcs[sid].readyState!=='open'){S.dcs[sid]=e.channel;setupDC(sid,e.channel)}
  };
  pc.onicecandidate=function(e){
    if(e.candidate&&S.transport)S.transport.sendSignal({type:'candidate',candidate:e.candidate.toJSON(),from:S.sid,target:sid})
  };
  pc.ontrack=function(e){
    console.log('[SardabMeet] ontrack sid='+sid+' kind='+e.track.kind+' id='+e.track.id);
    var stream=e.streams&&e.streams[0]?e.streams[0]:new MediaStream([e.track]);
    if(e.track.kind==='audio'){
      // Guard against duplicate audio tracks from stale renegotiation
      if(e.track.id===S._lastRemoteAudioId[sid])return;
      S._lastRemoteAudioId[sid]=e.track.id;
      try{
        var AC=window.AudioContext||window.webkitAudioContext;
        if(AC){
          if(!audioCtx)audioCtx=new AC();
          if(audioCtx.state==='suspended')audioCtx.resume();
          if(!audioCtx._gain){audioCtx._gain=audioCtx.createGain();audioCtx._gain.connect(audioCtx.destination)}
          var src=audioCtx.createMediaStreamSource(stream);
          src.connect(audioCtx._gain);
          audioCtx._gain.gain.value=1;
          var analyser=audioCtx.createAnalyser();analyser.fftSize=256;
          var src2=audioCtx.createMediaStreamSource(stream);
          src2.connect(analyser);
          S._audioAnalysers[sid]={analyser:analyser,dataArray:new Uint8Array(analyser.frequencyBinCount)};
        }
      }catch(ex){}
      // Fallback: route to peerAudio when AudioContext unavailable
      if(!audioCtx){var el=$('peerAudio');if(el)el.srcObject=stream}
      if(!S.streams[sid]){S.streams[sid]=stream;renderGrid()}
    }else if(e.track.kind==='video'){
      if(e.track.id===S._lastRemoteVideoId[sid])return;
      S._lastRemoteVideoId[sid]=e.track.id;
      var vs=(e.streams&&e.streams[0])||new MediaStream([e.track]);
      var isScreen=S._expectingScreenTrack[sid];
      S._expectingScreenTrack[sid]=false;
      var screenInfo=S._expectedScreenInfo[sid];
      S._expectedScreenInfo[sid]=null;
      if(!isScreen){
        var lbl=(e.track.label||'').toLowerCase();
        if(lbl.indexOf('screen')>=0||lbl.indexOf('display')>=0||lbl.indexOf('window')>=0)isScreen=true;
      }
      if(isScreen){
        for(var k=0;k<S.remoteScreenTracks.length;k++){if(S.remoteScreenTracks[k].track.id===e.track.id)return}
        var sname=screenInfo?screenInfo.name:'';
        if(!sname){var peer=S.users[sid];if(peer)sname=peer.name}
        var tile=document.createElement('div');tile.className='video-tile';tile.setAttribute('data-screen','true');
        tile.dataset.sid=sid;
        var video=document.createElement('video');video.autoplay=true;video.playsinline=true;video.className='video-el screen-el';
        video.srcObject=vs;tile.appendChild(video);
        var lblDiv=document.createElement('div');lblDiv.className='tile-label';
        var nameSpan=document.createElement('span');nameSpan.className='name';
        nameSpan.textContent=sname?(sname+"'s Screen"):'Screen Share';
        lblDiv.appendChild(nameSpan);tile.appendChild(lblDiv);
        var entry={sid:sid,track:e.track,tile:tile,video:video,sname:sname};
        S.remoteScreenTracks.push(entry);
        var grid=$(S.gridId);if(grid)grid.appendChild(tile);
        updateScreenLayout();renderGrid();
        e.track.onended=function(){
          if(tile.parentNode)tile.parentNode.removeChild(tile);
          video.srcObject=null;
          for(var k=0;k<S.remoteScreenTracks.length;k++){if(S.remoteScreenTracks[k].track===e.track){S.remoteScreenTracks.splice(k,1);break}}
          updateUI();updateScreenLayout();renderGrid();
        };
      }else{
        if(S.streams[sid]){
          var existing=S.streams[sid].getVideoTracks()[0];
          if(existing&&existing.readyState!=='ended'){
            // Keep existing — prevents black blink from stale renegotiation
          }else{
            if(existing)S.streams[sid].removeTrack(existing);
            S.streams[sid].addTrack(e.track);
          }
        }else{S.streams[sid]=vs}
        renderGrid();
      }
    }
  };
  pc.onnegotiationneeded=async function(){
    try{
      var _make=S.makingOffer[sid],_ign=S.ignoreOffer[sid],_done=S.negoDone[sid];
      var _cst=S.connectionStatus[sid];
      console.log('[SardabMeet] negoNeeded check sid="'+sid+'" make='+JSON.stringify(_make)+' ign='+JSON.stringify(_ign)+' done='+JSON.stringify(_done)+' cst='+JSON.stringify(_cst)+' sig='+pc.signalingState+' conn='+pc.connectionState);
      console.log('[SardabMeet] negoNeeded makeKeys='+JSON.stringify(Object.keys(S.makingOffer))+' doneKeys='+JSON.stringify(Object.keys(S.negoDone)));
      if(_make||_ign||_done||_cst==='connected'){console.log('[SardabMeet] negoNeeded BLOCKED sid='+sid);return}
      console.log('[SardabMeet] negoNeeded PASSES guard sid='+sid);
      S.makingOffer[sid]=true;
      var offer=await pc.createOffer({iceRestart:false});
      if(S.makingOffer[sid]!==true){console.log('[SardabMeet] negoNeeded abort glare sid='+sid);S.makingOffer[sid]=false;return}
      if(S.connectionStatus[sid]==='connected'){console.log('[SardabMeet] negoNeeded abort connected sid='+sid);S.makingOffer[sid]=false;return}
      await pc.setLocalDescription(offer);
      await waitForIceGathering(pc);
      if(pc.connectionState==='connected'){console.log('[SardabMeet] negoNeeded FINALBLOCK connected sid='+sid);S.makingOffer[sid]='done';S.negoDone[sid]=true;return}
      if(!S.ignoreOffer[sid]&&pc.signalingState==='have-local-offer'&&S.transport){console.log('[SardabMeet] negoNeeded SENDING offer sid='+sid);S.transport.sendSignal({type:'offer',sdp:pc.localDescription.sdp,from:S.sid,target:sid})}
    }catch(e){
      console.warn('[SardabMeet] negoNeeded err sid='+sid,e);
      if(!S.ignoreOffer[sid]){S.makingOffer[sid]=false;S.ignoreOffer[sid]=false}
    }
  };
  pc.onconnectionstatechange=function(){
    var prev=S.connectionStatus[sid];
    console.log('[SardabMeet] pc['+sid+'] state: '+pc.connectionState+' (was: '+prev+')');
    S.connectionStatus[sid]=pc.connectionState;
    if(pc.connectionState==='connected'){
      if(S.negoDone[sid]){S.makingOffer[sid]='done';S.ignoreOffer[sid]=false}
      if(!S.timerInterval&&S.inCall)startTimer();
      updateUI();updateScreenLayout();renderGrid();renderParticipants();
    }else if(pc.connectionState==='disconnected'||pc.connectionState==='failed'){
      if(prev==='connected'||prev==='connecting'){
        console.log('[SardabMeet] pc['+sid+'] lost, ending peer');
        endPeer(sid);
      }
    }
  };
  pc.onsignalingstatechange=function(){
    console.log('[SardabMeet] pc['+sid+'] signaling: '+pc.signalingState);
  };
}

function endPeer(sid){
  try{if(S.pcs[sid])S.pcs[sid].close()}catch(e){}
  delete S.pcs[sid];delete S.dcs[sid];delete S.makingOffer[sid];delete S.ignoreOffer[sid];delete S.pendingCandidates[sid];delete S.negoDone[sid];
  delete S._lastRemoteVideoId[sid];delete S._lastRemoteAudioId[sid];
  delete S._expectingScreenTrack[sid];delete S._expectedScreenInfo[sid];delete S.connectionStatus[sid];delete S.speaking[sid];delete S._audioAnalysers[sid];
  if(S.streams[sid]){S.streams[sid].getTracks().forEach(function(t){t.stop()});delete S.streams[sid]}
  delete S.users[sid];delete S.raisedHands[sid];
  for(var i=S.remoteScreenTracks.length-1;i>=0;i--){
    if(S.remoteScreenTracks[i].sid===sid){
      var e=S.remoteScreenTracks[i];
      if(e.tile&&e.tile.parentNode)e.tile.parentNode.removeChild(e.tile);
      if(e.video)e.video.srcObject=null;
      S.remoteScreenTracks.splice(i,1);
    }
  }
  updateUI();updateScreenLayout();renderGrid();renderParticipants();
}

// ---------- Signaling ----------

async function handleSignal(sig){
  if(sig.t==='join'){
    if(sig.f!==S.sid&&!S.pcs[sig.f]&&S.inCall)setupPC(sig.f);
    if(!S.users[sig.f])S.users[sig.f]={sid:sig.f,name:(sig.d&&sig.d.name)||'User',micOn:true,camOn:true};
    if(S.sharing&&sig.f!==S.sid)setTimeout(function(){broadcast('screen_state',{action:'start',sender:S.sid,name:S.name})},1000);
    if(sig.f!==S.sid){playSound('join');var nm=(sig.d&&sig.d.name)||'User';showToast(nm+' joined')}
    updateUI();renderGrid();renderParticipants();
  }else if(sig.t==='leave'){
    var leftName=(S.users[sig.f]&&S.users[sig.f].name)||'User';
    showToast(leftName+' left');
    playSound('leave');endPeer(sig.f)
  }
  else if(sig.t==='signal'&&sig.d){
    var d=sig.d;
    if(d.target===S.sid||d.target==='broadcast'||!d.target){
      var from=sig.f;
      if(from===S.sid)return;
      if(d.type==='offer'){
        if(!S.pcs[from]){
          S._setupFromSignal=from;
          setupPC(from);
          S._setupFromSignal=null;
        }
        var pc=S.pcs[from];if(!pc)return;
        if(S.makingOffer[from]===true){
          if(S.sid>from){
            return; // impolite — insist on our offer
          }else{
            S.ignoreOffer[from]=true; // polite — accept remote offer
          }
        }
        if(pc.signalingState!=='stable'){
          try{await pc.setLocalDescription({type:'rollback'})}catch(e){}
        }
        console.log('[SardabMeet] offerHandler set negoDone BEFORE sRD sid='+from+' state='+pc.signalingState);
        S.negoDone[from]=true; // set BEFORE setRemoteDescription to block synchronous onnegotiationneeded
        try{
          await pc.setRemoteDescription(new RTCSessionDescription({type:d.type,sdp:d.sdp}));
          S.pendingCandidates[from].forEach(function(c){try{pc.addIceCandidate(c)}catch(e){}});
          S.pendingCandidates[from]=[];
          await pc.setLocalDescription(await pc.createAnswer());
          await waitForIceGathering(pc);
          if(S.transport)S.transport.sendSignal({type:'answer',sdp:pc.localDescription.sdp,from:S.sid,target:from});
        }catch(e){console.warn('[SardabMeet] offer err',e)}
        S.makingOffer[from]='done';S.ignoreOffer[from]=false;S.negoDone[from]=true;
        console.log('[SardabMeet] offerHandler done sid='+from+' make='+S.makingOffer[from]+' done='+S.negoDone[from]);
      }else if(d.type==='answer'){
        var pc=S.pcs[from];
        if(pc&&pc.signalingState==='have-local-offer'){
          console.log('[SardabMeet] answerHandler set negoDone BEFORE sRD sid='+from+' state='+pc.signalingState);
          S.negoDone[from]=true; // set BEFORE setRemoteDescription to block synchronous onnegotiationneeded
          try{
            await pc.setRemoteDescription(new RTCSessionDescription({type:d.type,sdp:d.sdp}));
            S.pendingCandidates[from].forEach(function(c){try{pc.addIceCandidate(c)}catch(e){}});
            S.pendingCandidates[from]=[];
          }catch(e){}
        }
        S.makingOffer[from]='done';S.ignoreOffer[from]=false;S.negoDone[from]=true;
        console.log('[SardabMeet] answerHandler done sid='+from+' make='+S.makingOffer[from]+' done='+S.negoDone[from]);
      }else if(d.type==='candidate'){
        var pc=S.pcs[from];
        if(pc&&pc.remoteDescription){try{pc.addIceCandidate(new RTCIceCandidate(d.candidate))}catch(e){}}
        else if(pc){S.pendingCandidates[from].push(new RTCIceCandidate(d.candidate))}
      }
    }
  }
}

function handleUsers(users){
  if(!users)return;
  users.forEach(function(u){if(!S.users[u.sid])S.users[u.sid]={sid:u.sid,name:u.name||'User',micOn:true,camOn:true}});
  Object.keys(S.users).forEach(function(sid){
    if(sid===S.sid)return;
    var found=users.some(function(u){return u.sid===sid});
    if(!found)endPeer(sid);
  });
  updateUI();renderGrid();renderParticipants();
}

// ---------- Broadcast ----------

function broadcast(type,data){
  if(!data)data={};
  data.type=type;
  var msg=JSON.stringify(data);
  Object.keys(S.dcs).forEach(function(sid){
    var dc=S.dcs[sid];
    if(dc.readyState==='open'){
      if(S.encKey){
        Crypto.encrypt(S.encKey,msg).then(function(enc){try{dc.send(enc)}catch(e){console.warn('[SardabMeet] dc send err',e)}}).catch(function(e){console.warn('[SardabMeet] encrypt err',e);try{dc.send(msg)}catch(e2){}});
      }else{
        try{dc.send(msg)}catch(e){}
      }
    }
  });
}

// ---------- UI ----------

function updateUI(){
  var bt=$('btnMic');
  if(bt){bt.innerHTML=S.micOn?'<i class="fa-solid fa-microphone"></i>':'<i class="fa-solid fa-microphone-slash"></i>';bt.classList.toggle('off',!S.micOn)}
  var cb=$('btnCam');
  if(cb){cb.innerHTML=S.camOn?'<i class="fa-solid fa-video"></i>':'<i class="fa-solid fa-video-slash"></i>';cb.classList.toggle('off',!S.camOn)}
  var st=$('btnShare');
  if(st)st.classList.toggle('share-active',S.sharing);
  var mt=$('micToggle');
  if(mt){mt.innerHTML=S.micOn?'<i class="fa-solid fa-microphone"></i>':'<i class="fa-solid fa-microphone-slash"></i>';mt.classList.toggle('off',!S.micOn)}
  var ct=$('camToggle');
  if(ct){ct.innerHTML=S.camOn?'<i class="fa-solid fa-video"></i>':'<i class="fa-solid fa-video-slash"></i>';ct.classList.toggle('off',!S.camOn)}
  var sct=$('screenToggle');
  if(sct)sct.classList.toggle('active',S.sharing);
  var hb=$('btnHand');
  if(hb)hb.classList.toggle('hand-raised',!!S._handRaised);
  var eb=$('e2eeBadge');
  if(eb)eb.classList.toggle('active',!!S.encKey);
  var fb=$('btnFullscreen');
  if(fb){var isFs=!!document.fullscreenElement;fb.innerHTML=isFs?'<i class="fa-solid fa-compress"></i>':'<i class="fa-solid fa-expand"></i>';fb.classList.toggle('active',isFs)}
  var ub=$('unreadBadge');
  if(ub){ub.textContent=S._unreadCount>9?'9+':S._unreadCount;ub.style.display=S._unreadCount>0?'flex':'none'}
  var ub2=$('unreadBadge2');
  if(ub2){ub2.textContent=S._unreadCount>9?'9+':S._unreadCount;ub2.style.display=S._unreadCount>0?'flex':'none'}
  var m=Math.floor(S.timer/60),s=S.timer%60;
  var tr=$('timer');if(tr)tr.textContent=String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');
}

function startTimer(){
  if(S.timerInterval)return;
  S.timer=0;
  S.timerInterval=setInterval(function(){if(S.inCall){S.timer++;updateUI()}},1000);
}

function toggleMic(){
  S.micOn=!S.micOn;
  if(S.localStream)S.localStream.getAudioTracks().forEach(function(t){t.enabled=S.micOn});
  updateUI();renderGrid();
  broadcast('status',{micOn:S.micOn,camOn:S.camOn,sender:S.sid});
}

function toggleCam(){
  S.camOn=!S.camOn;
  if(S.localStream)S.localStream.getVideoTracks().forEach(function(t){t.enabled=S.camOn});
  updateUI();renderGrid();
  broadcast('status',{micOn:S.micOn,camOn:S.camOn,sender:S.sid});
}

async function toggleShare(){
  Object.keys(S.pcs).forEach(function(sid){
    if(S.makingOffer[sid]==='done')S.makingOffer[sid]=false;
    S.ignoreOffer[sid]=false;S.negoDone[sid]=false;S.connectionStatus[sid]=''
  });
  if(S.sharing){
    S.sharing=false;S.screenOn=false;
    if(S._screenTrackId){
      Object.keys(S.pcs).forEach(function(sid){
        var pc=S.pcs[sid];if(!pc)return;
        var senders=pc.getSenders();
        for(var i=senders.length-1;i>=0;i--){
          var s=senders[i];
          if(s.track&&s.track.id===S._screenTrackId)try{pc.removeTrack(s)}catch(e){}
        }
      });
      S._screenTrackId=null;
    }
    if(S.screenStream){S.screenStream.getTracks().forEach(function(t){t.stop()});S.screenStream=null}
    broadcast('screen_state',{action:'stop',sender:S.sid,name:S.name});
    updateUI();updateScreenLayout();renderGrid();
    return;
  }
  try{
    S.screenStream=await navigator.mediaDevices.getDisplayMedia({video:{frameRate:15,cursor:'always'},audio:false});
    var screenTrack=S.screenStream.getVideoTracks()[0];
    if(!screenTrack){S.screenStream=null;return}
    S.sharing=true;S.screenOn=true;
    S._screenTrackId=screenTrack.id;
    Object.keys(S.pcs).forEach(function(sid){
      var pc=S.pcs[sid];if(!pc)return;
      try{pc.addTrack(screenTrack,S.screenStream)}catch(e){}
    });
    screenTrack.onended=function(){if(S.sharing)toggleShare()};
    broadcast('screen_state',{action:'start',sender:S.sid,name:S.name});
    updateUI();updateScreenLayout();renderGrid();
  }catch(e){
    if(e.name==='NotAllowedError')showToast('Screen sharing was cancelled.');
    else if(e.name==='NotSupportedError')showToast('Screen sharing not available.');
    else showToast('Screen sharing failed.');
    S.screenStream=null
  }
}

function toggleHand(){
  S._handRaised=!S._handRaised;
  updateUI();
  broadcast('hand',{raised:S._handRaised,sender:S.sid});
  if(S._handRaised)showToast('You raised hand');
}

function sendReaction(emoji){
  showReaction(emoji);
  broadcast('reaction',{emoji:emoji,sender:S.sid});
  S._reactionPickerOpen=false;
  var rp=$('reactionPicker');if(rp)rp.style.display='none';
}

function handleFileChunk(msg){
  if(!S.fileBufs[msg.id]){
    S.fileBufs[msg.id]={chunks:[],total:msg.total,name:msg.name,fileType:msg.fileType||'',sender:msg.sender,time:msg.time};
  }
  S.fileBufs[msg.id].chunks[msg.idx]=msg.chunk;
  var pending=S.fileBufs[msg.id];
  var received=0;
  for(var k=0;k<pending.chunks.length;k++){if(pending.chunks[k]!==undefined)received++}
  if(received===pending.total){
    var allData=pending.chunks.join('');
    var binaryStr=atob(allData);
    var bytes=new Uint8Array(binaryStr.length);
    for(var j=0;j<binaryStr.length;j++)bytes[j]=binaryStr.charCodeAt(j);
    var blob=new Blob([bytes],{type:pending.fileType||'application/octet-stream'});
    var url=URL.createObjectURL(blob);
    S.msgs.push({type:'file_ready',name:pending.name,url:url,fileType:pending.fileType,time:pending.time,sender:pending.sender});
    delete S.fileBufs[msg.id];
    renderChat();
  }
}

async function sendChat(text,fileData){
  if(!text&&!fileData)return;
  if(fileData){
    var fileId=Math.random().toString(36).substring(2,10)+Date.now().toString(36);
    var arrayBuffer=fileData.arrayBuffer||await fileData.file.arrayBuffer();
    var chunkSize=16*1024;
    var total=Math.ceil(arrayBuffer.byteLength/chunkSize);
    var t=Date.now();
    var meta={type:'file_meta',name:fileData.name,total:total,id:fileId,fileType:fileData.type,time:t,sender:S.sid,sid:S.sid};
    S.msgs.push(meta);renderChat();
    var metaStr=JSON.stringify(meta);
    var encMeta=S.encKey?await Crypto.encrypt(S.encKey,metaStr).catch(function(){return metaStr}):metaStr;
    Object.keys(S.dcs).forEach(function(sid){var dc=S.dcs[sid];if(dc.readyState==='open'){try{dc.send(encMeta)}catch(e){}}});
    for(var i=0;i<total;i++){
      var start=i*chunkSize;
      var end=Math.min(start+chunkSize,arrayBuffer.byteLength);
      var chunk=new Uint8Array(arrayBuffer.slice(start,end));
      var bin='';for(var j=0;j<chunk.length;j++)bin+=String.fromCharCode(chunk[j]);
      var b64=btoa(bin);
      var chunkMsg={type:'file',name:fileData.name,chunk:b64,idx:i,total:total,id:fileId,time:t,sender:S.sid,fileType:fileData.type};
      var chunkStr=JSON.stringify(chunkMsg);
      var encChunk=S.encKey?await Crypto.encrypt(S.encKey,chunkStr).catch(function(){return chunkStr}):chunkStr;
      Object.keys(S.dcs).forEach(function(sid){var dc=S.dcs[sid];if(dc.readyState==='open'){try{dc.send(encChunk)}catch(e){}}});
    }
    return;
  }
  var msg={type:'chat',text:text,sender:S.name,sid:S.sid,time:Date.now()};
  S.msgs.push(msg);renderChat();
  var msgStr=JSON.stringify(msg);
  var enc=(S.encKey?await Crypto.encrypt(S.encKey,msgStr).catch(function(){return msgStr}):msgStr);
  Object.keys(S.dcs).forEach(function(sid){var dc=S.dcs[sid];if(dc.readyState==='open'){try{dc.send(enc)}catch(e){}}});
}

function renderChat(){
  var container=$('chatMessages');if(!container)return;
  container.innerHTML='';
  if(S.msgs.length===0){container.innerHTML='<div style="text-align:center;color:var(--text4);font-size:13px;padding:40px 16px" data-i18n="chat.empty">No messages yet</div>';return}
  S.msgs.slice(-100).forEach(function(msg){
    var el=document.createElement('div');el.className='chat-msg '+(msg.sid===S.sid?'self':'other');
    var isFile=msg.type==='file_meta'||msg.type==='file_ready';
    var dt=new Date(msg.time);
    var rel=relTime(msg.time);
    var abs=String(dt.getHours()).padStart(2,'0')+':'+String(dt.getMinutes()).padStart(2,'0');
    var ts='<span title="'+abs+'">'+rel+'</span>';
    var body='';
    if(isFile){
      if(msg.type==='file_ready')body='<span class="file-link" data-url="'+msg.url+'" data-name="'+esc(msg.name)+'"><i class="fa-solid fa-paperclip"></i> '+esc(msg.name)+'</span>';
      else body='<span><i class="fa-solid fa-paperclip"></i> '+esc(msg.name)+'</span>';
    }else body=esc(msg.text);
    el.innerHTML='<div class="sender">'+esc(msg.sender||msg.name||'')+'</div><div class="text">'+body+'</div><div class="time">'+ts+'</div>';
    container.appendChild(el);
  });
  var nearBottom=container.scrollTop+container.clientHeight>=container.scrollHeight-60;
  if(nearBottom)container.scrollTop=container.scrollHeight;
  var sb=$('scrollBottom');
  if(sb)sb.style.display=nearBottom?'none':'flex';
  container.querySelectorAll('.file-link[data-url]').forEach(function(el){
    el.addEventListener('click',function(){
      var a=document.createElement('a');a.href=el.dataset.url;a.download=el.dataset.name;
      document.body.appendChild(a);a.click();setTimeout(function(){document.body.removeChild(a)},100);
    });
  });
}

function renderParticipants(){
  var list=$('participantsList');if(!list)return;
  list.innerHTML='';
  var countEl=$('participantCount');
  var count=Object.keys(S.users).length+1;
  if(countEl)countEl.textContent=count;
  var selfRow=document.createElement('div');selfRow.className='participant-row'+(S.speaking[S.sid]?' speaking':'');
  selfRow.innerHTML='<div class="p-avatar">'+esc((S.name||'Y').charAt(0).toUpperCase())+'</div><div class="p-name">'+esc(S.name||'You')+' (You)</div><div class="p-badge"><span class="conn-status '+(S.connectionStatus[S.sid]||'')+'"></span>'+(!S.micOn?'<i class="fa-solid fa-microphone-slash" style="color:#ff6b6b"></i>':'<i class="fa-solid fa-microphone"></i>')+' '+(S._handRaised?'<span class="hand"><i class="fa-solid fa-hand"></i></span>':'')+'</div>';
  list.appendChild(selfRow);
  Object.keys(S.users).forEach(function(sid){
    if(sid===S.sid)return;
    var u=S.users[sid];if(!u)return;
    var row=document.createElement('div');row.className='participant-row'+(S.speaking[sid]?' speaking':'');
    var micOn=u.micOn!==false;
    var handRaised=!!S.raisedHands[sid];
    row.innerHTML='<div class="p-avatar">'+esc((u.name||'?').charAt(0).toUpperCase())+'</div><div class="p-name">'+esc(u.name||'User')+'</div><div class="p-badge"><span class="conn-status '+(S.connectionStatus[sid]||'')+'"></span>'+(micOn?'<i class="fa-solid fa-microphone"></i>':'<i class="fa-solid fa-microphone-slash" style="color:#ff6b6b"></i>')+' '+(handRaised?'<span class="hand"><i class="fa-solid fa-hand"></i></span>':'')+'</div>';
    list.appendChild(row);
  });
}

function toggleChat(){
  S.chatOpen=!S.chatOpen;
  var cp=$('chatPanel');if(cp)cp.classList.toggle('open',S.chatOpen);
  if(S.chatOpen&&S.participantsOpen)toggleParticipants();
  if(S.chatOpen){S._unreadCount=0;updateUI()}
}

function toggleParticipants(){
  S.participantsOpen=!S.participantsOpen;
  var pp=$('participantsPanel');if(pp)pp.classList.toggle('open',S.participantsOpen);
  if(S.participantsOpen&&S.chatOpen)toggleChat();
}

// ---------- Room ----------

async function joinRoom(){
  if(!S.room||!S.name)return;
  S.isCreator=sessionStorage.getItem('sardab-meet-creator')==='1';
  try{
    S.localStream=await navigator.mediaDevices.getUserMedia({video:true,audio:true}).catch(function(){return navigator.mediaDevices.getUserMedia({video:false,audio:true}).catch(function(){return null})});
    if(S.localStream&&S.localStream.getAudioTracks().length>0)S.localStream.getAudioTracks().forEach(function(t){t.enabled=S.micOn});
    if(S.localStream&&S.localStream.getVideoTracks().length>0)S.localStream.getVideoTracks().forEach(function(t){t.enabled=S.camOn});
  }catch(e){}
  S.transport=createSignalingTransport(APP);
  S.transport.onSignal(handleSignal);
  S.transport.onUsers(handleUsers);
  var res=await S.transport.connect(S.room,S.sid,S.name,S.isCreator);
  if(!res||!res.ok){$('joinError').textContent='Failed to join room';return}
  S.isCreator=!!res.creator;
  sessionStorage.setItem('sardab-meet-creator',S.isCreator?'1':'0');
  sessionStorage.setItem('sardab-meet-sid',S.sid);
  sessionStorage.setItem('sardab-meet-name',S.name);
  sessionStorage.setItem('sardab-meet-room',S.room);
  S.inCall=true;
  var rv=$('roomView');if(rv)rv.classList.add('open');
  var js=$('joinScreen');if(js)js.style.display='none';
  var sv=$('selfVideo');if(sv&&S.localStream)sv.srcObject=S.localStream;
  try{S.encKey=await Crypto.deriveKey(S.room)}catch(e){S.encKey=null}
  try{
    var AC=window.AudioContext||window.webkitAudioContext;
    if(AC&&S.localStream&&S.localStream.getAudioTracks().length>0){
      if(!audioCtx)audioCtx=new AC();
      var selfSrc=audioCtx.createMediaStreamSource(S.localStream);
      var selfAnalyser=audioCtx.createAnalyser();selfAnalyser.fftSize=256;
      selfSrc.connect(selfAnalyser);
      S._audioAnalysers[S.sid]={analyser:selfAnalyser,dataArray:new Uint8Array(selfAnalyser.frequencyBinCount)};
    }
  }catch(e){}
  // Resume AudioContext on first user interaction (autoplay policy)
  var resumeAC=function(){
    if(audioCtx&&audioCtx.state==='suspended')audioCtx.resume();
    document.removeEventListener('click',resumeAC);
    document.removeEventListener('touchstart',resumeAC)
  };
  document.addEventListener('click',resumeAC,{once:true});
  document.addEventListener('touchstart',resumeAC,{once:true,passive:true});
  updateUI();renderGrid();renderParticipants();
  Object.keys(S.users).forEach(function(sid){
    if(sid!==S.sid&&!S.pcs[sid])setupPC(sid);
  });
  if(S._speakingInterval)clearInterval(S._speakingInterval);
  S._speakingInterval=setInterval(function(){
    var any=false;
    Object.keys(S._audioAnalysers).forEach(function(sid){
      var a=S._audioAnalysers[sid];
      if(!a||!a.analyser)return;
      a.analyser.getByteFrequencyData(a.dataArray);
      var avg=0;
      for(var i=0;i<a.dataArray.length;i++)avg+=a.dataArray[i];
      avg/=a.dataArray.length;
      var speaking=avg>15;
      if(speaking!==!!S.speaking[sid]){S.speaking[sid]=speaking;any=true}
    });
    if(any){renderGrid();renderParticipants()}
  },800);
}

async function endCall(){
  if(S._endingCall)return;
  S._endingCall=true;
  S.inCall=false;
  if(S.timerInterval){clearInterval(S.timerInterval);S.timerInterval=null}
  if(S._speakingInterval){clearInterval(S._speakingInterval);S._speakingInterval=null}
  if(S.transport){S.transport.disconnect();S.transport=null}
  Object.keys(S.pcs).forEach(function(sid){try{S.pcs[sid].close()}catch(e){}});
  if(S.localStream){S.localStream.getTracks().forEach(function(t){t.stop()});S.localStream=null}
  if(S.screenStream){S.screenStream.getTracks().forEach(function(t){t.stop()});S.screenStream=null}
  S.pcs={};S.dcs={};S.streams={};S.users={};S.makingOffer={};S.ignoreOffer={};S.pendingCandidates={};S.negoDone={};
  S.msgs=[];S.chatOpen=false;S.participantsOpen=false;S.sharing=false;S.screenOn=false;
  S.camOn=true;S.micOn=true;S._handRaised=false;S.raisedHands={};S.remoteScreenTracks=[];
  S._layoutScreen=false;S._maximizedTile=null;S._endingCall=false;S.speaking={};S._audioAnalysers={};
  S._screenTrackId=null;S.connectionStatus={};S._unreadCount=0;
  S._lastRemoteVideoId={};S._lastRemoteAudioId={};S._expectingScreenTrack={};S._expectedScreenInfo={};
  var cp=$('chatPanel');if(cp)cp.classList.remove('open');
  var pp=$('participantsPanel');if(pp)pp.classList.remove('open');
  var rv=$('roomView');if(rv)rv.classList.remove('open');
  var js=$('joinScreen');if(js)js.style.display='flex';
  var grid=$(S.gridId);
  if(grid){
    grid.classList.remove('screen-active','has-maximized');
    grid.innerHTML='';
    var st=document.createElement('div');st.className='video-tile';st.id='selfTile';
    st.innerHTML='<video id="selfVideo" autoplay playsinline muted class="video-el mirror"></video><div class="avatar-placeholder" id="selfPlaceholder" style="display:flex"><i class="fa-solid fa-video-slash"></i><span class="avatar-letter" id="selfAvatar">Y</span><span class="avatar-label" id="selfName">You</span></div><div class="tile-label"><span class="name" id="selfNameLabel">You</span><span class="mic-icon" id="selfMicIcon"><i class="fa-solid fa-microphone"></i></span></div>';
    grid.appendChild(st)
  }
  var el=$('peerAudio');if(el)el.srcObject=null;
  sessionStorage.removeItem('sardab-meet-sid');
  sessionStorage.removeItem('sardab-meet-name');
  sessionStorage.removeItem('sardab-meet-room');
  window.location.href=BASE+'/app/meet/';
}

// ---------- Init ----------

function init(){
  var joinBtn=$('joinBtn'),nameInput=$('nameInput');
  if(joinBtn&&nameInput){
    var doJoin=function(){
      var name=nameInput.value.trim();
      if(!name){$('joinError').textContent='Please enter your name';return}
      S.name=name;joinRoom();
    };
    joinBtn.addEventListener('click',doJoin);
    nameInput.addEventListener('keydown',function(e){if(e.key==='Enter')doJoin()});
  }
  $('btnMic')?.addEventListener('click',toggleMic);
  $('btnCam')?.addEventListener('click',toggleCam);
  $('btnShare')?.addEventListener('click',toggleShare);
  $('btnHand')?.addEventListener('click',toggleHand);
  $('btnEnd')?.addEventListener('click',endCall);
  $('leaveBtn')?.addEventListener('click',endCall);
  $('btnFullscreen')?.addEventListener('click',toggleFullscreen);
  $('btnChat')?.addEventListener('click',toggleChat);
  $('closeChat')?.addEventListener('click',toggleChat);
  $('btnParticipants')?.addEventListener('click',toggleParticipants);
  $('closeParticipants')?.addEventListener('click',toggleParticipants);
  $('participantsToggle')?.addEventListener('click',toggleParticipants);
  $('chatToggle')?.addEventListener('click',toggleChat);
  $('micToggle')?.addEventListener('click',toggleMic);
  $('camToggle')?.addEventListener('click',toggleCam);
  $('screenToggle')?.addEventListener('click',toggleShare);
  var chatInput=$('chatInput'),chatSend=$('chatSend'),chatAttach=$('chatAttach'),fileInput=$('fileInput');
  if(chatInput&&chatSend){
    chatSend.addEventListener('click',function(){if(chatInput.value.trim()){sendChat(chatInput.value);chatInput.value='';chatInput.style.height='auto'}});
    chatInput.addEventListener('keydown',function(e){
      if(e.key==='Enter'&&!e.shiftKey&&chatInput.value.trim()){e.preventDefault();sendChat(chatInput.value);chatInput.value='';chatInput.style.height='auto'}
    });
    chatInput.addEventListener('input',function(){chatInput.style.height='auto';chatInput.style.height=Math.min(chatInput.scrollHeight,120)+'px'});
  }
  if(chatAttach&&fileInput){
    chatAttach.addEventListener('click',function(){fileInput.click()});
    fileInput.addEventListener('change',async function(e){
      var file=e.target.files&&e.target.files[0];
      if(!file)return;
      if(file.size>10*1024*1024){alert('File too large');fileInput.value='';return}
      var buf=await file.arrayBuffer();
      await sendChat('',{name:file.name,type:file.type,file:null,arrayBuffer:buf});
      fileInput.value='';
    });
  }
  var sb=$('scrollBottom');
  if(sb)sb.addEventListener('click',function(){var cm=$('chatMessages');if(cm){cm.scrollTop=cm.scrollHeight;sb.style.display='none'}});
  var cm=$('chatMessages');
  if(cm)cm.addEventListener('scroll',function(){var sb=$('scrollBottom');if(sb)sb.style.display=cm.scrollTop+cm.clientHeight>=cm.scrollHeight-60?'none':'flex'});
  var cp=$('chatPanel');
  if(cp){
    cp.addEventListener('dragover',function(e){e.preventDefault();cp.classList.add('drag-over')});
    cp.addEventListener('dragleave',function(e){cp.classList.remove('drag-over')});
    cp.addEventListener('drop',async function(e){
      e.preventDefault();cp.classList.remove('drag-over');
      var file=e.dataTransfer.files&&e.dataTransfer.files[0];
      if(!file)return;
      if(file.size>10*1024*1024){alert('File too large');return}
      var buf=await file.arrayBuffer();
      await sendChat('',{name:file.name,type:file.type,file:null,arrayBuffer:buf});
    });
  }
  var reactPicker=$('reactionPicker'),reactBtn=$('btnReact');
  if(reactBtn&&reactPicker){
    reactBtn.addEventListener('click',function(){S._reactionPickerOpen=!S._reactionPickerOpen;reactPicker.style.display=S._reactionPickerOpen?'flex':'none'});
    reactPicker.querySelectorAll('.reaction-btn').forEach(function(btn){btn.addEventListener('click',function(){sendReaction(btn.dataset.emoji);reactPicker.style.display='none'})});
    document.addEventListener('click',function(e){if(!e.target.closest('#reactionPicker')&&!e.target.closest('#btnReact')){reactPicker.style.display='none';S._reactionPickerOpen=false}});
  }
  var grid=$(S.gridId);
  if(grid)grid.addEventListener('click',function(e){var tile=e.target.closest('.video-tile');if(tile&&!e.target.closest('.ctrl-btn')&&!e.target.closest('.badge-icon'))toggleMaximize(tile)});
  document.addEventListener('keydown',function(e){
    if(e.target.tagName==='INPUT'||e.target.tagName==='TEXTAREA')return;
    if(e.key==='m'||e.key==='M')toggleMic();
    if(e.key==='v'||e.key==='V')toggleCam();
    if(e.key==='s'||e.key==='S')toggleShare();
    if(e.key==='h'||e.key==='H')toggleHand();
    if(e.key==='c'||e.key==='C')toggleChat();
    if(e.key==='f'||e.key==='F')toggleFullscreen();
    if(e.key==='Escape'){
      if(S.chatOpen)toggleChat();if(S.participantsOpen)toggleParticipants();clearMaximize();
      if(document.fullscreenElement)document.exitFullscreen()['catch'](function(){});
      var rp=$('reactionPicker');if(rp)rp.style.display='none';
    }
  });
  document.addEventListener('fullscreenchange',updateUI);
  window.addEventListener('beforeunload',function(){if(S.inCall&&S.transport)S.transport.sendLeave()});
  if(S.room&&S.name){joinRoom();return}
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);
else init();
})();