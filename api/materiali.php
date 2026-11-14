<?php
// materiali.php — elenco dei materiali caricati dal docente collegato
// ("La tua area" di area.html), dal piu' recente al piu' vecchio.
//
// Risposta: { successo, materiali: [ { id, titolo, categoria, indirizzo, materia, anno,
//             nome_file, tipo, data, dimensione_kb } ] }

require_once __DIR__ . '/../includes/risposte_json.php';
require_once __DIR__ . '/../includes/accesso.php';

// ===== 1) Utente collegato =====
$docenteId = richiedi_utente('Accedi per vedere i tuoi materiali.');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/materiali.php';

// ===== 2) Lettura dal database =====
// i JOIN sostituiscono gli id di categoria, indirizzo e materia con i loro nomi
$stmt = $pdo->prepare(
    'SELECT m.id, m.titolo, c.nome AS categoria, d.nome AS indirizzo, ma.nome AS materia,
            m.anno, m.tipo_file, m.path_file, m.nome_file, m.dimensione_kb, m.data_caricamento
     FROM MATERIALE m
     JOIN CATEGORIA c ON c.id = m.categoria_id
     JOIN INDIRIZZO d ON d.id = m.indirizzo_id
     JOIN MATERIA ma  ON ma.id = m.materia_id
     WHERE m.docente_id = :docente
     ORDER BY m.data_caricamento DESC, m.id DESC'
);
$stmt->execute(['docente' => $docenteId]);

// ===== 3) Risposta =====
$materiali = [];
foreach ($stmt as $riga) {
    $materiali[] = [
        'id'            => (int) $riga['id'],
        'titolo'        => $riga['titolo'],
        'categoria'     => $riga['categoria'],
        'indirizzo'     => $riga['indirizzo'],
        'materia'       => $riga['materia'],
        'anno'          => (int) $riga['anno'],
        'nome_file'     => nome_originale($riga['nome_file'], $riga['path_file']),
        'tipo'          => strtoupper($riga['tipo_file']),
        'data'          => date('d/m/Y', strtotime($riga['data_caricamento'])),
        'dimensione_kb' => (int) $riga['dimensione_kb'],
    ];
}

rispondi_json(['successo' => true, 'materiali' => $materiali]);
