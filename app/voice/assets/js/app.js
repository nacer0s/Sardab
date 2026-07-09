(function(){'use strict';console.log('[Sardab] app.js loaded');
const APP='voice';
const BASE=window.location.pathname.replace(/\/app\/.*$/,'');
const S={
  sid:sessionStorage.getItem('sardab-voice-sid')||Math.random().toString(36).substring(2,10),
  room:window.ROOM_CODE||'',
  name:sessionStorage.getItem('sardab-voice-name')||'',
  users:[],pc:null,dc:null,localStream:null,screenStream:null,
  muted:false,speaker:true,sharing:false,screenOn:false,inCall:false,
  peerConnected:false,isCreator:false,makingOffer:false,offerSent:false,
  transport:null,pendingCandidates:[],fileBufs:{},
  encKey:null,msgQueue:[],timer:0,timerInterval:null,
  msgs:[],msgId:0,replyTo:null,
  remoteScreenTracks:[],
  _lastRemoteVideoId:'',_expectingScreenTrack:false,_expectedScreenInfo:null,
  gridId:'voiceGrid',_layoutScreen:false,_maximizedTile:null
};
const STUN={iceServers:[{urls:'stun:stun.l.google.com:19302'},{urls:'stun:stun1.l.google.com:19302'},{urls:'turn:openrelay.metered.ca:80',username:'openrelayproject',credential:'openrelayproject'}]};
const $=function(id){return document.getElementById(id)};
var typingTimer=null;
var audioCtx=null;

function esc(s){
  if(typeof s!=='string')return '';
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function waitForIceGathering(pc){
  return new Promise(function(resolve){
    if(pc.iceGatheringState==='complete'){resolve();return;}
    var check=function(){if(pc.iceGatheringState==='complete'){pc.removeEventListener('icegatheringstatechange',check);resolve()}};
    pc.addEventListener('icegatheringstatechange',check);
    setTimeout(function(){pc.removeEventListener('icegatheringstatechange',check);resolve()},3000);
  });
}

function updateUI(){
  var pn=$('remoteName');var pnl=$('remoteNameLabel');
  var peer=S.users.find(function(u){return u.sid!==S.sid});
  var peerName=peer?peer.name:'';
  if(pn)pn.textContent=S.peerConnected?(peerName||'Connected'):S.isCreator?'Waiting...':'Connecting...';
  if(pnl)pnl.textContent=S.peerConnected?(peerName||'Connected'):S.isCreator?'Waiting...':'Connecting...';

  var mt=$('btnMic');
  if(mt){mt.innerHTML=S.muted?'<i class="fa-solid fa-microphone-slash"></i>':'<i class="fa-solid fa-microphone"></i>';mt.classList.toggle('off',S.muted)}
  var sp=$('btnSpeaker');
  if(sp){sp.innerHTML=S.speaker?'<i class="fa-solid fa-volume-up"></i>':'<i class="fa-solid fa-volume-off"></i>';sp.classList.toggle('off',!S.speaker)}
  var bt=$('btnShare');
  if(bt)bt.classList.toggle('active',S.sharing);

  var lv=$('localAvatar');
  if(lv)lv.textContent=S.name?S.name.charAt(0).toUpperCase():'Y';
  var rv=$('remoteAvatar');
  if(rv)rv.textContent=peerName?peerName.charAt(0).toUpperCase():'?';
  var ln=$('localNameLabel');
  if(ln)ln.textContent=S.name||'You';

  var lmi=$('localMicIcon');
  if(lmi)lmi.innerHTML=S.muted?'<i class="fa-solid fa-microphone-slash"></i>':'<i class="fa-solid fa-microphone"></i>';
  var rmi=$('remoteMicIcon');
  if(rmi)rmi.innerHTML=S.peerConnected?'<i class="fa-solid fa-microphone"></i>':'<i class="fa-solid fa-microphone-slash"></i>';

  var m=Math.floor(S.timer/60),s=S.timer%60;
  var te=$('callTimer');if(te)te.textContent=String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');
  var tr=$('timer');if(tr)tr.textContent=String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');
  var eb=$('e2eeBadge');
  if(eb)eb.classList.toggle('active',!!S.encKey);
}

async function joinRoom(){
  S.inCall=true;
  $('roomView').classList.add('open');
  $('joinView').style.display='none';
  updateUI();
  try{S.localStream=await navigator.mediaDevices.getUserMedia({audio:true,video:false})}
  catch(e){alert('Microphone access required');endCall();return}
  try{S.encKey=await Crypto.deriveKey(S.room)}catch(e){S.encKey=null}
  updateUI();
  S.transport=createSignalingTransport(APP);
  S.transport.onSignal(handleSignal);
  S.transport.onUsers(handleUsers);
  S.transport.onDisconnect(function(){S.peerConnected=false});
  var res=await S.transport.connect(S.room,S.sid,S.name);
  if(!res||!res.ok){console.error('Failed to join room');endCall();return}
  S.isCreator=sessionStorage.getItem('sardab-voice-creator')==='1';
  if(sessionStorage.getItem('sardab-voice-creator')===null){
    S.isCreator=!!res.creator;
    sessionStorage.setItem('sardab-voice-creator',S.isCreator?'1':'0');
  }
  console.log('[Sardab] joinRoom: isCreator='+S.isCreator+' sid='+S.sid+' room='+S.room);
  S.pc=new RTCPeerConnection(STUN);
  S.localStream.getTracks().forEach(function(t){S.pc.addTrack(t,S.localStream)});
  if(S.isCreator){
    S.dc=S.pc.createDataChannel('sardab-data',{ordered:true});
    S.dc.binaryType='arraybuffer';
    setupDC(S.dc);
    console.log('[Sardab] creator: created outbound dc');
  }
  S.pc.ondatachannel=function(e){
    console.log('[Sardab] ondatachannel received state='+(S.dc?S.dc.readyState:'no-dc'));
    if(!S.dc||S.dc.readyState!=='open'){S.dc=e.channel;S.dc.binaryType='arraybuffer';setupDC(S.dc);console.log('[Sardab] dc from ondatachannel')}
  };
  S.pc.onicecandidate=function(e){
    if(e.candidate&&S.transport)S.transport.sendSignal({type:'candidate',candidate:e.candidate.toJSON()})
  };
  S.pc.ontrack=function(e){
    if(e.track.kind==='audio'){
      console.log('[Sardab] ontrack audio');
      var stream=e.streams&&e.streams[0];
      if(!stream)stream=new MediaStream([e.track]);
      var el=$('peerAudio');
      if(el){
        el.srcObject=stream;
        el.play()['catch'](function(){
          function playFn(){el.play()['catch'](function(){});document.removeEventListener('click',playFn);document.removeEventListener('touchstart',playFn)}
          document.addEventListener('click',playFn,{once:true});
          document.addEventListener('touchstart',playFn,{once:true});
        });
      }
      try{
        var AC=window.AudioContext||window.webkitAudioContext;
        if(AC){
          if(!audioCtx)audioCtx=new AC();
          if(audioCtx.state==='suspended')audioCtx.resume();
          if(!audioCtx._gain){audioCtx._gain=audioCtx.createGain();audioCtx._gain.connect(audioCtx.destination)}
          var src=audioCtx.createMediaStreamSource(stream);
          src.connect(audioCtx._gain);
          audioCtx._gain.gain.value=S.speaker?1:0;
        }
      }catch(ex){}
    }else if(e.track.kind==='video'){
      if(e.track.id===S._lastRemoteVideoId)return;
      S._lastRemoteVideoId=e.track.id;
      var vs=(e.streams&&e.streams[0])||new MediaStream([e.track]);
      var isScreen=S._expectingScreenTrack;
      S._expectingScreenTrack=false;
      var screenOwner=S._expectedScreenInfo;
      S._expectedScreenInfo=null;
      if(!isScreen){
        var lbl=(e.track.label||'').toLowerCase();
        if(lbl.indexOf('screen')>=0||lbl.indexOf('display')>=0||lbl.indexOf('window')>=0)isScreen=true;
      }
      if(isScreen){
        var sid=screenOwner?screenOwner.sid:'';
        var sname=screenOwner?screenOwner.name:'';
        if(!sname){
          var peer=S.users.find(function(u){return u.sid!==S.sid});
          if(peer)sname=peer.name;
        }
        var tile=document.createElement('div');
        tile.className='video-tile';
        tile.setAttribute('data-screen','true');
        var video=document.createElement('video');
        video.autoplay=true;video.playsinline=true;video.className='video-el screen-el';
        video.srcObject=vs;
        tile.appendChild(video);
        var lblDiv=document.createElement('div');
        lblDiv.className='tile-label';
        var nameSpan=document.createElement('span');
        nameSpan.className='name';
        nameSpan.textContent=sname?(sname+"'s Screen"):'Screen Share';
        lblDiv.appendChild(nameSpan);
        tile.appendChild(lblDiv);
        var vg=$(S.gridId);
        if(vg){
          if(vg.classList.contains('screen-active')){var ma=$('screenMainArea');if(ma)ma.appendChild(tile);else vg.appendChild(tile)}
          else vg.appendChild(tile);
        }
        var entry={sid:sid,track:e.track,tile:tile,video:video,sname:sname};
        S.remoteScreenTracks.push(entry);
        updateScreenLayout();
        e.track.onended=function(){
          if(tile.parentNode)tile.parentNode.removeChild(tile);
          video.srcObject=null;
          for(var k=0;k<S.remoteScreenTracks.length;k++){
            if(S.remoteScreenTracks[k].track===e.track){
              S.remoteScreenTracks.splice(k,1);break
            }
          }
          updateUI();updateScreenLayout();
        };
      }
    }
  };
  S.pc.onconnectionstatechange=function(){
    console.log('[Sardab] connectionState: '+S.pc.connectionState+' sigState: '+S.pc.signalingState);
    if(S.pc.connectionState==='connected'){S.peerConnected=true;startTimer();updateUI();updateScreenLayout();if(S.sharing)sendScreenRenegotiation();var vg=$(S.gridId);if(vg)vg.classList.remove('single')}
    else if(S.pc.connectionState==='disconnected'){
      S.peerConnected=false;updateUI();
      setTimeout(function(){
        if(S.pc&&S.pc.connectionState==='disconnected'&&!S._reconnecting){
          S._reconnecting=true;
          S.pc.restartIce()['catch'](function(){});
          setTimeout(function(){S._reconnecting=false;if(S.pc&&S.pc.connectionState==='disconnected')endCall()},10000);
        }
      },3000);
    }else if(S.pc.connectionState==='failed')endCall();
  };
  S.pc.oniceconnectionstatechange=function(){console.log('[Sardab] iceState: '+S.pc.iceConnectionState);if(S.pc.iceConnectionState==='failed')endCall()};
  var hasPeer=S.users.some(function(u){return u.sid!==S.sid});
  console.log('[Sardab] post-setup: isCreator='+S.isCreator+' hasPeer='+hasPeer+' users='+S.users.length);
  if(S.isCreator&&!S.peerConnected&&hasPeer){console.log('[Sardab] starting call from post-setup');startCall()}
  renderUsers();
  updateUserCount();
}

function startTimer(){
  if(S.timerInterval)return;
  S.timer=0;
  S.timerInterval=setInterval(function(){if(S.peerConnected){S.timer++;updateUI()}},1000);
}

function setupDC(dc){
  dc.binaryType='arraybuffer';
  try{dc.bufferedAmountLowThreshold=16384}catch(e){}
  dc.onopen=function(){S.peerConnected=true;if(!S.timerInterval)startTimer();flushMsgQueue();updateUI()};
  dc.onclose=function(){S.peerConnected=false;updateUI()};
  dc.onmessage=async function(e){
    if(!S.encKey)return;
    try{handleMsg(JSON.parse(await Crypto.decrypt(S.encKey,e.data)))}
    catch(err){console.error('decrypt err',err)}
  };
}

function handleMsg(msg){
  if(msg.type==='file')handleFileChunk(msg);
  else if(msg.type==='chat'){S.msgs.push(msg);renderChatMsg(msg,false)}
  else if(msg.type==='delete')handleDeleteMsg(msg);
  else if(msg.type==='typing')handleTyping(msg);
  else if(msg.type==='reaction')handleReaction(msg);
  else if(msg.type==='screen_state'){
    if(msg.action==='start'){
      S._expectingScreenTrack=true;
      S._expectedScreenInfo={sid:msg.sender,name:msg.name};
    }else if(msg.action==='stop'){
      S._expectingScreenTrack=false;
      var idx=-1;
      for(var i=0;i<S.remoteScreenTracks.length;i++){
        if(S.remoteScreenTracks[i].sid===msg.sender){idx=i;break}
      }
      if(idx>=0){
        var entry=S.remoteScreenTracks[idx];
        if(entry.track){entry.track.onended=null}
        if(entry.tile&&entry.tile.parentNode)entry.tile.parentNode.removeChild(entry.tile);
        if(entry.video)entry.video.srcObject=null;
        S.remoteScreenTracks.splice(idx,1);
      }
      updateUI();updateScreenLayout();
    }
  }
}

function handleDeleteMsg(msg){
  if(!msg.targetId)return;
  var el=document.querySelector('.chat-msg[data-msg-id="'+msg.targetId+'"]');
  if(el)el.remove();
}

function handleTyping(msg){
  var el=$('typingIndicator');
  if(!el)return;
  if(msg.isTyping){
    el.textContent=i18n.t('chat.typing',{name:msg.name});
    el.classList.add('visible');
    clearTimeout(el._typingTimer);
    el._typingTimer=setTimeout(function(){el.classList.remove('visible')},2000);
  }else el.classList.remove('visible');
}

function handleReaction(msg){
  if(!msg.msgId||!msg.reaction||!msg.action)return;
  var target=S.msgs.find(function(m){return m.id===msg.msgId});
  if(!target)return;
  if(!target.reactions)target.reactions={};
  if(msg.action==='remove'){
    if(target.reactions[msg.reaction]){delete target.reactions[msg.reaction][msg.sender];if(Object.keys(target.reactions[msg.reaction]).length===0)delete target.reactions[msg.reaction]}
  }else if(msg.action==='add'){
    for(var emoji in target.reactions){if(target.reactions[emoji][msg.sender]){delete target.reactions[emoji][msg.sender];if(Object.keys(target.reactions[emoji]).length===0)delete target.reactions[emoji]}}
    if(!target.reactions[msg.reaction])target.reactions[msg.reaction]={};
    target.reactions[msg.reaction][msg.sender]=true;
  }
  renderReactions(msg.msgId);
}

function handleSendReaction(msgId,reaction){
  var msg=S.msgs.find(function(m){return m.id===msgId});
  if(!msg)return;
  if(!msg.reactions)msg.reactions={};
  if(msg.reactions[reaction]&&msg.reactions[reaction][S.sid]){
    var sig={type:'reaction',action:'remove',msgId:msgId,reaction:reaction,sender:S.sid,name:S.name};
    broadcast(sig);handleReaction(sig);return;
  }
  var old=null;
  for(var emoji in msg.reactions){if(msg.reactions[emoji][S.sid]){old=emoji;break}}
  if(old){var rm={type:'reaction',action:'remove',msgId:msgId,reaction:old,sender:S.sid,name:S.name};broadcast(rm);handleReaction(rm)}
  var sig={type:'reaction',action:'add',msgId:msgId,reaction:reaction,sender:S.sid,name:S.name};
  broadcast(sig);handleReaction(sig);
}

function renderReactions(msgId){
  var msgEl=document.querySelector('.chat-msg[data-msg-id="'+msgId+'"]');
  if(!msgEl)return;
  var target=S.msgs.find(function(m){return m.id===msgId});
  if(!target||!target.reactions){var e=msgEl.querySelector('.msg-reactions');if(e)e.innerHTML='';return}
  var reactEl=msgEl.querySelector('.msg-reactions');
  if(!reactEl){reactEl=document.createElement('div');reactEl.className='msg-reactions';msgEl.appendChild(reactEl)}
  reactEl.innerHTML='';
  var keys=Object.keys(target.reactions).sort();
  for(var i=0;i<keys.length;i++){
    var emoji=keys[i],count=Object.keys(target.reactions[emoji]).length;
    if(count===0)continue;
    var r=document.createElement('span');
    r.className='reaction-badge';r.dataset.reaction=emoji;
    r.textContent=emoji+' '+count;
    reactEl.appendChild(r);
  }
}

function handleFileChunk(msg){
  if(!msg.id||msg.idx===undefined||!msg.chunk||typeof msg.chunk!=='string')return;
  if(!S.fileBufs[msg.id]){
    S.fileBufs[msg.id]={chunks:[],total:msg.total,name:msg.name,fileType:msg.fileType||'',sender:msg.sender,time:msg.time,authorName:msg.authorName};
    S.msgs.push({type:'file_meta',name:msg.name,id:msg.id,total:msg.total,fileType:msg.fileType||'',time:msg.time,sender:msg.sender,authorName:msg.authorName});
    renderChatMsg({type:'file_meta',name:msg.name,total:msg.total,id:msg.id,fileType:msg.fileType||'',time:msg.time,sender:msg.sender,authorName:msg.authorName},false);
  }
  S.fileBufs[msg.id].chunks[msg.idx]=msg.chunk;
  var pending=S.fileBufs[msg.id];
  var received=0;
  for(var k=0;k<pending.total;k++){if(pending.chunks[k]!==undefined)received++}
  if(received===pending.total){
    try{
      var allBytes=[];
      for(var k=0;k<pending.total;k++){
        var chunkB64=pending.chunks[k].replace(/[^A-Za-z0-9+/=]/g,'');
        var binaryStr=atob(chunkB64);
        for(var j=0;j<binaryStr.length;j++)allBytes.push(binaryStr.charCodeAt(j));
      }
      var bytes=new Uint8Array(allBytes);
      var blob=new Blob([bytes],{type:pending.fileType||'application/octet-stream'});
      var url=URL.createObjectURL(blob);
      var rid=msg.id+'-ready';
      S.msgs.push({type:'file_ready',name:pending.name,url:url,fileType:pending.fileType,time:pending.time,sender:pending.sender,authorName:pending.authorName,id:rid});
      renderChatMsg({type:'file_ready',name:pending.name,url:url,fileType:pending.fileType,time:pending.time,sender:pending.sender,authorName:pending.authorName,id:rid},false);
    }catch(e){console.error('file assemble err',e)}
    delete S.fileBufs[msg.id];
  }
}

function flushMsgQueue(){
  if(!S.peerConnected||!S.encKey||!S.msgQueue.length)return;
  for(var i=0;i<S.msgQueue.length;i++)broadcast(S.msgQueue[i]);
  S.msgQueue=[];
}

function broadcast(msg){
  if(!S.encKey)return;
  try{
    Crypto.encrypt(S.encKey,msg).then(function(enc){
      if(S.dc&&S.dc.readyState==='open'){try{S.dc.send(enc)}catch(e){S.msgQueue.push(enc)}}
      else S.msgQueue.push(enc);
    });
  }catch(e){console.error('broadcast err',e)}
}

function handleUsers(users){
  S.users=(users||[]).map(function(u){return{sid:u.sid,name:u.name,online:u.online!==false}});
  if(!S.users.some(function(u){return u.sid===S.sid}))S.users.push({sid:S.sid,name:S.name,online:true});
  renderUsers();
  updateUserCount();
  updateUI();
}

function handleSignal(sig){
    console.log('[Sardab] signal t='+sig.t+' f='+sig.f.substring(0,6)+' type='+(sig.d?sig.d.type:'')+' pc='+!!S.pc+' creator='+S.isCreator);
    if(sig.f===S.sid){console.log('[Sardab] ignoring self signal');return}
    if(sig.t==='join'){
        if(!S.users.some(function(u){return u.sid===sig.f}))S.users.push({sid:sig.f,name:(sig.d||{}).name||'User',online:true});
        renderUsers();updateUserCount();
        if(S.isCreator&&!S.peerConnected&&S.pc)startCall();else console.log('[Sardab] join not starting call: creator='+S.isCreator+' connected='+S.peerConnected+' pc='+!!S.pc);
        updateUI();return;
    }
    if(sig.t==='leave'){
        S.users=S.users.filter(function(u){return u.sid!==sig.f});
        renderUsers();updateUserCount();
        if(S.peerConnected)endCall();else console.log('[Sardab] leave not ending: not connected');
        updateUI();return;
    }
    if(sig.t!=='signal'||!sig.d||!sig.d.type)return;
    var d=sig.d;
    switch(d.type){
        case'offer':handleOffer(d,sig.f);break;
        case'answer':handleAnswer(d);break;
        case'candidate':handleIce(d);break;
    }
}

async function handleOffer(data,fromSid){
  console.log('[Sardab] handleOffer: from='+fromSid.substring(0,6)+' sigState='+(S.pc?S.pc.signalingState:'no-pc')+' makingOffer='+S.makingOffer);
  if(!S.pc||!data.sdp||S.makingOffer)return;
  try{
    if(S.pc.signalingState!=='stable'){try{console.log('[Sardab] rollback');await S.pc.setLocalDescription({type:'rollback'})}catch(e){}}
    await S.pc.setRemoteDescription(new RTCSessionDescription({type:'offer',sdp:data.sdp}));
    console.log('[Sardab] remote desc set (offer), pending='+S.pendingCandidates.length);
    while(S.pendingCandidates.length>0){var c=S.pendingCandidates.shift();try{await S.pc.addIceCandidate(new RTCIceCandidate(c))}catch(e){}}
    var answer=await S.pc.createAnswer();
    await S.pc.setLocalDescription(answer);
    await waitForIceGathering(S.pc);
    console.log('[Sardab] sending answer');
    if(S.transport)S.transport.sendSignal({type:'answer',sdp:S.pc.localDescription.sdp});
  }catch(e){console.warn('[Sardab] handleOffer err:',e)}
}

async function handleAnswer(data){
  console.log('[Sardab] handleAnswer: sigState='+(S.pc?S.pc.signalingState:'no-pc'));
  if(!S.pc||!data.sdp)return;
  try{
    if(S.pc.signalingState==='have-local-offer'){
      await S.pc.setRemoteDescription(new RTCSessionDescription({type:'answer',sdp:data.sdp}));
      console.log('[Sardab] remote desc set (answer), pending='+S.pendingCandidates.length);
      while(S.pendingCandidates.length>0){var c=S.pendingCandidates.shift();try{await S.pc.addIceCandidate(new RTCIceCandidate(c))}catch(e){}}
    }else console.log('[Sardab] answer ignored: state='+S.pc.signalingState);
  }catch(e){console.warn('[Sardab] handleAnswer err:',e)}
}

function handleIce(data){
  if(!data.candidate)return;
  if(!S.pc){S.pendingCandidates.push(data.candidate);return}
  try{
    if(S.pc.remoteDescription&&S.pc.remoteDescription.type)S.pc.addIceCandidate(new RTCIceCandidate(data.candidate))['catch'](function(){});
    else S.pendingCandidates.push(data.candidate);
  }catch(e){}
}

async function startCall(){
  console.log('[Sardab] startCall: makingOffer='+S.makingOffer+' sigState='+(S.pc?S.pc.signalingState:'no-pc')+' dc='+(S.dc?S.dc.readyState:'no-dc')+' sent='+S.offerSent);
  if(!S.pc||S.makingOffer||S.offerSent)return;
  S.makingOffer=true;
  try{
    var offer=await S.pc.createOffer();
    console.log('[Sardab] offer created, setting local desc');
    await S.pc.setLocalDescription(offer);
    console.log('[Sardab] waiting ICE gathering');
    await waitForIceGathering(S.pc);
    console.log('[Sardab] sending offer, candidates in SDP='+(S.pc.localDescription.sdp.match(/a=candidate/g)||[]).length);
    if(S.transport)S.transport.sendSignal({type:'offer',sdp:S.pc.localDescription.sdp});
    S.offerSent=true;
  }catch(e){console.error('[Sardab] startCall err:',e)}
  finally{S.makingOffer=false;console.log('[Sardab] startCall done sent='+S.offerSent)}
}

async function sendScreenRenegotiation(){
  if(!S.pc||S.makingOffer)return;
  var cs=S.pc.connectionState;
  if(cs!=='connected'&&cs!=='connecting'){console.log('[Sardab] sendScreenRenegotiation: skip state='+cs);return}
  console.log('[Sardab] sendScreenRenegotiation');
  try{
    var offer=await S.pc.createOffer({iceRestart:false});
    await S.pc.setLocalDescription(offer);
    await waitForIceGathering(S.pc);
    if(S.transport)S.transport.sendSignal({type:'offer',sdp:S.pc.localDescription.sdp});
  }catch(e){console.warn('[Sardab] screen reneg err',e)}
}

async function sendFile(file){
  if(!S.encKey){alert('Not encrypted');return}
  if(file.size>10*1024*1024){alert('File must be under 10MB');return}
  var fileId=S.sid+'-'+Math.random().toString(36).substring(2,10);
  var arrayBuffer=await file.arrayBuffer();
  var chunkSize=4096;
  var total=Math.ceil(arrayBuffer.byteLength/chunkSize);
  var t=Date.now();
  S.msgs.push({type:'file_meta',name:file.name,id:fileId,total:total,fileType:file.type,time:t,sender:S.sid,authorName:S.name});
  chatPanel(true);
  renderChatMsg({type:'file_meta',name:file.name,total:total,id:fileId,fileType:file.type,time:t,sender:S.sid,authorName:S.name},true);
  for(var i=0;i<total;i++){
    var start=i*chunkSize;
    var end=Math.min(start+chunkSize,arrayBuffer.byteLength);
    var chunk=new Uint8Array(arrayBuffer.slice(start,end));
    var bin='';
    for(var j=0;j<chunk.length;j++)bin+=String.fromCharCode(chunk[j]);
    var b64=btoa(bin);
    broadcast({type:'file',name:file.name,chunk:b64,idx:i,total:total,id:fileId,time:t,sender:S.sid,fileType:file.type,authorName:S.name});
    if(S.dc&&S.dc.readyState==='open'&&S.dc.bufferedAmount>64000){
      await new Promise(function(resolve){
        var done=false;
        var timer=setTimeout(function(){done=true;resolve()},3000);
        S.dc.addEventListener('bufferedamountlow',function h(){if(!done){done=true;clearTimeout(timer);resolve()}},{once:true});
      });
    }
  }
  var blob=new Blob([arrayBuffer],{type:file.type||'application/octet-stream'});
  var url=URL.createObjectURL(blob);
  var rid=fileId+'-ready';
  S.msgs.push({type:'file_ready',name:file.name,url:url,fileType:file.type,time:t,sender:S.sid,authorName:S.name,id:rid});
  renderChatMsg({type:'file_ready',name:file.name,url:url,fileType:file.type,time:t,sender:S.sid,authorName:S.name,id:rid},true);
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
  S._maximizedTile=tile;
  tile.classList.add('maximized');
  grid.classList.add('has-maximized');
}

function updateScreenLayout(){
  clearMaximize();
  var grid=$(S.gridId);
  if(!grid)return;
  var hasScreen=S.sharing||S.remoteScreenTracks.length>0;
  console.log('[Sardab] updateScreenLayout hasScreen='+hasScreen+' layoutScreen='+S._layoutScreen);
  if(hasScreen===S._layoutScreen){
    // Already in correct mode — ensure new tiles go to right container
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

function showToast(msg){
  var t=$('toast');
  if(!t){t=document.createElement('div');t.id='toast';Object.assign(t.style,{position:'fixed',top:'80px',left:'50%',transform:'translateX(-50%)',background:'var(--card,#1e1e2e)',color:'var(--text,#eee)',border:'1px solid var(--border,#333)',borderRadius:'var(--radius,12px)',padding:'10px 20px',fontSize:'13px',zIndex:999,boxShadow:'0 4px 20px rgba(0,0,0,0.3)',opacity:'0',transition:'opacity .3s ease',maxWidth:'90vw',textAlign:'center',pointerEvents:'none'});document.body.appendChild(t)}
  t.textContent=msg;t.style.opacity='1';
  if(t._hide)clearTimeout(t._hide);
  t._hide=setTimeout(function(){t.style.opacity='0'},3000);
}

async function toggleShare(){
  if(S.sharing){
    console.log('[Sardab] toggleShare stopping');
    broadcast({type:'screen_state',action:'stop',sender:S.sid});
    if(S.screenStream){S.screenStream.getTracks().forEach(function(t){t.stop()});S.screenStream=null}
    var lsv=$('localScreenVideo');
    var lst=$('localScreenTile');
    if(lsv){lsv.srcObject=null;lsv.style.display='none'}
    if(lst)lst.style.display='none';
    if(S.pc){
      var senders=S.pc.getSenders();
      for(var i=senders.length-1;i>=0;i--){
        var s=senders[i];
        if(s.track&&s.track.kind==='video'&&s.track.label&&s.track.label.indexOf('screen')>=0){
          try{S.pc.removeTrack(s);}catch(e){}
        }
      }
    }
    S.sharing=false;S.screenOn=false;
    updateUI();updateScreenLayout();
    sendScreenRenegotiation();
    return;
  }
  console.log('[Sardab] toggleShare starting');
  if(!navigator.mediaDevices||!navigator.mediaDevices.getDisplayMedia){
    showToast('Screen sharing not supported on this browser. Try Chrome/Edge on Android or Safari on iOS 16+.');
    return;
  }
  try{
    S.screenStream=await navigator.mediaDevices.getDisplayMedia({video:{frameRate:15,cursor:'always'},audio:false});
    var screenTrack=S.screenStream.getVideoTracks()[0];
    if(!screenTrack){S.screenStream=null;return}
    console.log('[Sardab] got screen track');
    var lsv=$('localScreenVideo');
    var lst=$('localScreenTile');
    if(lsv&&lst){
      lsv.srcObject=S.screenStream;
      lsv.style.display='';
      lst.style.display='flex';
    }
    if(S.pc){
      S.pc.addTrack(screenTrack,S.screenStream);
      broadcast({type:'screen_state',action:'start',sender:S.sid,name:S.name});
      sendScreenRenegotiation();
    }
    screenTrack.onended=function(){if(S.sharing)toggleShare()};
    S.sharing=true;S.screenOn=true;
    updateUI();updateScreenLayout();
  }catch(e){console.warn('[Sardab] toggleShare err',e.name||e);
    if(e.name==='NotAllowedError')showToast('Screen sharing was cancelled.');
    else if(e.name==='NotSupportedError'||e.name==='NotFoundError')showToast('Screen sharing not available on this device.');
    else showToast('Screen sharing failed. Try again.');
    S.screenStream=null;
  }
}

function resumeAudio(){
  var el=$('peerAudio');
  if(el&&el.paused&&el.srcObject)el.play()['catch'](function(){});
  try{
    var AC=window.AudioContext||window.webkitAudioContext;
    if(AC&&audioCtx&&audioCtx.state==='suspended')audioCtx.resume();
  }catch(e){}
}

function toggleMute(){
  S.muted=!S.muted;
  if(S.localStream)S.localStream.getAudioTracks().forEach(function(t){t.enabled=!S.muted});
  resumeAudio();updateUI();
}

function toggleSpeaker(){
  S.speaker=!S.speaker;
  var el=$('peerAudio');
  if(el)el.muted=!S.speaker;
  if(audioCtx&&audioCtx._gain)audioCtx._gain.gain.value=S.speaker?1:0;
  resumeAudio();updateUI();
}

function chatPanel(show){
  var cp=$('chatPanel');
  if(!cp)return;
  if(show===undefined)show=!cp.classList.contains('open');
  cp.classList.toggle('open',show);
  if(show){
    var ci=$('chatInput');
    if(ci)ci.focus();
    var cm=$('chatMessages');
    if(cm&&cm.children.length===0)cm.innerHTML='<div class="empty-msg-placeholder"><div><i class="fa-solid fa-message"></i><span data-i18n="chat.empty">No messages yet</span></div></div>';
  }
}

function renderChatMsg(msg,isMine){
  var container=$('chatMessages');
  if(!container)return;
  if(container.children.length===1&&container.querySelector('.empty-msg-placeholder'))container.innerHTML='';
  var div=document.createElement('div');
  div.className='chat-msg '+(isMine?'self':'other');
  div.dataset.msgId=msg.id||('m'+(++S.msgId));
  var ts='';
  if(msg.time){var t=new Date(msg.time);if(!isNaN(t.getTime()))ts=String(t.getHours()).padStart(2,'0')+':'+String(t.getMinutes()).padStart(2,'0')}
  var displayName=msg.authorName||msg.sender||'';
  var isImage=msg.fileType&&msg.fileType.indexOf('image/')===0;
  var isVideo=msg.fileType&&msg.fileType.indexOf('video/')===0;
  var hasText=!!msg.text;
  var replyHtml='';
  if(msg.replyTo)replyHtml='<div class="msg-reply"><span class="reply-name">'+esc(msg.replyTo.name||'')+'</span><span class="reply-text">'+esc(msg.replyTo.text||'')+'</span></div>';
  var body='<div class="sender">'+esc(displayName)+'</div>';
  if(replyHtml)body+=replyHtml;
  body+='<div class="text">';
  if(isImage&&msg.url)body+='<div class="msg-image"><img src="'+esc(msg.url)+'" alt="'+esc(msg.name)+'"></div>';
  else if(isVideo&&msg.url)body+='<div class="msg-video"><video src="'+esc(msg.url)+'" controls preload="metadata"></video></div>';
  else if(hasText)body+=esc(msg.text);
  else if(msg.url)body+='<a href="'+esc(msg.url)+'" download="'+esc(msg.name)+'" class="file-link"><i class="fas fa-paperclip"></i> '+esc(msg.name)+'</a>';
  else body+='<i class="fas fa-paperclip"></i> '+esc(msg.name);
  body+='</div><div class="msg-reactions"></div><div class="msg-actions"><button class="msg-action-btn" data-action="reply" title="Reply"><i class="fa-solid fa-reply"></i></button><button class="msg-action-btn" data-action="react" title="React"><i class="fa-solid fa-face-smile"></i></button><button class="msg-action-btn" data-action="delete" title="Delete"><i class="fa-solid fa-trash"></i></button></div>';
  body+='<div class="time">'+ts+'</div>';
  div.innerHTML=body;
  container.appendChild(div);
  container.scrollTop=container.scrollHeight;
  if(msg.reactions&&Object.keys(msg.reactions).length)renderReactions(msg.id||div.dataset.msgId);
  Array.from(div.querySelectorAll('.file-link')).forEach(function(el){
    el.addEventListener('click',function(e){
      e.preventDefault();
      var a=document.createElement('a');
      a.href=this.href;a.download=this.getAttribute('download')||'';
      document.body.appendChild(a);a.click();
      setTimeout(function(){document.body.removeChild(a)},100);
    });
  });
}

async function sendChat(text){
  if(!text.trim()||!S.encKey)return;
  var msg={type:'chat',text:text.trim(),authorName:S.name,time:Date.now(),sender:S.sid,id:'m'+(++S.msgId)};
  if(S.replyTo){
    var orig=S.msgs.find(function(m){return m.id===S.replyTo.id});
    if(orig)msg.replyTo={id:orig.id,name:orig.authorName||orig.sender,text:orig.text||(orig.fileType?'📎 '+(orig.name||'File'):'')};
  }
  S.msgs.push(msg);renderChatMsg(msg,true);broadcast(msg);
}

function renderUsers(){
  var modalBody=$('modalBody');
  if(!modalBody)return;
  if(!S.users||S.users.length===0){modalBody.innerHTML='<div class="empty-users">No users connected</div>';return}
  var html='';
  for(var i=0;i<S.users.length;i++){
    var u=S.users[i],isMe=u.sid===S.sid,initial=(u.name||'?').charAt(0),online=u.online!==false;
    html+='<div class="user-item"><div class="user-avatar">'+esc(initial)+'</div><span class="user-name">'+esc(u.name)+(isMe?' <span class="you-badge">(You)</span>':'')+'</span><span class="user-status'+(online?' online':'')+'"></span></div>';
  }
  modalBody.innerHTML=html;
}

function updateUserCount(){
  var mc=$('modalUserCount');
  if(mc)mc.textContent=S.users.length;
}

function toggleSidebar(){
  var modal=$('usersModal');
  if(modal)modal.classList.toggle('open');
}

function closeUsersModal(){
  var modal=$('usersModal');
  if(modal)modal.classList.remove('open');
}

async function endCall(){
  if(S._endingCall)return;
  S._endingCall=true;
  S.inCall=false;
  if(S.timerInterval){clearInterval(S.timerInterval);S.timerInterval=null}
  if(S.transport){S.transport.disconnect();S.transport=null}
  if(S.pc){S.pc.close();S.pc=null}
  if(S.localStream){S.localStream.getTracks().forEach(function(t){t.stop()});S.localStream=null}
  if(S.screenStream){S.screenStream.getTracks().forEach(function(t){t.stop()});S.screenStream=null}
  if(typingTimer){clearTimeout(typingTimer);typingTimer=null}
  S.peerConnected=false;S.pendingCandidates=[];S.sharing=false;S.screenOn=false;S.users=[];S.msgs=[];
  S.dc=null;S.fileBufs={};S.msgQueue=[];S._expectingScreenTrack=false;S._expectedScreenInfo=null;S._lastRemoteVideoId='';
  for(var ri=0;ri<S.remoteScreenTracks.length;ri++){
    var e=S.remoteScreenTracks[ri];
    if(e.track)e.track.onended=null;
    if(e.video)e.video.srcObject=null;
    if(e.tile&&e.tile.parentNode)e.tile.parentNode.removeChild(e.tile);
  }
  S.remoteScreenTracks=[];
  updateScreenLayout();
  S._layoutScreen=false;
  var el=$('peerAudio');if(el)el.srcObject=null;
  var lsv=$('localScreenVideo');if(lsv){lsv.srcObject=null;lsv.style.display='none'}
  var lst=$('localScreenTile');if(lst)lst.style.display='none';
  sessionStorage.removeItem('sardab-voice-sid');
  sessionStorage.removeItem('sardab-voice-name');
  sessionStorage.removeItem('sardab-voice-room');
  sessionStorage.removeItem('sardab-voice-creator');
  window.location.href=BASE+'/app/voice/';
}

function showReactionPicker(msgId){
  document.querySelectorAll('.reaction-picker').forEach(function(el){el.remove()});
  if(window.dismissPicker){document.removeEventListener('click',window.dismissPicker);window.dismissPicker=null}
  var picker=document.createElement('div');
  picker.className='reaction-picker';
  var reactions=['👍','❤️','😂','😮','😢','🙏'];
  picker.innerHTML=reactions.map(function(r){return '<button class="reaction-btn" data-msg-id="'+msgId+'" data-reaction="'+r+'">'+r+'</button>'}).join('');
  document.body.appendChild(picker);
  var dismiss=function(e){if(!picker.contains(e.target)){picker.remove();document.removeEventListener('click',dismiss);window.dismissPicker=null}};
  window.dismissPicker=dismiss;
  setTimeout(function(){document.addEventListener('click',dismiss)},10);
}

function showReplyPreview(){
  if(!S.replyTo)return;
  var bar=$('chatInput').parentNode;
  if(!bar)return;
  var preview=$('replyPreview');
  if(!preview){
    preview=document.createElement('div');
    preview.id='replyPreview';preview.className='reply-preview';
    bar.parentNode.insertBefore(preview,bar);
  }
  preview.innerHTML='<span class="reply-preview-text">'+esc(S.replyTo.text)+'</span><button class="reply-preview-close" title="Cancel"><i class="fa-solid fa-xmark"></i></button>';
  preview.querySelector('.reply-preview-close').onclick=function(){S.replyTo=null;var p=$('replyPreview');if(p)p.remove()};
}

function showDeleteOptions(msgId,isOwnMsg){
  var existing=$('deleteOptions');
  if(existing)existing.remove();
  var modal=document.createElement('div');
  modal.id='deleteOptions';modal.className='delete-options-modal';
  var html='<button class="del-opt" data-action="me"><i class="fa-solid fa-user"></i><span data-i18n="delete.me">Delete for me</span></button>';
  if(isOwnMsg)html+='<button class="del-opt del-opt-danger" data-action="all"><i class="fa-solid fa-users"></i><span data-i18n="delete.all">Delete for everyone</span></button>';
  modal.innerHTML='<div class="backdrop"></div><div class="delete-options-panel"><h3 data-i18n="delete.msg">Delete Message</h3>'+html+'</div>';
  document.body.appendChild(modal);
  modal.querySelector('.backdrop').onclick=function(){modal.remove()};
  modal.querySelectorAll('.del-opt').forEach(function(btn){
    btn.onclick=function(){
      var action=btn.dataset.action;
      modal.remove();
      if(action==='me')confirmDelete(msgId,false);
      else if(action==='all')confirmDelete(msgId,true);
    };
  });
}

function confirmDelete(msgId,forAll){
  var existing=$('confirmModal');
  if(existing)existing.remove();
  var modal=document.createElement('div');
  modal.id='confirmModal';modal.className='confirm-modal';
  var title=forAll?i18n.t('delete.all?'):i18n.t('delete.me?');
  var text=forAll?i18n.t('delete.all.desc'):i18n.t('delete.me.desc');
  modal.innerHTML='<div class="backdrop"></div><div class="confirm-panel"><h3>'+title+'</h3><p>'+text+'</p><div class="confirm-actions"><button class="confirm-btn cancel">'+i18n.t('cancel')+'</button><button class="confirm-btn danger">'+i18n.t(forAll?'delete.all':'delete.me')+'</button></div></div>';
  document.body.appendChild(modal);
  modal.querySelector('.backdrop').onclick=function(){modal.remove()};
  modal.querySelector('.confirm-btn.cancel').onclick=function(){modal.remove()};
  modal.querySelector('.confirm-btn.danger').onclick=function(){modal.remove();deleteMsg(msgId,forAll)};
}

function deleteMsg(msgId,forAll){
  var msg=S.msgs.find(function(m){return m.id===msgId});
  if(!msg)return;
  if(forAll)broadcast({type:'delete',targetId:msgId,sender:S.sid});
  var existing=document.querySelector('.chat-msg[data-msg-id="'+msgId+'"]');
  if(existing)existing.remove();
}

function init(){
  $('btnMic')?.addEventListener('click',toggleMute);
  $('btnSpeaker')?.addEventListener('click',toggleSpeaker);
  $('btnShare')?.addEventListener('click',toggleShare);
  $('btnEnd')?.addEventListener('click',function(){endCall()});
  $('leaveBtn')?.addEventListener('click',function(){endCall()});
  $('sidebarToggle')?.addEventListener('click',toggleSidebar);
  $('chatToggle')?.addEventListener('click',function(){chatPanel()});
  var usersModal=$('usersModal');
  if(usersModal){
    var bd=usersModal.querySelector('.backdrop');
    if(bd)bd.addEventListener('click',closeUsersModal);
    var cm=$('closeUsersModal');
    if(cm)cm.addEventListener('click',closeUsersModal);
  }
  var attachBtn=$('attachBtn');
  var fileInput=$('fileInput');
  if(attachBtn&&fileInput){
    attachBtn.addEventListener('click',function(){fileInput.click()});
    fileInput.addEventListener('change',async function(e){
      var file=e.target.files&&e.target.files[0];
      if(!file)return;
      if(file.size>10*1024*1024){alert('File too large');fileInput.value='';return}
      await sendFile(file);fileInput.value='';
    });
  }
  var closeChat=$('closeChat');
  if(closeChat)closeChat.addEventListener('click',function(){chatPanel(false)});
  var chatInput=$('chatInput');
  var chatSendBtn=$('chatSendBtn');
  if(chatInput&&chatSendBtn){
    chatSendBtn.addEventListener('click',function(){if(chatInput.value.trim()){sendChat(chatInput.value);chatInput.value='';S.replyTo=null;var p=$('replyPreview');if(p)p.remove()}});
    chatInput.addEventListener('keydown',function(e){if(e.key==='Enter'&&chatInput.value.trim()){e.preventDefault();sendChat(chatInput.value);chatInput.value='';S.replyTo=null;var p=$('replyPreview');if(p)p.remove()}});
    var _lastTyping=0;
    chatInput.addEventListener('input',function(){
      if(!S.peerConnected)return;
      var now=Date.now();
      if(typingTimer)clearTimeout(typingTimer);
      if(now-_lastTyping>2000){_lastTyping=now;broadcast({type:'typing',isTyping:true,name:S.name,sender:S.sid})}
      typingTimer=setTimeout(function(){broadcast({type:'typing',isTyping:false,name:S.name,sender:S.sid})},2000);
    });
  }
  document.addEventListener('click',function(e){
    var t=e.target;
    var actionBtn=t.closest('.msg-action-btn');
    if(actionBtn){
      var msgEl=actionBtn.closest('.chat-msg');
      var msgId=msgEl?.dataset?.msgId;
      var action=actionBtn.dataset.action;
      if(!msgId)return;
      var msg=S.msgs.find(function(m){return m.id===msgId});
      if(!msg)return;
      if(action==='reply'){S.replyTo={id:msgId,name:msg.authorName||msg.sender,text:msg.text||(msg.fileType?'📎 '+(msg.name||'File'):'')};showReplyPreview()}
      else if(action==='delete')showDeleteOptions(msgId,msg.sender===S.sid);
      else if(action==='react')showReactionPicker(msgId);
      return;
    }
    var reactionBtn=t.closest('.reaction-btn');
    if(reactionBtn){
      var msgId=reactionBtn.dataset.msgId;
      var reaction=reactionBtn.dataset.reaction;
      if(msgId&&reaction){handleSendReaction(msgId,reaction);document.querySelectorAll('.reaction-picker').forEach(function(el){el.remove()})}
      return;
    }
    var reactBadge=t.closest('.reaction-badge');
    if(reactBadge){
      var msgEl=reactBadge.closest('.chat-msg');
      var msgId=msgEl?.dataset?.msgId;
      var reaction=reactBadge.dataset.reaction;
      if(msgId&&reaction)handleSendReaction(msgId,reaction);
      return;
    }
    if(!t.closest('.msg-action-btn')&&!t.closest('.reaction-picker')&&!t.closest('.reaction-badge')){
      var dMsgEl=t.closest('.chat-msg');
      if(dMsgEl&&dMsgEl.dataset.msgId){
        var now=Date.now();
        if(dMsgEl._lastTap&&now-dMsgEl._lastTap<300){dMsgEl._lastTap=0;handleSendReaction(dMsgEl.dataset.msgId,'👍');dMsgEl.classList.add('double-tap-flash');setTimeout(function(){dMsgEl.classList.remove('double-tap-flash')},300);return}
        dMsgEl._lastTap=now;
      }
    }
  });
  var maxGrid=$(S.gridId);
  if(maxGrid)maxGrid.addEventListener('click',function(e){
    var tile=e.target.closest('.video-tile');
    if(tile)toggleMaximize(tile);
  });
  document.addEventListener('click',function(){resumeAudio()},{once:true});
  document.addEventListener('touchstart',function(){resumeAudio()},{once:true,passive:true});
  document.addEventListener('touchstart',function(e){
    var el=e.target.closest('.chat-msg');
    if(!el||e.target.closest('.msg-action-btn')||e.target.closest('.reaction-picker')||e.target.closest('.reaction-badge'))return;
    el._lpTimer=setTimeout(function(){showReactionPicker(el.dataset.msgId)},500);
  },{passive:true});
  document.addEventListener('touchend',function(e){
    var el=e.target.closest('.chat-msg');
    if(el&&el._lpTimer){clearTimeout(el._lpTimer);delete el._lpTimer}
  });
  document.addEventListener('touchmove',function(e){
    var el=e.target.closest('.chat-msg');
    if(el&&el._lpTimer){clearTimeout(el._lpTimer);delete el._lpTimer}
  },{passive:true});
  window.addEventListener('resize',function(){
    var modal=$('usersModal');
    if(modal)modal.classList.remove('open');
  });
  window.addEventListener('beforeunload',function(){if(S.transport){try{S.transport.disconnect()}catch(e){}}});
  window.addEventListener('pagehide',function(){if(S.transport){try{S.transport.disconnect()}catch(e){}}});
  if(S.room&&S.name){joinRoom();return}
  var joinBtn=$('joinBtn');
  var nameInput=$('nameInput');
  if(joinBtn&&nameInput){
    var doJoin=function(){
      var name=nameInput.value.trim();
      if(!name){$('joinError').textContent=i18n.t('join.error.name');return}
      S.name=name;
      sessionStorage.setItem('sardab-voice-name',name);
      sessionStorage.setItem('sardab-voice-sid',S.sid);
      joinRoom();
    };
    joinBtn.addEventListener('click',doJoin);
    nameInput.addEventListener('keydown',function(e){if(e.key==='Enter')doJoin()});
  }
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);
else init();
})();