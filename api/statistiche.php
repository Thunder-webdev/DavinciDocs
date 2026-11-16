<?php
// statistiche.php — i numeri della home ("N materiali condivisi"). Pubblico, come la
// home: restituisce solo un conteggio, nessun dato dei materiali.
//
// Risposta: { successo, materiali }   oppure   { successo:false, errore }

require_once __DIR__ . '/../includes/risposte_json.php';
require_once __DIR__ . '/../includes/config.php';

// ===== 1) Conteggio =====
// COUNT(*) restituisce una sola riga con una sola colonna: fetchColumn() la legge
$materiali = (int) $pdo->query('SELECT COUNT(*) FROM MATERIALE')->fetchColumn();

// ===== 2) Risposta =====
rispondi_json(['successo' => true, 'materiali' => $materiali]);
