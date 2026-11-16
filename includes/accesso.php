<?php
// accesso.php — controlli comuni agli endpoint in api/: metodo della richiesta e
// utente collegato. Va incluso dopo includes/risposte_json.php.

// ===== Solo POST =====
// Per le richieste che cambiano qualcosa (caricare, eliminare, uscire). Link e
// immagini esterne fanno richieste GET: cosi' non possono agire all'insaputa dell'utente.
function richiedi_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        rispondi_errore('Metodo non consentito: usare POST.', 405);
    }
}

// ===== Utente collegato =====
// Restituisce l'id dell'utente collegato; se non c'e' risponde 401 con $messaggio.
// - senza cookie di sessione la sessione non si apre nemmeno (non se ne crea una vuota);
// - session_write_close(): qui la sessione si legge soltanto, chiuderla subito la
//   libera per le altre richieste della stessa pagina.
function richiedi_utente(string $messaggio): int
{
    if (isset($_COOKIE[session_name()])) {
        session_start();
        session_write_close();
    }
    if (empty($_SESSION['utente_id'])) {
        rispondi_errore($messaggio, 401);
    }
    return (int) $_SESSION['utente_id'];
}
