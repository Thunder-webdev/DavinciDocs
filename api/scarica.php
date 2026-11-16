<?php
// scarica.php — invia il file di un materiale (api/scarica.php?id=12).
// I file in materiali/ non si raggiungono direttamente (materiali/.htaccess): passano
// tutti da qui. Il download e' pubblico, come il resto del sito ("Esplora i materiali
// senza registrarti"): non serve essere collegati.
//
// Riceve: id (nell'indirizzo, GET)   Risposta: il file   oppure   { successo:false, errore }

require_once __DIR__ . '/../includes/risposte_json.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/materiali.php';

// ===== 1) Quale materiale =====
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if ($id === false) {
    rispondi_errore('Materiale non valido.');
}

$stmt = $pdo->prepare('SELECT nome_file, path_file FROM MATERIALE WHERE id = :id');
$stmt->execute(['id' => $id]);
$riga = $stmt->fetch();
$file = $riga ? percorso_materiale($riga['path_file']) : null;
if ($file === null) {
    rispondi_errore('Materiale non trovato.', 404);
}

// ===== 2) Nome proposto per il salvataggio =====
// Quello originale del caricamento (es. "Verifica Più.pdf"), senza i caratteri vietati
// nei nomi di file ne' gli a capo (che spezzerebbero l'intestazione HTTP).
$nome = trim(preg_replace('/[\\\\\/:*?"<>|\x00-\x1f]+/u', ' ', nome_originale($riga['nome_file'], $riga['path_file'])));
$nome = $nome === '' ? basename($file) : $nome;
$nomeAscii = preg_replace('/[^A-Za-z0-9._-]+/', '_', $nome); // per i browser vecchi
$nomeUtf8 = rawurlencode($nome);                             // nome con accenti (RFC 5987)

// ===== 3) Invio del file =====
// SEMPRE come allegato da salvare, mai da aprire nel browser: un .svg o un .html
// caricato potrebbe contenere script, che aperti dal sito girerebbero con i suoi
// permessi. Per questo tipo generico (octet-stream), "attachment" e nosniff
// (il browser non deve indovinare un tipo diverso).
while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename=\"{$nomeAscii}\"; filename*=UTF-8''{$nomeUtf8}");
header('Content-Length: ' . filesize($file));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private');
readfile($file);
exit;
