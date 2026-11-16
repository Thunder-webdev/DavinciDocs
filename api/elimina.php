<?php
// elimina.php — elimina un materiale: la riga in MATERIALE e il file in materiali/.
// Solo il docente che l'ha caricato puo' eliminarlo.
//
// Riceve: id (application/x-www-form-urlencoded, solo POST)
// Risposta: { successo }   oppure   { successo:false, errore }

require_once __DIR__ . '/../includes/risposte_json.php';
require_once __DIR__ . '/../includes/accesso.php';

// ===== 1) Solo POST, utente collegato =====
// solo POST: un link o un'immagine esterna non devono poter cancellare nulla
richiedi_post();
$docenteId = richiedi_utente('Accedi per eliminare i materiali.');

// ===== 2) Quale materiale =====
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
if ($id === false) {
    rispondi_errore('Materiale non valido.');
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/materiali.php';

// ===== 3) Il materiale e' del docente? =====
// Chi prova a eliminare il materiale di un altro riceve lo stesso "non trovato" di un
// id inesistente: non si scopre quali materiali esistono.
$stmt = $pdo->prepare('SELECT path_file FROM MATERIALE WHERE id = :id AND docente_id = :docente');
$stmt->execute(['id' => $id, 'docente' => $docenteId]);
$percorso = $stmt->fetchColumn();
if ($percorso === false) {
    rispondi_errore('Materiale non trovato.', 404);
}

// ===== 4) Eliminazione =====
// Prima la riga, poi il file: un file orfano e' innocuo, una riga senza file sarebbe
// un materiale in elenco che non si scarica. Poi via le cartelle rimaste vuote.
$stmt = $pdo->prepare('DELETE FROM MATERIALE WHERE id = :id AND docente_id = :docente');
$stmt->execute(['id' => $id, 'docente' => $docenteId]);

$file = percorso_materiale($percorso);
if ($file !== null) {
    if (unlink($file)) {
        rimuovi_cartelle_vuote(dirname($file));
    } else {
        error_log('Impossibile cancellare il file ' . $file . ' (materiale ' . $id . ')');
    }
}

rispondi_json(['successo' => true]);
