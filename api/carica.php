<?php
// carica.php — riceve il form "Carica materiale" di area.html (multipart/form-data:
// campi in $_POST, file in $_FILES['file']), salva il file in
// materiali/<cognome-nome>/<materia>/ e scrive la riga in MATERIALE.
//
// Riceve: titolo, descrizione, categoria, indirizzo, materia, anno (id), file (solo POST)
// Risposta: { successo, materiale: { id, titolo, categoria, indirizzo, materia, anno,
//             nome_file, tipo, data, dimensione_kb } }   oppure   { successo:false, errore }

require_once __DIR__ . '/../includes/risposte_json.php';
require_once __DIR__ . '/../includes/accesso.php';

// ===== Regole sui file =====
// Stessa lista di js/main_area.js: quella del browser avvisa subito, questa e' il
// controllo che conta (il browser si aggira).
const ESTENSIONI_AMMESSE = [
    // documenti
    'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'odt', 'odp', 'ods',
    // archivi e immagini
    'zip', 'png', 'jpg', 'jpeg',
    // grafica
    'svg', 'ps', 'ai', 'indd',
    // CAT: AutoCAD e SketchUp
    'dwg', 'dxf', 'skp',
    // informatica
    'c', 'cpp', 'java', 'py', 'json', 'html', 'css', 'js',
];
const DIMENSIONE_MAX = 20 * 1024 * 1024; // 20 MB

// ===== 1) Solo POST, utente collegato =====
richiedi_post();
$docenteId = richiedi_utente('Accedi per caricare materiali.');

// ===== 2) Il file =====
// Oltre post_max_size PHP scarta tutto il corpo: $_POST e $_FILES arrivano vuoti.
if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    rispondi_errore('Il file è troppo grande per il server (limite PHP post_max_size = '
        . ini_get('post_max_size') . ').', 413);
}

$file = $_FILES['file'] ?? null;
if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
    rispondi_errore('Scegli il file da caricare.');
}
if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
    rispondi_errore('Il file è troppo grande per il server (limite PHP upload_max_filesize = '
        . ini_get('upload_max_filesize') . ').', 413);
}
if ($file['error'] !== UPLOAD_ERR_OK) {
    error_log('Upload fallito, codice PHP ' . $file['error']);
    rispondi_errore('Caricamento del file non riuscito. Riprova.', 500);
}
if ($file['size'] > DIMENSIONE_MAX) {
    rispondi_errore('Il file supera il limite di 20 MB.', 413);
}
if (!is_uploaded_file($file['tmp_name'])) {
    rispondi_errore('Caricamento del file non valido.');
}

$estensione = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($estensione, ESTENSIONI_AMMESSE, true)) {
    rispondi_errore('Tipo di file non ammesso. Formati ammessi: ' . implode(', ', ESTENSIONI_AMMESSE) . '.');
}

// ===== 3) I campi del form =====
$titolo      = trim($_POST['titolo'] ?? '');
$descrizione = trim($_POST['descrizione'] ?? '');
$categoriaId = filter_var($_POST['categoria'] ?? '', FILTER_VALIDATE_INT);
$indirizzoId = filter_var($_POST['indirizzo'] ?? '', FILTER_VALIDATE_INT);
$materiaId   = filter_var($_POST['materia'] ?? '', FILTER_VALIDATE_INT);
$anno        = filter_var($_POST['anno'] ?? '', FILTER_VALIDATE_INT);

