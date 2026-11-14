-- ============================================================
-- DaVinciDocs — Schema fisico (MySQL / InnoDB)
-- Da importare in phpMyAdmin: crea il database "davincidocs"
-- (o un nome a scelta) ed esegui questo script nella scheda SQL.
-- Crea SOLO la struttura (le tabelle): i dati si caricano poi con
-- sql/inserimento_dati.sql (indirizzi, categorie, materie, quadro orario)
-- e sql/dati_test.sql (utenti e credenziali di prova).
-- ============================================================

CREATE DATABASE IF NOT EXISTS davincidocs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE davincidocs;

-- Tabelle indipendenti --------------------------------------------------

CREATE TABLE IF NOT EXISTS INDIRIZZO (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    nome    VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Tipo di materiale (dispensa, slide, verifica...): una tabella invece di un
-- ENUM, cosi' una nuova categoria si aggiunge con un INSERT, senza ALTER TABLE.
CREATE TABLE IF NOT EXISTS CATEGORIA (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    nome    VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Ogni materia compare una sola volta (Matematica, Inglese... sono comuni a
-- piu' indirizzi): in quali indirizzi e anni si studia lo dice PIANO_STUDI.
CREATE TABLE IF NOT EXISTS MATERIA (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    nome    VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Solo il personale ha un account: gli studenti consultano i materiali da
-- ospiti ("Esplora i materiali senza registrarti"). Per questo il tipo di
-- utente e' uno solo (tipo_utente); la vecchia colonna "ruolo" lo ripeteva.
CREATE TABLE IF NOT EXISTS UTENTE (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    nome                 VARCHAR(50)  NOT NULL,
    cognome              VARCHAR(50)  NOT NULL,
    cell                 VARCHAR(15),
    email                VARCHAR(100) NOT NULL UNIQUE,
    tipo_utente          ENUM('docente','amministratore') NOT NULL,
    -- ===== DISATTIVAZIONE UTENTE =====
    -- disattivare un utente non ne cancella il ruolo: se torna attivo,
    -- resta docente o amministratore com'era. Un utente disattivato non
    -- puo' accedere (api/login.php) ma i suoi materiali restano.
    -- Per disattivare: UPDATE UTENTE SET attivo = FALSE WHERE id = ...;
    attivo               BOOLEAN NOT NULL DEFAULT TRUE,
    -- ===== FINE DISATTIVAZIONE UTENTE =====
    tempo_indeterminato  BOOLEAN,
    CONSTRAINT chk_utente_email_dominio
        CHECK (email LIKE '%@davincimilazzo.edu.it')
) ENGINE=InnoDB;

-- Tabelle che dipendono dal primo livello --------------------------------

-- NB: qui la chiave primaria e' solo username (non piu' username+password
-- come nella versione "didattica" del documento di riferimento): una
-- password con hash non si puo' usare come parte di una chiave di ricerca,
-- password_verify() e' l'unico modo corretto per confrontarla. Per
-- convenzione lo username coincide con l'email istituzionale.
CREATE TABLE IF NOT EXISTS CREDENZIALI (
    username    VARCHAR(100) NOT NULL PRIMARY KEY,
    password    VARCHAR(255) NOT NULL,
    utente_id   INT          NOT NULL UNIQUE,
    data_acc    DATETIME,
    FOREIGN KEY (utente_id) REFERENCES UTENTE(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Quadro orario: la materia X si studia nell'indirizzo Y al Z anno.
-- Una riga per ogni combinazione (es. Matematica compare per tutti e
-- sei gli indirizzi e per tutti e cinque gli anni).
CREATE TABLE IF NOT EXISTS PIANO_STUDI (
    indirizzo_id   INT     NOT NULL,
    materia_id     INT     NOT NULL,
    anno           TINYINT NOT NULL,
    PRIMARY KEY (indirizzo_id, materia_id, anno),
    FOREIGN KEY (indirizzo_id) REFERENCES INDIRIZZO(id),
    FOREIGN KEY (materia_id)   REFERENCES MATERIA(id),
    CONSTRAINT chk_piano_anno CHECK (anno BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS INSEGNA (
    docente_id   INT NOT NULL,
    materia_id   INT NOT NULL,
    PRIMARY KEY (docente_id, materia_id),
    FOREIGN KEY (docente_id) REFERENCES UTENTE(id),
    FOREIGN KEY (materia_id) REFERENCES MATERIA(id)
) ENGINE=InnoDB;

-- Tabelle che dipendono dal secondo livello -------------------------------

-- Un materiale vale per UN indirizzo, una materia e un anno (es. Informatica,
-- 1 anno, indirizzo Turismo): un docente di Informatica puo' insegnare anche al
-- biennio di altri indirizzi, quindi l'indirizzo va scelto al caricamento.
-- La chiave esterna composta (indirizzo_id, materia_id, anno) -> PIANO_STUDI
-- garantisce che la combinazione esista nel quadro orario: non si puo' caricare
-- un materiale per una materia/anno che quell'indirizzo non ha. (E' possibile
-- perche' la chiave primaria di PIANO_STUDI e' proprio questa terna.)
CREATE TABLE IF NOT EXISTS MATERIALE (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    titolo             VARCHAR(150) NOT NULL,
    descrizione        TEXT,
    tipo_file          VARCHAR(10)  NOT NULL,
    path_file          VARCHAR(255) NOT NULL,
    nome_file          VARCHAR(255),
    dimensione_kb      INT,
    data_caricamento   DATETIME DEFAULT CURRENT_TIMESTAMP,
    docente_id         INT NOT NULL,
    indirizzo_id       INT NOT NULL,
    materia_id         INT NOT NULL,
    anno               TINYINT NOT NULL,
    categoria_id       INT NOT NULL,
    FOREIGN KEY (docente_id) REFERENCES UTENTE(id),
    FOREIGN KEY (categoria_id) REFERENCES CATEGORIA(id),
    FOREIGN KEY (indirizzo_id, materia_id, anno)
        REFERENCES PIANO_STUDI(indirizzo_id, materia_id, anno),
    CONSTRAINT chk_materiale_anno CHECK (anno BETWEEN 1 AND 5)
) ENGINE=InnoDB;
