-- ============================================================
-- DaVinciDocs — Dati di test (utenti e credenziali)
-- Da eseguire in phpMyAdmin DOPO sql/davincidocs.sql e sql/inserimento_dati.sql, sullo
-- stesso database: serve a ripopolarlo ogni volta che la cartella
-- del sito viene scaricata da git (il database non è nel repository).
--
-- Rieseguibile: usa INSERT IGNORE, quindi rilanciarlo su un database
-- già popolato non crea righe duplicate (il confronto è su
-- UTENTE.email, CREDENZIALI.username e sulla chiave di INSEGNA).
--
-- Richiede i dati fissi di sql/inserimento_dati.sql (MATERIA in particolare).
--
-- Le password sono salvate in chiaro, coerenti con la versione 5.2
-- (NON sicura) di login.php attualmente in uso — vedi sezione 5.2 del
-- documento di riferimento. Passando alla versione con hash (5.3),
-- queste righe vanno rigenerate con password_hash().
-- ============================================================

USE davincidocs;
-- il file è in UTF-8: senza questa riga "Sgrò" potrebbe arrivare storpiato
SET NAMES utf8mb4;

INSERT IGNORE INTO UTENTE (nome, cognome, email, tipo_utente, tempo_indeterminato) VALUES
('Maurizio',      'Minissale',  'maurizio.minissale@davincimilazzo.edu.it',  'docente', TRUE),
('Rosa Tina',     'Artigliere', 'rosita.artigliere@davincimilazzo.edu.it',   'docente', TRUE),
('Antonio',       'Caristia',   'antonio.caristia@davincimilazzo.edu.it',    'docente', TRUE),
('Antonio Santi', 'Sgrò',       'antonio.sgro@davincimilazzo.edu.it',        'docente', FALSE),
('Filippo',       'Rizzo',      'pietro.rizzo@davincimilazzo.edu.it',        'docente', TRUE),
('Antonio',       'Pirri',      'antonio.pirri@davincimilazzo.edu.it',       'docente', FALSE),
('Paola',         'Cotroneo',   'paoletta.cotroneo@davincimilazzo.edu.it',   'docente', TRUE),
('Vincenzo',      'Giorgianni', 'vincenzo.giorgianni@davincimilazzo.edu.it', 'docente', FALSE);

-- CREDENZIALI.username è sempre la stessa email di UTENTE, per convenzione del progetto.
INSERT IGNORE INTO CREDENZIALI (username, password, utente_id)
SELECT u.email, cred.password, u.id
FROM UTENTE u
JOIN (
  SELECT 'maurizio.minissale@davincimilazzo.edu.it' AS email, '1234'        AS password
  UNION ALL SELECT 'rosita.artigliere@davincimilazzo.edu.it',   'Rosa1234'
  UNION ALL SELECT 'antonio.caristia@davincimilazzo.edu.it',    'antO1234'
  UNION ALL SELECT 'antonio.sgro@davincimilazzo.edu.it',        'Sgro1234'
  UNION ALL SELECT 'pietro.rizzo@davincimilazzo.edu.it',        'Filippo1234'
  UNION ALL SELECT 'antonio.pirri@davincimilazzo.edu.it',       'Pirri1234'
  UNION ALL SELECT 'paoletta.cotroneo@davincimilazzo.edu.it',   'Paola1234'
  UNION ALL SELECT 'vincenzo.giorgianni@davincimilazzo.edu.it', 'Vincenzo1234'
) AS cred ON cred.email = u.email;

-- Materie insegnate (tabella INSEGNA): per provare il caricamento, tutti i
-- docenti di prova insegnano tutte le materie.
-- Gli id dei docenti NON sono scritti a mano: li assegna AUTO_INCREMENT e
-- cambiano da un database all'altro (es. 1, 2, 43, 44...). Si prendono con una
-- SELECT a partire dalle email; CROSS JOIN = ogni docente con ogni materia.
INSERT IGNORE INTO INSEGNA (docente_id, materia_id)
SELECT u.id, m.id
FROM UTENTE u
CROSS JOIN MATERIA m
WHERE u.email IN (
  'maurizio.minissale@davincimilazzo.edu.it',
  'rosita.artigliere@davincimilazzo.edu.it',
  'antonio.caristia@davincimilazzo.edu.it',
  'antonio.sgro@davincimilazzo.edu.it',
  'pietro.rizzo@davincimilazzo.edu.it',
  'antonio.pirri@davincimilazzo.edu.it',
  'paoletta.cotroneo@davincimilazzo.edu.it',
  'vincenzo.giorgianni@davincimilazzo.edu.it'
);