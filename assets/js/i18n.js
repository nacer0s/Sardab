(function(){
const LANG = localStorage.getItem('sardab-lang');
const translations = {
  en: {
    'brand.name': 'Sardab',
    'nav.features': 'Features',
    'nav.security': 'Security',
    'nav.faq': 'FAQ',
    'nav.app': 'Launch App',
    'encrypted': 'Encrypted',
    'copy': 'Copy Link',
    'copied': 'Copied!',
    'lobby.redirecting': 'Connected! Entering room...',
    'footer.tag': 'Full Encryption',
    'footer.zero': 'Zero Knowledge',
    'footer.direct': 'Direct Connection',
    'footer.rights': 'All rights reserved.',
    'footer.by': 'Built by',
    'join.error.name': 'Please enter your name',
    'join.error.code': 'Please enter a room code',
    'join.error.notfound': 'Room not found',
    'create.error.name': 'Please enter your name',
    'create.error.failed': 'Failed to create room',
    'placeholder.name': 'Your name',
    'placeholder.roomcode': 'XXXXXXXXXXXXXXXXXXXX',
    'msg.placeholder': 'Type a message...',
    'chat.typing': '{name} is typing...'
  },
  ar: {
    'brand.name': 'سرداب',
    'nav.features': 'المميزات',
    'nav.security': 'الأمان',
    'nav.faq': 'الأسئلة',
    'nav.app': 'الدخول للتطبيق',
    'encrypted': 'مشفر',
    'copy': 'نسخ الرابط',
    'copied': 'تم النسخ',
    'lobby.redirecting': 'تم الاتصال! جارٍ دخول الغرفة...',
    'footer.tag': 'تشفير كامل',
    'footer.zero': 'صفر معرفة',
    'footer.direct': 'اتصال مباشر',
    'footer.rights': 'جميع الحقوق محفوظة.',
    'footer.by': 'ببناء',
    'join.error.name': 'الرجاء إدخال اسمك',
    'join.error.code': 'الرجاء إدخال رمز الغرفة',
    'join.error.notfound': 'الغرفة غير موجودة',
    'create.error.name': 'الرجاء إدخال اسمك',
    'create.error.failed': 'فشل إنشاء الغرفة',
    'placeholder.name': 'اسمك',
    'placeholder.roomcode': 'XXXXXXXXXXXXXXXXXXXX',
    'msg.placeholder': 'اكتب رسالة...',
    'chat.typing': '{name} يكتب...'
  }
};
let current = LANG === 'ar' ? 'ar' : 'en';

function apply() {
  const tr = translations[current];
  if (!tr) return;
  document.documentElement.dir = current === 'ar' ? 'rtl' : 'ltr';
  document.documentElement.lang = current;
  document.querySelectorAll('[data-i18n]').forEach(el => {
    const key = el.dataset.i18n;
    const text = tr[key];
    if (text !== undefined) {
      if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
        el.placeholder = text;
      } else if (el.tagName === 'TITLE') {
        document.title = text;
      } else {
        el.textContent = text;
      }
    }
  });
  document.querySelectorAll('[data-i18n-html]').forEach(el => {
    const key = el.dataset.i18nHtml;
    const text = tr[key];
    if (text !== undefined) el.innerHTML = text;
  });
  document.querySelectorAll('[data-i18n-title]').forEach(el => {
    const key = el.dataset.i18nTitle;
    const text = tr[key];
    if (text !== undefined) el.title = text;
  });
  document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
    const key = el.dataset.i18nPlaceholder;
    const text = tr[key];
    if (text !== undefined) el.placeholder = text;
  });
  const btns = document.querySelectorAll('.lang-toggle');
  btns.forEach(b => { b.textContent = current === 'ar' ? 'EN' : 'AR'; });
}

const i18n = {
  get lang() { return current; },
  setTranslations(tr) {
    for (const lang in tr) {
      if (!translations[lang]) translations[lang] = {};
      Object.assign(translations[lang], tr[lang]);
    }
  },
  t(key, params) {
    let s = translations[current]?.[key] ?? key;
    if (params) for (const k in params) s = s.replace(new RegExp('\\{' + k + '\\}', 'g'), params[k]);
    return s;
  },
  switch(lang) {
    if (lang === current) return;
    current = lang;
    localStorage.setItem('sardab-lang', lang);
    apply();
  },
  init() {
    apply();
    document.addEventListener('click', e => {
      const btn = e.target.closest('.lang-toggle');
      if (btn) i18n.switch(current === 'ar' ? 'en' : 'ar');
    });
  }
};

window.i18n = i18n;
document.addEventListener('DOMContentLoaded', () => i18n.init());
})();