// Il browser invia sempre UTF-8; un testo in un'altra codifica (richiesta costruita
// a mano) verrebbe rifiutato da MySQL con un errore 500: meglio un 400 chiaro qui.
if (!mb_check_encoding($titolo, 'UTF-8') || !mb_check_encoding($descrizione, 'UTF-8')) {
    rispondi_errore('Titolo o descrizione contengono caratteri non validi.');
}
if ($titolo === '' || mb_strlen($titolo) > 150) {
    rispondi_errore('Inserisci un titolo (massimo 150 caratteri).');
}
if ($categoriaId === false || $indirizzoId === false || $materiaId === false || $anno === false) {
    rispondi_errore('Scegli categoria, indirizzo, materia e anno.');
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/materiali.php';

// ===== 4) Il docente puo' caricare qui? =====
// La materia deve essere sua (INSEGNA) e la terna indirizzo/materia/anno deve stare nel
// quadro orario (PIANO_STUDI). Il form offre solo scelte valide, ma il controllo vero e' questo.
$stmt = $pdo->prepare(
    'SELECT d.nome AS indirizzo, m.nome AS materia, u.nome AS nome_docente, u.cognome AS cognome_docente
     FROM INSEGNA i
     JOIN UTENTE u      ON u.id = i.docente_id
     JOIN PIANO_STUDI p ON p.materia_id = i.materia_id
     JOIN MATERIA m     ON m.id = p.materia_id
     JOIN INDIRIZZO d   ON d.id = p.indirizzo_id
     WHERE i.docente_id = :docente AND p.indirizzo_id = :indirizzo
       AND p.materia_id = :materia AND p.anno = :anno'
);
$stmt->execute(['docente' => $docenteId, 'indirizzo' => $indirizzoId, 'materia' => $materiaId, 'anno' => $anno]);
$scelta = $stmt->fetch();
if (!$scelta) {
    rispondi_errore('Non puoi caricare materiali per questa combinazione di indirizzo, materia e anno.', 403);
}

$stmt = $pdo->prepare('SELECT nome FROM CATEGORIA WHERE id = :id');
$stmt->execute(['id' => $categoriaId]);
$categoria = $stmt->fetchColumn();
if ($categoria === false) {
    rispondi_errore('Categoria non valida.');
}

// ===== 5) Salvataggio del file =====
// Cartella materiali/<cognome-nome>/<materia>/ (es. minissale-maurizio/informatica/):
// si sfoglia a mano senza conoscere gli id. Nel DB va il percorso relativo, quindi
// cambiare questa regola non tocca i file gia' caricati.
$cartellaDocente = nome_cartella($scelta['cognome_docente'] . ' ' . $scelta['nome_docente']);
if ($cartellaDocente === '') {
    $cartellaDocente = 'docente-' . $docenteId;
}
$cartellaMateria = nome_cartella($scelta['materia']);
if ($cartellaMateria === '') {
    $cartellaMateria = 'materia-' . $materiaId;
}
$nomeCartella = $cartellaDocente . '/' . $cartellaMateria; // relativo a materiali/
$cartella = __DIR__ . '/../materiali/' . $nomeCartella;
if (!is_dir($cartella) && !mkdir($cartella, 0775, true) && !is_dir($cartella)) {
    error_log('Impossibile creare la cartella ' . $cartella);
    rispondi_errore('Impossibile salvare il file sul server.', 500);
}

// Nome originale (es. "Verifica Più.pdf"): solo da mostrare nella scheda. Si tiene
// quello che segue l'ultimo / o \, senza caratteri di controllo, max 255 caratteri.
$nomeOriginale = preg_replace('/^.*[\\\\\/]/s', '', (string) $file['name']);
$nomeOriginale = mb_substr(preg_replace('/[\x00-\x1F\x7F]+/u', '', $nomeOriginale) ?? '', 0, 255, 'UTF-8');
// Nome sul disco: parte casuale (non si indovina, non sovrascrive) + nome ripulito
// (solo lettere, cifre, trattini: niente ../ o altri trucchi).
$pulito = trim(preg_replace('/[^A-Za-z0-9]+/', '-', pathinfo($file['name'], PATHINFO_FILENAME)), '-');
$pulito = substr($pulito === '' ? 'file' : $pulito, 0, 50);
$nomeSalvato = bin2hex(random_bytes(8)) . '_' . $pulito . '.' . $estensione;

if (!move_uploaded_file($file['tmp_name'], $cartella . '/' . $nomeSalvato)) {
    error_log('move_uploaded_file fallita verso ' . $cartella);
    rimuovi_cartelle_vuote($cartella);
    rispondi_errore('Impossibile salvare il file sul server.', 500);
}

// ===== 6) Riga in MATERIALE =====
// Se l'INSERT fallisce si toglie il file appena salvato (e la cartella, se ora e' vuota);
// la risposta 500 la da' il gestore di errori di risposte_json.php.
$dimensioneKb = max(1, (int) ceil($file['size'] / 1024));
try {
    $stmt = $pdo->prepare(
        'INSERT INTO MATERIALE (titolo, descrizione, tipo_file, path_file, nome_file, dimensione_kb,
                                docente_id, indirizzo_id, materia_id, anno, categoria_id)
         VALUES (:titolo, :descrizione, :tipo, :percorso, :nomefile, :kb, :docente, :indirizzo, :materia, :anno, :categoria)'
    );
    $stmt->execute([
        'titolo'      => $titolo,
        'descrizione' => $descrizione === '' ? null : $descrizione,
        'tipo'        => $estensione,
        'percorso'    => $nomeCartella . '/' . $nomeSalvato, // relativo a materiali/
        'nomefile'    => $nomeOriginale === '' ? null : $nomeOriginale,
        'kb'          => $dimensioneKb,
        'docente'     => $docenteId,
        'indirizzo'   => $indirizzoId,
        'materia'     => $materiaId,
        'anno'        => $anno,
        'categoria'   => $categoriaId,
    ]);
} catch (Throwable $e) {
    unlink($cartella . '/' . $nomeSalvato);
    rimuovi_cartelle_vuote($cartella);
    throw $e;
}

// ===== 7) Risposta =====
// stessi campi di api/materiali.php: la pagina aggiunge la scheda senza ricaricare l'elenco
rispondi_json([
    'successo'  => true,
    'materiale' => [
        'id'            => (int) $pdo->lastInsertId(),
        'titolo'        => $titolo,
        'categoria'     => $categoria,
        'indirizzo'     => $scelta['indirizzo'],
        'materia'       => $scelta['materia'],
        'anno'          => $anno,
        'nome_file'     => nome_originale($nomeOriginale === '' ? null : $nomeOriginale, $nomeSalvato),
        'tipo'          => strtoupper($estensione),
        'data'          => date('d/m/Y'),
        'dimensione_kb' => $dimensioneKb,
    ],
]);
