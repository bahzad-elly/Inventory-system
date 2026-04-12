(function() {
  const root = document.documentElement;
  const body = document.body;

  // ---------- THEME HANDLING ----------
  const btnLight = document.getElementById('btnLight');
  const btnDark = document.getElementById('btnDark');

  function setTheme(theme) {
    root.setAttribute('data-theme', theme);
    localStorage.setItem('inventory-theme', theme);
    
    if (btnLight && btnDark) {
      if (theme === 'dark') {
        btnDark.classList.add('active');
        btnLight.classList.remove('active');
      } else {
        btnLight.classList.add('active');
        btnDark.classList.remove('active');
      }
    }
  }

  if (btnLight) btnLight.addEventListener('click', () => setTheme('light'));
  if (btnDark) btnDark.addEventListener('click', () => setTheme('dark'));

  // ---------- LANGUAGE HANDLING ----------
  const btnEn = document.getElementById('btnEn');
  const btnKu = document.getElementById('btnKu');

  function setLanguage(lang) {
    if (lang === 'ku') {
      body.classList.add('ku');
    } else {
      body.classList.remove('ku');
    }
    localStorage.setItem('inventory-lang', lang);
    
    if (btnEn && btnKu) {
      if (lang === 'ku') {
        btnKu.classList.add('active');
        btnEn.classList.remove('active');
      } else {
        btnEn.classList.add('active');
        btnKu.classList.remove('active');
      }
    }
  }

  if (btnEn) btnEn.addEventListener('click', () => setLanguage('en'));
  if (btnKu) btnKu.addEventListener('click', () => setLanguage('ku'));

  // ---------- INITIAL LOAD ----------
  const savedTheme = localStorage.getItem('inventory-theme') || 'light';
  setTheme(savedTheme);

  const savedLang = localStorage.getItem('inventory-lang') || 'en';
  setLanguage(savedLang);

})();
