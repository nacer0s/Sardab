/**
 * Sardab Shared Crypto Module
 * AES-256-GCM encryption with PBKDF2 key derivation.
 * Used by all 4 apps for end-to-end encryption.
 */
(function() {
  'use strict';
  if (window.SardabCrypto) return;
  window.SardabCrypto = true;

  const SALT = new TextEncoder().encode('sardab-qc-salt-v2');
  const PBKDF2_ITERATIONS = 200000;
  const KEY_BITS = 256;
  const IV_LENGTH = 12;

  window.Crypto = {
    async deriveKey(code) {
      const enc = new TextEncoder();
      const mk = await crypto.subtle.importKey('raw', enc.encode(code + ':sardab:q1'), 'PBKDF2', false, ['deriveKey']);
      return crypto.subtle.deriveKey(
        { name: 'PBKDF2', salt: SALT, iterations: PBKDF2_ITERATIONS, hash: 'SHA-512' },
        mk,
        { name: 'AES-GCM', length: KEY_BITS },
        false,
        ['encrypt', 'decrypt']
      );
    },

    async encrypt(key, data) {
      const iv = crypto.getRandomValues(new Uint8Array(IV_LENGTH));
      const pt = new TextEncoder().encode(typeof data === 'string' ? data : JSON.stringify(data));
      const ct = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, key, pt);
      const out = new Uint8Array(IV_LENGTH + ct.byteLength);
      out.set(iv);
      out.set(new Uint8Array(ct), IV_LENGTH);
      let bin = '';
      for (let i = 0; i < out.length; i++) bin += String.fromCharCode(out[i]);
      return btoa(bin);
    },

    async decrypt(key, b64) {
      const bin = atob(b64);
      const raw = new Uint8Array(bin.length);
      for (let i = 0; i < bin.length; i++) raw[i] = bin.charCodeAt(i);
      const iv = raw.slice(0, IV_LENGTH);
      const ct = raw.slice(IV_LENGTH);
      const d = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, key, ct);
      return new TextDecoder().decode(d);
    },

    async encryptObj(key, obj) {
      return Crypto.encrypt(key, JSON.stringify(obj));
    },

    async decryptObj(key, b64) {
      const str = await Crypto.decrypt(key, b64);
      return JSON.parse(str);
    }
  };
})();
