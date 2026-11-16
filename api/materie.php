<?php
// materie.php — le scelte del form "Carica materiale" per il docente collegato:
// - le categorie (tabella CATEGORIA): una nuova categoria aggiunta con un INSERT
//   compare nel menu senza toccare area.html;
// - TUTTI gli indirizzi e, per ognuno, le materie del docente (INSEGNA) presenti nel
//   quadro orario di quell'indirizzo (PIANO_STUDI), con gli anni previsti. Un indirizzo
//   senza materie del docente compare lo stesso, con l'elenco vuoto: il form lo segnala.
//
// Risposta: { successo, categorie: [ { id, nome } ],
//             indirizzi: [ { id, nome, materie: [ { id, nome, anni:[1,2] } ] } ] }

require_once __DIR__ . '/../includes/risposte_json.php';
require_once __DIR__ . '/../includes/accesso.php';

// ===== 1) Utente collegato =====
$docenteId = richiedi_utente('Accedi per vedere le tue materie.');

require_once __DIR__ . '/../includes/config.php';

// ===== 2) Categorie =====
// nell'ordine degli id: "Altro" (id 6) resta in fondo
$categorie = [];
foreach ($pdo->query('SELECT id, nome FROM CATEGORIA ORDER BY id') as $riga) {
    $categorie[] = ['id' => (int) $riga['id'], 'nome' => $riga['nome']];
}

// ===== 3) Indirizzi =====
// tutti, anche quelli senza materie del docente; la chiave dell'array e' l'id
$indirizzi = [];
foreach ($pdo->query('SELECT id, nome FROM INDIRIZZO ORDER BY nome') as $riga) {
    $indirizzi[(int) $riga['id']] = ['id' => (int) $riga['id'], 'nome' => $riga['nome'], 'materie' => []];
}

// ===== 4) Materie del docente in ogni indirizzo =====
// Una riga per coppia indirizzo/materia; gli anni arrivano in un'unica stringa "3,4,5"
// (GROUP_CONCAT). Una materia in INSEGNA ma assente da PIANO_STUDI non compare:
// senza un posto nel quadro orario non si potrebbe caricare nulla.
$stmt = $pdo->prepare(
    'SELECT p.indirizzo_id, m.id AS materia_id, m.nome AS materia,
            GROUP_CONCAT(DISTINCT p.anno ORDER BY p.anno) AS anni
     FROM INSEGNA i
     JOIN MATERIA m ON m.id = i.materia_id
     JOIN PIANO_STUDI p ON p.materia_id = m.id
     WHERE i.docente_id = :docente
     GROUP BY p.indirizzo_id, m.id, m.nome
     ORDER BY m.nome'
);
$stmt->execute(['docente' => $docenteId]);

foreach ($stmt as $riga) {
    $indirizzi[(int) $riga['indirizzo_id']]['materie'][] = [
        'id'   => (int) $riga['materia_id'],
        'nome' => $riga['materia'],
        'anni' => array_map('intval', explode(',', $riga['anni'])),
    ];
}

// ===== 5) Risposta =====
// array_values(): da array con chiavi (gli id) a elenco semplice, che in JSON e' [ ... ]
rispondi_json(['successo' => true, 'categorie' => $categorie, 'indirizzi' => array_values($indirizzi)]);
