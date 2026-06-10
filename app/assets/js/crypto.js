(function () {
  'use strict';

  var ITERATIONS = 600000;

  function toBase64(buf) {
    var b = '';
    var bytes = new Uint8Array(buf);
    for (var i = 0; i < bytes.length; i++) b += String.fromCharCode(bytes[i]);
    return btoa(b);
  }

  function fromBase64(str) {
    var bin = atob(str);
    var bytes = new Uint8Array(bin.length);
    for (var i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
    return bytes;
  }

  function strToBuf(s) { return new TextEncoder().encode(s); }
  function bufToStr(b) { return new TextDecoder().decode(b); }

  async function deriveKey(passphrase, salt) {
    var km = await crypto.subtle.importKey('raw', strToBuf(passphrase), 'PBKDF2', false, ['deriveKey']);
    return crypto.subtle.deriveKey(
      { name: 'PBKDF2', salt: salt, iterations: ITERATIONS, hash: 'SHA-256' },
      km,
      { name: 'AES-GCM', length: 256 },
      false,
      ['encrypt', 'decrypt']
    );
  }

  async function encryptText(plaintext, passphrase) {
    var salt = crypto.getRandomValues(new Uint8Array(16));
    var iv   = crypto.getRandomValues(new Uint8Array(12));
    var key  = await deriveKey(passphrase, salt);
    var ct   = await crypto.subtle.encrypt({ name: 'AES-GCM', iv: iv }, key, strToBuf(plaintext));
    var combined = new Uint8Array(salt.length + iv.length + ct.byteLength);
    combined.set(salt, 0);
    combined.set(iv, salt.length);
    combined.set(new Uint8Array(ct), salt.length + iv.length);
    return toBase64(combined);
  }

  async function decryptText(ciphertext, passphrase) {
    var combined = fromBase64(ciphertext);
    var salt = combined.slice(0, 16);
    var iv   = combined.slice(16, 28);
    var ct   = combined.slice(28);
    var key  = await deriveKey(passphrase, salt);
    var plain = await crypto.subtle.decrypt({ name: 'AES-GCM', iv: iv }, key, ct);
    return bufToStr(plain);
  }

  window.Crypto = {
    encryptText: encryptText,
    decryptText: decryptText,
    toBase64: toBase64,
    fromBase64: fromBase64
  };

})();
