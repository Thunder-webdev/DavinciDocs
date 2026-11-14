<?php
// materiali.php — funzioni comuni agli endpoint che toccano i file caricati
// (carica.php, materiali.php, scarica.php, elimina.php). Va incluso dopo includes/risposte_json.php.

// ===== Percorso di un materiale =====
// Da path_file del database (es. "minissale-maurizio/informatica/ab12cd34_dispensa.pdf")
// al percorso vero sul disco, oppure null se il file non esiste o NON sta in materiali/.
// realpath() risolve "../": un path_file come "../../includes/config.php" viene scartato.
function percorso_materiale(string $relativo): ?string
{
    $radice = realpath(__DIR__ . '/../materiali');
    if ($radice === false) {
        return null;
    }
    $file = realpath($radice . DIRECTORY_SEPARATOR . $relativo);
    if ($file === false || strpos($file, $radice . DIRECTORY_SEPARATOR) !== 0 || !is_file($file)) {
        return null;
    }
    return $file;
}

// ===== Nome originale di un materiale =====
// Il nome del file com'era sul computer del docente (colonna nome_file). Le righe
// vecchie non lo hanno: si ricava dal nome sul disco togliendo il prefisso casuale
// ("ab12cd34ef56ab78_dispensa.pdf" -> "dispensa.pdf").
function nome_originale(?string $nomeFile, string $pathFile): string
{
    return $nomeFile ?? preg_replace('/^[0-9a-f]{16}_/', '', basename($pathFile));
}

// ===== Pulizia delle cartelle =====
// Toglie la cartella se e' vuota, poi risale (materia -> docente) finche' ne trova una
// non vuota. Non esce mai da materiali/ e non tocca materiali/ stessa.
// rmdir() cancella solo cartelle vuote: su una piena fallisce e il ciclo si ferma
// (la @ evita l'avviso nel log, e' un caso normale).
function rimuovi_cartelle_vuote(string $cartella): void
{
    $radice = realpath(__DIR__ . '/../materiali');
    $c = realpath($cartella);
    while ($radice !== false && $c !== false
        && strpos($c, $radice . DIRECTORY_SEPARATOR) === 0 && @rmdir($c)) {
        $c = dirname($c);
    }
}

// ===== Nomi di cartella =====
// Da un testo: minuscolo, senza accenti, solo lettere, cifre e trattini.
// "Sistemi e Reti" -> "sistemi-e-reti". Puo' restituire una stringa vuota.
function nome_cartella(string $testo): string
{
    $t = mb_strtolower($testo, 'UTF-8');
    $t = strtr($t, [
        'à' => 'a', 'á' => 'a', 'è' => 'e', 'é' => 'e', 'ì' => 'i', 'í' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ù' => 'u', 'ú' => 'u',
    ]);
    return trim(preg_replace('/[^a-z0-9]+/', '-', $t), '-');
}
