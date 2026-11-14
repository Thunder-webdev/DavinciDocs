(function(){
  // I materiali arrivano da api/materiali.php (vedi caricaMateriali): ognuno ha l'id del
  // database, che serve per scaricarlo ed eliminarlo.
  var materiali = [];
  // Finche' il server non risponde non si disegna nulla: altrimenti, per un attimo,
  // comparirebbe il messaggio "non hai ancora caricato nessun materiale" anche a chi ne ha.
  var elencoPronto = false;
  var erroreElenco = '';

  var lista = document.getElementById('areaList');

  // nome dell'utente collegato, annunciato da js/main.js (evento "utente-collegato"):
  // serve al messaggio dell'elenco vuoto. L'evento arriva dopo il caricamento della
  // pagina (la sessione si chiede al server), per questo l'elenco si ridisegna.
  var nomeUtente = '';
  var ERRORE_SERVER = 'Impossibile caricare i materiali: server non raggiungibile.';

  // aggiorna i dati dell'elenco e lo ridisegna
  function mostraElenco(nuoviMateriali, errore){
    materiali = nuoviMateriali;
    erroreElenco = errore;
    elencoPronto = true;
    disegna();
  }

  document.addEventListener('utente-collegato', function(e){
    nomeUtente = e.detail.nome;
    caricaMateriali();
  });
  // Nessun utente collegato (ospite che apre la pagina dall'indirizzo, oppure logout):
  // "La tua area" serve solo a chi e' collegato, si torna alla home.
  // replace() e non href: la pagina non resta nella cronologia, cosi' il tasto
  // "Indietro" non riporta qui (sarebbe rimandato subito alla home).
  document.addEventListener('utente-scollegato', function(){
    window.location.replace('home.html');
  });
  // il controllo della sessione e' fallito (server spento o in errore): senza questo
  // l'elenco resterebbe bianco, in attesa di una risposta che non arriva
  document.addEventListener('sessione-non-verificata', function(){
    mostraElenco([], ERRORE_SERVER);
  });

  // dalla risposta del server (dimensione in KB) a quello che mostra la scheda
  function daServer(m){
    return {
      id: m.id, titolo: m.titolo, categoria: m.categoria, indirizzo: m.indirizzo,
      materia: m.materia, anno: m.anno, nomeFile: m.nome_file, tipo: m.tipo, data: m.data,
      dimensione: formattaDimensione(m.dimensione_kb)
    };
  }

  function caricaMateriali(){
    fetch('api/materiali.php')
      .then(function(risposta){ return risposta.json(); })
      .then(function(dati){
        if (dati.successo) {
          mostraElenco(dati.materiali.map(daServer), '');
        } else {
          mostraElenco([], dati.errore);
        }
      })
      .catch(function(){
        mostraElenco([], ERRORE_SERVER);
      });
  }

  // KB -> "95 KB" / "2.1 MB"
  function formattaDimensione(kb){
    if (kb < 1024) return kb + ' KB';
    return (kb / 1024).toFixed(1) + ' MB';
  }

  function creaCard(materiale){
    var card = document.createElement('div');
    card.className = 'material-card';

    var tipo = document.createElement('div');
    tipo.className = 'material-tipo';
    tipo.textContent = materiale.tipo;

    var info = document.createElement('div');
    info.className = 'material-info';
    var titolo = document.createElement('h3');
    titolo.textContent = materiale.titolo;
    titolo.title = materiale.titolo; // titolo intero al passaggio del mouse, se in scheda e' tagliato
    var nomeFile = document.createElement('p');
    nomeFile.className = 'material-file';
    nomeFile.textContent = materiale.nomeFile;
    nomeFile.title = materiale.nomeFile; // nome intero al passaggio del mouse, se in scheda e' tagliato
    var meta = document.createElement('p');
    meta.className = 'material-meta';
    meta.textContent = materiale.categoria + ' · ' + materiale.materia + ' · ' + materiale.data + ' · ' + materiale.dimensione;
    info.appendChild(titolo);
    info.appendChild(meta);
    info.appendChild(nomeFile);

    var azioni = document.createElement('div');
    azioni.className = 'material-azioni';

    var scarica = document.createElement('button');
    scarica.type = 'button';
    scarica.className = 'azione-btn azione-scarica';
    scarica.setAttribute('aria-label', 'Scarica ' + materiale.titolo);
    scarica.dataset.id = materiale.id;
    scarica.innerHTML =
      '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">' +
      '<path d="M9 2.5v8.2m0 0-3.3-3.3M9 10.7l3.3-3.3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>' +
      '<path d="M3 13v1a1.5 1.5 0 0 0 1.5 1.5h9A1.5 1.5 0 0 0 15 14v-1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>' +
      '</svg>';

    var elimina = document.createElement('button');
    elimina.type = 'button';
    elimina.className = 'azione-btn azione-elimina';
    elimina.setAttribute('aria-label', 'Elimina ' + materiale.titolo);
    elimina.dataset.id = materiale.id;
    elimina.innerHTML =
      '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">' +
      '<path d="M3.5 5.2h11M7.3 5.2V3.6a1 1 0 0 1 1-1h1.4a1 1 0 0 1 1 1v1.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>' +
      '<path d="M5.1 5.2l.6 8.9a1 1 0 0 0 1 .9h4.6a1 1 0 0 0 1-.9l.6-8.9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>' +
      '<path d="M7.6 7.8v4M10.4 7.8v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>' +
      '</svg>';

    azioni.appendChild(scarica);
    azioni.appendChild(elimina);

    card.appendChild(tipo);
    card.appendChild(info);
    card.appendChild(azioni);
    return card;
  }

  // Il download parte da api/scarica.php, che risponde con il file come allegato: la
  // pagina non cambia. Un link con l'attributo "download" e' il modo piu' semplice.
  function scarica(id){
    var a = document.createElement('a');
    a.href = 'api/scarica.php?id=' + encodeURIComponent(id);
    a.download = '';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  }

  function disegna(){
    lista.innerHTML = '';
    lista.classList.remove('vuota'); // la classe centra il messaggio quando non ci sono schede
    if (!elencoPronto) return; // il server non ha ancora risposto
    if (erroreElenco) {
      lista.classList.add('vuota');
      var errore = document.createElement('div');
      errore.className = 'area-vuota';
      var testoErrore = document.createElement('p');
      testoErrore.textContent = erroreElenco;
      errore.appendChild(testoErrore);
      lista.appendChild(errore);
      return;
    }
    if (materiali.length === 0) {
      lista.classList.add('vuota');
      var vuota = document.createElement('div');
      vuota.className = 'area-vuota';
      // textContent e nodi di testo, mai innerHTML: il nome arriva dal database.
      // Il nome in un <span> a parte, per colorarlo (colore 4 in style_area.css).
      // (Gli ospiti non arrivano qui: sono rimandati alla home.)
      var messaggio = document.createElement('p');
      var nome = document.createElement('span');
      nome.className = 'nome-utente';
      nome.textContent = nomeUtente;
      messaggio.appendChild(document.createTextNode('Ciao '));
      messaggio.appendChild(nome);
      messaggio.appendChild(document.createTextNode(', non hai ancora caricato nessun materiale.'));
      messaggio.appendChild(document.createElement('br'));
      messaggio.appendChild(document.createTextNode('Inizia a caricare i tuoi materiali!'));
      vuota.appendChild(messaggio);
      lista.appendChild(vuota);
      return;
    }
    materiali.forEach(function(materiale){
      lista.appendChild(creaCard(materiale));
    });
  }

  lista.addEventListener('click', function(e){
    var elimina = e.target.closest('.azione-elimina');
    if (elimina) {
      apriConferma(Number(elimina.dataset.id));
      return;
    }
    var scaricaBtn = e.target.closest('.azione-scarica');
    if (scaricaBtn) {
      scarica(Number(scaricaBtn.dataset.id));
    }
  });

  // ===== Switch vista elenco / griglia =====
  var vistaElencoBtn = document.getElementById('vistaElenco');
  var vistaGrigliaBtn = document.getElementById('vistaGriglia');

  function impostaVista(griglia){
    lista.classList.toggle('griglia', griglia);
    vistaElencoBtn.setAttribute('aria-pressed', String(!griglia));
    vistaGrigliaBtn.setAttribute('aria-pressed', String(griglia));
  }

  vistaElencoBtn.addEventListener('click', function(){ impostaVista(false); });
  vistaGrigliaBtn.addEventListener('click', function(){ impostaVista(true); });

  // ===== Finestra "Carica materiale" =====
  var backdrop = document.getElementById('uploadBackdrop');
  var openBtn = document.getElementById('openUpload');
  var closeBtn = document.getElementById('uploadClose');
  var form = document.getElementById('uploadForm');
  var fileInput = document.getElementById('uploadFile');
  var fileBtn = document.getElementById('uploadFileBtn');
  var fileNome = document.getElementById('uploadFileNome');
  var indirizzoSelect = document.getElementById('uploadIndirizzo');
  var materiaSelect = document.getElementById('uploadMateria');
  var annoSelect = document.getElementById('uploadAnno');
  var categoriaSelect = document.getElementById('uploadCategoria');
  var titoloInput = document.getElementById('uploadTitolo');
  var descrizioneInput = document.getElementById('uploadDescrizione');
  var submitBtn = document.getElementById('uploadSubmit');
  var erroreEl = document.getElementById('uploadErrore');

  // Formati ammessi e dimensione massima: la stessa lista e' in api/carica.php, dove
  // il controllo vale davvero; qui serve solo ad avvisare subito, senza inviare il file.
  var ESTENSIONI_AMMESSE = [
    'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'odt', 'odp', 'ods',
    'zip', 'png', 'jpg', 'jpeg',
    'svg', 'ps', 'ai', 'indd',
    'dwg', 'dxf', 'skp',
    'c', 'cpp', 'java', 'py', 'json', 'html', 'css', 'js'
  ];
  var DIMENSIONE_MAX = 20 * 1024 * 1024; // 20 MB
  // la finestra di selezione file propone solo questi formati
  fileInput.accept = ESTENSIONI_AMMESSE.map(function(e){ return '.' + e; }).join(',');

  function mostraErrore(testo){
    erroreEl.textContent = testo;
    erroreEl.hidden = false;
  }

  function mostraNomeFile(testo, errore){
    fileNome.textContent = testo;
    fileNome.classList.toggle('errore', errore);
  }

  // ----- Menu Indirizzo > Materia > Anno, riempiti da api/materie.php -----
  // [{ id, nome, materie:[{ id, nome, anni:[3,4,5] }] }, ...]: quello che il docente collegato puo' scegliere
  var indirizziDocente = [];

  // svuota un menu e lo riempie: "voci" = [{ valore, testo }], "segnaposto" = prima riga non sceglibile
  function riempiSelect(select, voci, segnaposto, abilitato){
    select.innerHTML = '';
    var primo = document.createElement('option');
    primo.value = '';
    primo.disabled = true;
    primo.selected = true;
    primo.textContent = segnaposto;
    select.appendChild(primo);
    voci.forEach(function(v){
      var o = document.createElement('option');
      o.value = v.valore;
      o.textContent = v.testo;
      select.appendChild(o);
    });
    select.disabled = !abilitato;
  }

  function azzeraMateria(){ riempiSelect(materiaSelect, [], 'Prima scegli l\'indirizzo', false); }
  function azzeraAnno(){ riempiSelect(annoSelect, [], 'Prima scegli la materia', false); }
  // Materia e Anno tornano disattivati finche' non si sceglie l'indirizzo
  function azzeraDipendenti(){ azzeraMateria(); azzeraAnno(); }

  // Un messaggio (errore, elenco vuoto) va nei menu Categoria e Indirizzo, che restano
  // abilitati: cosi' "required" blocca comunque l'invio. Materia e Anno restano disattivati.
  function mostraMessaggioMenu(messaggio){
    indirizziDocente = [];
    riempiSelect(categoriaSelect, [], messaggio, true);
    riempiSelect(indirizzoSelect, [], messaggio, true);
    azzeraDipendenti();
  }

  // chiamata a ogni apertura della finestra: cosi' funziona anche se il login
  // avviene dopo il caricamento della pagina
  function caricaOpzioni(){
    mostraMessaggioMenu('Caricamento...');
    fetch('api/materie.php')
      .then(function(risposta){ return risposta.json(); })
      .then(function(dati){
        if (!dati.successo) {
          mostraMessaggioMenu(dati.errore);
          return;
        }
        // categorie dalla tabella CATEGORIA (value = id)
        riempiSelect(categoriaSelect, dati.categorie.map(function(c){
          return { valore: c.id, testo: c.nome };
        }), 'Scegli la categoria', true);
        indirizziDocente = dati.indirizzi;
        if (indirizziDocente.length === 0) {
          mostraMessaggioMenu('Nessun indirizzo disponibile');
          return;
        }
        riempiSelect(indirizzoSelect, indirizziDocente.map(function(i){
          return { valore: i.id, testo: i.nome };
        }), 'Scegli l\'indirizzo', true);
        azzeraDipendenti();
      })
      .catch(function(){
        mostraMessaggioMenu('Server non raggiungibile');
      });
  }

  function indirizzoScelto(){
    return indirizziDocente.filter(function(i){ return String(i.id) === indirizzoSelect.value; })[0];
  }

  // scelto l'indirizzo, il menu Materia offre solo le materie del docente presenti in quell'indirizzo
  indirizzoSelect.addEventListener('change', function(){
    var ind = indirizzoScelto();
    if (!ind) return;
    // un indirizzo senza materie del docente resta sceglibile: il messaggio sta in un
    // menu Materia abilitato, cosi' "required" blocca comunque l'invio
    riempiSelect(materiaSelect, ind.materie.map(function(m){
      return { valore: m.id, testo: m.nome };
    }), ind.materie.length === 0 ? 'Nessuna tua materia in questo indirizzo' : 'Scegli la materia', true);
    azzeraAnno();
  });

  // scelta la materia, il menu Anno offre solo gli anni del suo quadro orario in quell'indirizzo
  materiaSelect.addEventListener('change', function(){
    var ind = indirizzoScelto();
    if (!ind) return;
    var materia = ind.materie.filter(function(m){ return String(m.id) === materiaSelect.value; })[0];
    if (!materia) return;
    riempiSelect(annoSelect, materia.anni.map(function(anno){
      return { valore: anno, testo: anno + '° anno' };
    }), 'Scegli l\'anno', true);
  });

  function apriModal(){
    backdrop.classList.add('open');
    caricaOpzioni();
    titoloInput.focus();
  }
  function chiudiModal(){
    backdrop.classList.remove('open');
    form.reset();
    azzeraDipendenti();
    erroreEl.hidden = true;
    mostraNomeFile('Nessun file selezionato', false);
  }

  openBtn.addEventListener('click', apriModal);
  closeBtn.addEventListener('click', chiudiModal);
  backdrop.addEventListener('click', function(e){ if (e.target === backdrop) chiudiModal(); });

  fileBtn.addEventListener('click', function(){ fileInput.click(); });
  fileInput.addEventListener('change', function(){
    mostraNomeFile(fileInput.files[0] ? fileInput.files[0].name : 'Nessun file selezionato', false);
  });

  form.addEventListener('submit', function(e){
    e.preventDefault();
    erroreEl.hidden = true;
    var file = fileInput.files[0];
    // l'input file e' nascosto: se manca il file lo segnaliamo noi accanto al pulsante
    if (!file) {
      mostraNomeFile('Scegli il file da caricare', true);
      fileBtn.focus();
      return;
    }
    var estensione = file.name.split('.').pop().toLowerCase();
    if (file.name.indexOf('.') === -1 || ESTENSIONI_AMMESSE.indexOf(estensione) === -1) {
      mostraNomeFile('Tipo di file non ammesso', true);
      return;
    }
    if (file.size > DIMENSIONE_MAX) {
      mostraNomeFile('Il file supera il limite di 20 MB', true);
      return;
    }

    // multipart/form-data: e' l'unico formato che porta un file; il Content-Type
    // (con il boundary) lo imposta il browser da solo, per questo non lo scriviamo.
    var dati = new FormData();
    dati.append('titolo', titoloInput.value);
    dati.append('categoria', categoriaSelect.value);   // id di CATEGORIA
    dati.append('indirizzo', indirizzoSelect.value);   // id di INDIRIZZO
    dati.append('materia', materiaSelect.value);       // id di MATERIA
    dati.append('anno', annoSelect.value);
    dati.append('descrizione', descrizioneInput.value);
    dati.append('file', file);

    submitBtn.disabled = true;
    submitBtn.textContent = 'Caricamento...';
    fetch('api/carica.php', { method: 'POST', body: dati })
      .then(function(risposta){ return risposta.json(); })
      .then(function(esito){
        if (!esito.successo) {
          mostraErrore(esito.errore);
          return;
        }
        // in cima all'elenco, come lo darebbe api/materiali.php (dal piu' recente)
        materiali.unshift(daServer(esito.materiale));
        disegna();
        chiudiModal();
      })
      .catch(function(){
        mostraErrore('Errore di connessione al server.');
      })
      .finally(function(){
        submitBtn.disabled = false;
        submitBtn.textContent = 'Carica';
      });
  });

  // ===== Finestra di conferma (elimina materiale) =====
  var confirmBackdrop = document.getElementById('confirmBackdrop');
  var confirmTesto = document.getElementById('confirmTesto');
  var confirmOk = document.getElementById('confirmOk');
  var confirmAnnulla = document.getElementById('confirmAnnulla');
  var confirmErrore = document.getElementById('confirmErrore');
  var idDaEliminare = null;

  function apriConferma(id){
    var materiale = materiali.filter(function(m){ return m.id === id; })[0];
    if (!materiale) return;
    idDaEliminare = id;
    confirmTesto.textContent = 'Il materiale «' + materiale.titolo + '» sarà eliminato e non potrà essere recuperato.';
    confirmErrore.hidden = true;
    confirmBackdrop.classList.add('open');
  }
  function chiudiConferma(){
    confirmBackdrop.classList.remove('open');
    idDaEliminare = null;
  }

  confirmOk.addEventListener('click', function(){
    if (idDaEliminare === null) return;
    var id = idDaEliminare;
    confirmOk.disabled = true;
    confirmErrore.hidden = true;
    fetch('api/elimina.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id=' + encodeURIComponent(id)
    })
      .then(function(risposta){ return risposta.json(); })
      .then(function(esito){
        if (!esito.successo) {
          // la finestra resta aperta: l'utente vede perche' non e' stato eliminato
          confirmErrore.textContent = esito.errore;
          confirmErrore.hidden = false;
          return;
        }
        materiali = materiali.filter(function(m){ return m.id !== id; });
        disegna();
        chiudiConferma();
      })
      .catch(function(){
        confirmErrore.textContent = 'Errore di connessione al server.';
        confirmErrore.hidden = false;
      })
      .finally(function(){
        confirmOk.disabled = false;
      });
  });
  confirmAnnulla.addEventListener('click', chiudiConferma);
  confirmBackdrop.addEventListener('click', function(e){ if (e.target === confirmBackdrop) chiudiConferma(); });

  // Esc chiude la finestra aperta (quella di login la chiude js/main.js)
  document.addEventListener('keydown', function(e){
    if (e.key !== 'Escape') return;
    if (backdrop.classList.contains('open')) chiudiModal();
    if (confirmBackdrop.classList.contains('open')) chiudiConferma();
  });
})();
