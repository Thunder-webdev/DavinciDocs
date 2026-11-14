<?php
// logout.php — disconnette l'utente: svuota e distrugge la sessione PHP
// e cancella il cookie di sessione dal browser.
//
// Riceve: nulla (solo POST)   Risposta: { successo }

require_once __DIR__ . '/../includes/risposte_json.php';
require_once __DIR__ . '/../includes/accesso.php';

// ===== 1) Solo POST =====
// un semplice link o un'immagine esterna non possono disconnettere l'utente
richiedi_post();

// ===== 2) Chiusura della sessione =====
session_start();
$_SESSION = [];

// il cookie si cancella rimandandolo con una scadenza gia' passata
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

session_destroy();

rispondi_json(['successo' => true]);
