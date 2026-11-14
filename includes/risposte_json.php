<?php
// risposte_json.php — risposte JSON comuni a tutti gli endpoint in api/.
// Va incluso per primo: da qui in poi anche un errore imprevisto (es. una query
// su una tabella mancante) arriva al browser come JSON e non come pagina HTML.

// ===== Impostazioni =====
ini_set('display_errors', '0'); // gli errori vanno nel log di PHP, non nella risposta
header('Content-Type: application/json; charset=utf-8');

// ===== Risposte =====
// Invia $dati come JSON con il codice HTTP dato e termina lo script.
function rispondi_json(array $dati, int $codice = 200): void
{
    http_response_code($codice);
    echo json_encode($dati, JSON_UNESCAPED_UNICODE);
    exit;
}

// Scorciatoia per gli errori: { successo:false, errore } e termina lo script.
function rispondi_errore(string $messaggio, int $codice = 400): void
{
    rispondi_json(['successo' => false, 'errore' => $messaggio], $codice);
}

// ===== Errori imprevisti =====
// Un'eccezione non gestita diventa un 500 generico: il dettaglio resta nel log.
set_exception_handler(function (Throwable $e) {
    error_log((string) $e);
    rispondi_errore('Errore interno del server. Riprova più tardi.', 500);
});
