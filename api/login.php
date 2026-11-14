<?php
// login.php — verifica le credenziali (email + password) e apre la sessione.
// VERSIONE 1 (sezione 5.2 del documento di riferimento): confronto diretto
// della password in chiaro. NON sicura — da sostituire con la versione a hash
// (sezione 5.3) prima di qualsiasi uso reale.
//
// Riceve: email, password (application/x-www-form-urlencoded, solo POST)
// Risposta: { successo, nome, cognome, tipo_utente }   oppure   { successo:false, errore }

require_once __DIR__ . '/../includes/risposte_json.php';

// ===== SOLO POST =====
// La password deve viaggiare nel CORPO della richiesta, non nell'indirizzo.
// Con GET finirebbe nell'URL (login.php?email=...&password=...), cioe' nella
// cronologia del browser, nei log del server e nei link copiati.
// Controllo fatto prima di sessione e database: una richiesta sbagliata
// viene respinta subito, senza consumare risorse.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rispondi_errore('Metodo non consentito: usare POST.', 405);
}
// ===== FINE SOLO POST =====

session_start();
require_once __DIR__ . '/../includes/config.php';

// ===== 1) Dati del form =====
// main.js invia il form come application/x-www-form-urlencoded: i dati sono in $_POST
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Errori di accesso con codice 200: la richiesta e' corretta, sono i dati a non esserlo.
if ($email === '' || $password === '') {
    rispondi_errore('Inserisci email e password.', 200);
}

// ===== 2) Verifica delle credenziali =====
// Query preparata: email e password non entrano mai nel testo SQL (niente SQL injection).
$stmt = $pdo->prepare(
    'SELECT u.id, u.nome, u.cognome, u.tipo_utente, u.attivo
     FROM CREDENZIALI c
     JOIN UTENTE u ON u.id = c.utente_id
     WHERE c.username = :username AND c.password = :password'
);
$stmt->execute(['username' => $email, 'password' => $password]);
$riga = $stmt->fetch();

if (!$riga) {
    rispondi_errore('Email o password non corrette.', 200);
}

// ===== DISATTIVAZIONE UTENTE: un utente con UTENTE.attivo = FALSE non puo' accedere =====
// (la colonna u.attivo e' letta nella SELECT qui sopra)
// controllato dopo la password: il messaggio specifico lo vede solo chi conosce
// le credenziali, non chi prova email a caso
if (!$riga['attivo']) {
    rispondi_errore('Account disattivato: rivolgiti alla segreteria didattica.', 200);
}
// ===== FINE DISATTIVAZIONE UTENTE =====

// ===== 3) Apertura della sessione =====
$_SESSION['utente_id']   = $riga['id'];
$_SESSION['nome']        = $riga['nome'];
$_SESSION['cognome']     = $riga['cognome'];
$_SESSION['tipo_utente'] = $riga['tipo_utente'];

rispondi_json([
    'successo'    => true,
    'nome'        => $riga['nome'],
    'cognome'     => $riga['cognome'],
    'tipo_utente' => $riga['tipo_utente'],
]);
