(function(){
  var menuBtn = document.getElementById('menuBtn');
  var dropdown = document.getElementById('menuDropdown');
  var loginItem = document.getElementById('loginItem');
  var logoutItem = document.getElementById('logoutItem');
  var topbarUsername = document.getElementById('topbarUsername');
  var usernameFull = document.getElementById('usernameFull');
  var usernameInitials = document.getElementById('usernameInitials');
  var heroLoginBtn = document.querySelector('.hero-cta .btn-primary');
  var caricaItem = document.getElementById('caricaItem'); // voce "Carica materiali" del menu
  var exploreBtn = document.getElementById('exploreBtn');
  var exploreBtnTestoBase = exploreBtn ? exploreBtn.textContent : '';

  function getInitials(name){
    return name.split(' ').map(function(p){ return p.charAt(0).toUpperCase(); }).join('');
  }

  function setMenuOpen(open){
    dropdown.classList.toggle('open', open);
    menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  // true quando un utente ha effettuato il login; usato anche dal pulsante dell'hero
  var collegato = false;

  // aggiornano topbar e menu dopo login, logout o al caricamento della pagina
  function mostraUtente(nome, cognome){
    collegato = true;
    var nomeCompleto = nome + ' ' + cognome;
    loginItem.textContent = nomeCompleto;
    loginItem.classList.add('user');
    logoutItem.hidden = false;
    usernameFull.textContent = nomeCompleto;
    usernameInitials.textContent = getInitials(nomeCompleto);
    topbarUsername.hidden = false;
    if (heroLoginBtn) {
      heroLoginBtn.textContent = 'Carica materiali';
      heroLoginBtn.classList.add('carica');
      heroLoginBtn.setAttribute('href', 'area.html'); // da collegato porta a "La tua area"
    }
    if (exploreBtn) {
      exploreBtn.textContent = 'Ciao ' + nome + ', esplora i materiali';
    }
    // lo sanno anche gli altri script della pagina (es. js/main_area.js)
    document.dispatchEvent(new CustomEvent('utente-collegato', { detail: { nome: nome, cognome: cognome } }));
  }
  function mostraOspite(){
    collegato = false;
    loginItem.textContent = 'Login';
    loginItem.classList.remove('user');
    logoutItem.hidden = true;
    topbarUsername.hidden = true;
    usernameFull.textContent = '';
    usernameInitials.textContent = '';
    if (heroLoginBtn) {
      heroLoginBtn.textContent = 'Accedi';
      heroLoginBtn.classList.remove('carica');
      heroLoginBtn.setAttribute('href', '#');
    }
    if (exploreBtn) {
      exploreBtn.textContent = exploreBtnTestoBase;
    }
    document.dispatchEvent(new CustomEvent('utente-scollegato'));
  }

  // ===== Contatore "materiali condivisi" della home =====
  // Il numero arriva dal database (api/statistiche.php). Finche' non arriva, o se il
  // server non risponde, resta il trattino scritto in home.html.
  var statMateriali = document.getElementById('statMateriali');
  if (statMateriali) {
    fetch('api/statistiche.php')
      .then(function(risposta){ return risposta.json(); })
      .then(function(dati){
        if (!dati.successo) return;
        var n = dati.materiali;
        var numero = document.createElement('b');
        numero.textContent = n;
        statMateriali.textContent = ''; // textContent e nodi di testo: niente innerHTML
        statMateriali.appendChild(numero);
        statMateriali.appendChild(document.createTextNode(n === 1 ? ' materiale condiviso' : ' materiali condivisi'));
      })
      .catch(function(){
        console.error('Conteggio dei materiali non riuscito: server non raggiungibile.');
      });
  }

  // al caricamento: se la sessione PHP e' ancora aperta, l'utente resta collegato
  fetch('api/sessione.php')
    .then(function(risposta){ return risposta.json(); })
    .then(function(dati){
      // in tutti e due i casi l'evento avvisa anche gli altri script (es. main_area.js)
      if (dati.collegato) {
        mostraUtente(dati.nome, dati.cognome);
      } else {
        mostraOspite();
      }
    })
    .catch(function(){
      console.error('Verifica della sessione non riuscita: server non raggiungibile.');
      // gli altri script non aspettano invano (es. main_area.js mostra un messaggio)
      document.dispatchEvent(new CustomEvent('sessione-non-verificata'));
    });

  menuBtn.addEventListener('click', function(e){
    e.stopPropagation();
    setMenuOpen(!dropdown.classList.contains('open'));
  });
  document.addEventListener('click', function(e){
    if (!e.target.closest('.menu-wrap')) setMenuOpen(false);
  });

  logoutItem.addEventListener('click', function(){
    fetch('api/logout.php', { method: 'POST' })
      .then(function(risposta){ return risposta.json(); })
      .then(function(dati){
        if (!dati.successo) return;
        // mostraOspite() avvisa anche gli altri script (evento "utente-scollegato"):
        // in area.html e' js/main_area.js a riportare alla home
        mostraOspite();
        setMenuOpen(false);
      })
      .catch(function(){
        console.error('Logout non riuscito: server non raggiungibile.');
      });
  });

  // ===== Login modal =====
  var backdrop = document.getElementById('loginBackdrop');
  var modalClose = document.getElementById('modalClose');
  var authForm = document.getElementById('authForm');
  var passwordInput = document.getElementById('authPassword');
  var togglePassword = document.getElementById('togglePassword');
  var togglePasswordIcon = togglePassword.querySelector('img');
  var errorEl = document.getElementById('authError');
  var submitBtn = document.getElementById('authSubmit');

  function setPasswordVisible(visible){
    passwordInput.type = visible ? 'text' : 'password';
    togglePasswordIcon.src = visible ? 'img/icon-eye-off.svg' : 'img/icon-eye.svg';
    togglePassword.setAttribute('aria-label', visible ? 'Nascondi password' : 'Mostra password');
    togglePassword.setAttribute('aria-pressed', visible ? 'true' : 'false');
  }
  togglePassword.addEventListener('click', function(){
    setPasswordVisible(passwordInput.type === 'password');
    passwordInput.focus();
  });

  function openModal(){
    backdrop.classList.add('open');
    setMenuOpen(false);
  }
  function closeModal(){
    backdrop.classList.remove('open');
    // svuota i campi: dopo il login (o un tentativo fallito) email e password
    // non devono restare nel form per chi apre la finestra dopo
    authForm.reset();
    errorEl.hidden = true;
    setPasswordVisible(false);
  }

  loginItem.addEventListener('click', function(e){
    // da loggato e' un link vuoto (#), come il nome nella topbar: per ora
    // non apre piu' la finestra di login.
    if (collegato) return;
    e.preventDefault();
    openModal();
  });
  if (heroLoginBtn) {
    heroLoginBtn.addEventListener('click', function(e){
      // da loggato il pulsante è "Carica materiali" e il suo href porta ad area.html:
      // lasciamo seguire il link. Da ospite apre la finestra di login.
      if (collegato) return;
      e.preventDefault();
      openModal();
    });
  }
  if (caricaItem) {
    caricaItem.addEventListener('click', function(e){
      // il link porta ad area.html, che serve a chi e' collegato: gli altri fanno prima il login
      if (collegato) return;
      e.preventDefault();
      openModal();
    });
  }
  modalClose.addEventListener('click', closeModal);
  backdrop.addEventListener('click', function(e){
    if (e.target === backdrop) closeModal();
  });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') {
      closeModal();
      setMenuOpen(false);
    }
  });

  authForm.addEventListener('submit', function(e){
    e.preventDefault();
    var email = document.getElementById('authEmail').value;
    var password = passwordInput.value;

    submitBtn.disabled = true;
    // POST: email e password viaggiano nel corpo della richiesta (body),
    // non nell'indirizzo. In F12 > Rete si vedono in "Payload", non nell'URL.
    fetch('api/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'email=' + encodeURIComponent(email) + '&password=' + encodeURIComponent(password)
    })
      .then(function(risposta){ return risposta.json(); })
      .then(function(dati){
        if (!dati.successo) {
          errorEl.textContent = dati.errore;
          errorEl.hidden = false;
          return;
        }
        mostraUtente(dati.nome, dati.cognome);
        closeModal();
      })
      .catch(function(){
        errorEl.textContent = 'Errore di connessione al server.';
        errorEl.hidden = false;
      })
      .finally(function(){
        submitBtn.disabled = false;
      });
  });
})();
