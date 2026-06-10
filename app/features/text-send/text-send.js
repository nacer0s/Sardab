(function () {
  'use strict';

  var $ = function (id) { return document.getElementById(id); };

  var storeData  = $('store-data');
  var storePass  = $('store-pass');
  var btnStore   = $('btn-store');
  var vaultId    = $('vault-id');
  var vaultIdR   = $('vault-id-retrieve');
  var retrievePass = $('retrieve-pass');
  var btnRetrieve  = $('btn-retrieve');
  var retrieveData = $('retrieve-data');
  var status     = $('vault-status');
  var signalInd  = $('signal-indicator');
  var strengthF  = $('strength-fill');
  var strengthL  = $('strength-label');
  var storeTtl   = $('store-ttl');

  function setStatus(text, type) {
    if (status) {
      status.innerHTML = text;
      status.className = 'status-alert visible is-' + type;
    }
  }

  function clearStatus() {
    if (status) { status.classList.remove('visible'); status.textContent = ''; }
  }

  function setBusy(btn, busy) {
    if (btn) btn.disabled = busy;
  }

  var tabs = document.querySelectorAll('.tab-btn');
  tabs.forEach(function (btn) {
    btn.addEventListener('click', function () {
      tabs.forEach(function (b) { b.setAttribute('aria-selected', 'false'); });
      btn.setAttribute('aria-selected', 'true');
      var key = btn.dataset.section.replace('section-', '');
      document.querySelectorAll('.section').forEach(function (s) {
        s.setAttribute('aria-hidden', s.id !== 'section-' + key ? 'true' : 'false');
      });
      clearStatus();
    });
  });

  function checkSignal() {
    var id = vaultIdR ? vaultIdR.value.trim() : '';
    if (!id) { if (signalInd) signalInd.textContent = ''; return; }
    fetch('/app/api/signal.php?id=' + encodeURIComponent(id)).then(function (r) { return r.json(); }).then(function (d) {
      if (signalInd) {
        if (d.exists) signalInd.innerHTML = '<span style="color:var(--clr-green)">Vault exists</span>';
        else if (d.expired) signalInd.innerHTML = '<span style="color:var(--clr-amber)">Vault expired</span>';
        else signalInd.innerHTML = '<span style="color:var(--clr-text-muted)">Vault not found</span>';
      }
    }).catch(function () {});
  }

  var sigTimer = null;
  if (vaultIdR) {
    vaultIdR.addEventListener('input', function () {
      if (sigTimer) clearTimeout(sigTimer);
      sigTimer = setTimeout(checkSignal, 400);
    });
  }

  if (storePass && strengthF && strengthL) {
    storePass.addEventListener('input', function () {
      var v = storePass.value;
      var score = 0;
      if (v.length >= 8) score++; if (v.length >= 12) score++;
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
      if (/\d/.test(v)) score++;
      if (/[^a-zA-Z0-9]/.test(v)) score++;
      if (v.length >= 16) score++;
      var pct = (score / 6) * 100;
      strengthF.style.width = pct + '%';
      var labels = ['', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong', 'Excellent'];
      strengthL.textContent = labels[score] || '';
      strengthL.style.color = score < 3 ? 'var(--clr-danger)' : (score < 5 ? 'var(--clr-amber)' : 'var(--clr-green)');
    });
  }

  if (btnStore) {
    btnStore.addEventListener('click', async function () {
      var data = storeData ? storeData.value.trim() : '';
      var pass = storePass ? storePass.value.trim() : '';
      var id   = vaultId ? vaultId.value.trim() : '';
      var ttl  = storeTtl ? storeTtl.value : '';

      if (!data || !pass || !id) {
        setStatus('Fill in all fields', 'error');
        return;
      }

      setBusy(btnStore, true);
      btnStore.innerHTML = '<i class="fa-solid fa-spinner fa-spin btn-icon"></i> Encrypting…';

      try {
        var ciphertext = await Crypto.encryptText(data, pass);
        btnStore.innerHTML = '<i class="fa-solid fa-cloud-arrow-up btn-icon"></i> Uploading…';

        var resp = await fetch('/app/api/vault.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ data: ciphertext, id: id, ttl: ttl || null })
        });
        var result = await resp.json();

        if (result.ok) {
          var link = window.location.origin + '/v/' + encodeURIComponent(id);
          setStatus('Vault stored! <a href="' + link + '" style="text-decoration:underline;color:var(--clr-green);">' + link + '</a>', 'ok');
          storeData.value = '';
          storePass.value = '';
          if (strengthF) strengthF.style.width = '0%';
          if (strengthL) strengthL.textContent = '';
        } else {
          setStatus(result.error || 'Store failed', 'error');
        }
      } catch (e) {
        setStatus('Error: ' + e.message, 'error');
      }

      setBusy(btnStore, false);
      btnStore.innerHTML = '<i class="fa-solid fa-lock btn-icon"></i> Encrypt &amp; Store';
    });
  }

  if (btnRetrieve) {
    var _retrievedData = null;

    btnRetrieve.addEventListener('click', async function () {
      var id   = vaultIdR ? vaultIdR.value.trim() : '';
      var pass = retrievePass ? retrievePass.value.trim() : '';

      if (!id || !pass) {
        setStatus('Fill in Vault ID and Passphrase', 'error');
        return;
      }

      // If already decrypted and showing, burn it now
      if (_retrievedData) {
        setBusy(btnRetrieve, true);
        btnRetrieve.innerHTML = '<i class="fa-solid fa-spinner fa-spin btn-icon"></i> Burning…';
        try {
          await fetch('/app/api/vault.php?id=' + encodeURIComponent(id) + '&burn=1');
        } catch (e) {}
        setStatus('<i class="fa-solid fa-check-circle"></i> Data destroyed on server.', 'ok');
        _retrievedData = null;
        if (retrieveData) retrieveData.value = '';
        btnRetrieve.innerHTML = '<i class="fa-solid fa-eye btn-icon"></i> Decrypt &amp; View Once';
        setBusy(btnRetrieve, false);
        return;
      }

      setBusy(btnRetrieve, true);
      btnRetrieve.innerHTML = '<i class="fa-solid fa-spinner fa-spin btn-icon"></i> Decrypting…';

      try {
        var resp = await fetch('/app/api/vault.php?id=' + encodeURIComponent(id));
        var result = await resp.json();

        if (!result.ok) {
          setStatus(result.error || 'Vault not found', 'error');
          setBusy(btnRetrieve, false);
          btnRetrieve.innerHTML = '<i class="fa-solid fa-eye btn-icon"></i> Decrypt &amp; View Once';
          return;
        }

        btnRetrieve.innerHTML = '<i class="fa-solid fa-unlock btn-icon"></i> Decrypted';
        var plaintext = await Crypto.decryptText(result.data, pass);
        if (retrieveData) retrieveData.value = plaintext;
        _retrievedData = plaintext;

        // Burn the server copy now that decryption succeeded
        fetch('/app/api/vault.php?id=' + encodeURIComponent(id) + '&burn=1').catch(function () {});
        setStatus('<i class="fa-solid fa-check-circle"></i> Decrypted! Data destroyed on server.', 'ok');

        btnRetrieve.innerHTML = '<i class="fa-solid fa-trash-can btn-icon"></i> Clear &amp; Burn';
      } catch (e) {
        setStatus('Decryption failed — wrong passphrase? You can retry.', 'error');
        _retrievedData = null;
      }

      setBusy(btnRetrieve, false);
    });
  }

  if (vaultId) {
    vaultId.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); if (storePass) storePass.focus(); }
    });
  }
  if (storePass) {
    storePass.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); if (btnStore) btnStore.click(); }
    });
  }
  if (vaultIdR) {
    vaultIdR.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); if (retrievePass) retrievePass.focus(); }
    });
  }
  if (retrievePass) {
    retrievePass.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); if (btnRetrieve) btnRetrieve.click(); }
    });
  }

  var mode = document.body.dataset.mode;
  var presetId = document.body.dataset.vaultId || '';
  if (mode === 'retrieve' && presetId && vaultIdR) {
    vaultIdR.value = presetId;
    vaultIdR.readOnly = true;
    var tab = document.querySelector('.tab-btn[data-section="section-retrieve"]');
    if (tab) tab.click();
    checkSignal();
  }

})();