<?php
// config.php — connessione al database (PDO): crea $pdo.

// per rispondi_errore(); require_once: se l'endpoint l'ha gia' incluso non succede nulla
require_once __DIR__ . '/risposte_json.php';

// ===== Parametri di connessione =====
$db_host = 'localhost';
$db_name = 'davincidocs';
$db_user = 'root';
$db_pass = 'root';

// ===== Connessione =====
// ERRMODE_EXCEPTION: ogni errore SQL diventa un'eccezione (gestita da risposte_json.php).
// FETCH_ASSOC: le righe arrivano come array con i nomi delle colonne.
try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log((string) $e); // il dettaglio resta nel log di PHP, non va all'utente
    rispondi_errore('Connessione al database non riuscita.', 500);
}
