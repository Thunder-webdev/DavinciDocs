-- ============================================================
-- DaVinciDocs — Inserimento dei dati fissi
-- Da eseguire in phpMyAdmin DOPO sql/davincidocs.sql (che crea le
-- tabelle), sullo stesso database. Carica: indirizzi, categorie,
-- materie e quadro orario (piano di studi).
--
-- Rieseguibile: non crea doppioni. Tutte le tabelle usano INSERT IGNORE:
-- una riga gia' presente (stessa chiave primaria o UNIQUE) viene saltata
-- senza errore, quelle nuove vengono aggiunte. Attenzione: una riga gia'
-- presente NON viene aggiornata (es. un nome corretto qui resta vecchio nel DB).
-- Dopo questo script si puo' caricare sql/dati_test.sql.
-- ============================================================

USE davincidocs;

-- I sei indirizzi dell'ITET Leonardo da Vinci, con la denominazione ufficiale
-- del riordino degli istituti tecnici. Id espliciti e INSERT IGNORE come per
-- le categorie.
INSERT IGNORE INTO INDIRIZZO (id, nome) VALUES
(1, 'Amministrazione, Finanza e Marketing'),
(2, 'Costruzioni, Ambiente e Territorio'),
(3, 'Grafica e Comunicazione'),
(4, 'Turismo'),
(5, 'Trasporti e Logistica'),
(6, 'Informatica e Telecomunicazioni');

-- Categorie di partenza. Id espliciti: la pagina li usa come valori del menu
-- "Categoria". INSERT IGNORE: rieseguire lo script non crea doppioni.
INSERT IGNORE INTO CATEGORIA (id, nome) VALUES
(1, 'Dispensa'),
(2, 'Esercitazione'),
(3, 'Slide'),
(4, 'Verifica'),
(5, 'Esercizi svolti'),
(6, 'Altro');

-- Materie di partenza (poche, per provare il caricamento): le stesse del menu
-- "Materia" del form. Id espliciti e INSERT IGNORE, come per indirizzi e categorie.
INSERT IGNORE INTO MATERIA (id, nome) VALUES
(1, 'Telecomunicazioni'),
(2, 'Informatica'),
(3, 'Sistemi e Reti'),
(4, 'TPSI'),
(5, 'GPOI');

-- Quadro orario di partenza (piano di studi).
-- Una riga per ogni combinazione indirizzo / materia / anno, con gli id:
--   indirizzi: 1 AFM, 2 CAT, 3 Grafica, 4 Turismo, 5 Trasporti e Logistica,
--              6 Informatica e Telecomunicazioni (come nell'INSERT in alto)
--   materie:   1 Telecomunicazioni, 2 Informatica, 3 Sistemi e Reti,
--              4 TPSI, 5 GPOI
-- Indirizzo 6, triennio:
--   Informatica, Sistemi e Reti, TPSI: 3, 4 e 5 anno
--   Telecomunicazioni:                 3 e 4 anno
--   GPOI:                              solo 5 anno
-- Biennio: l'Informatica si insegna al 1 e 2 anno in TUTTI gli indirizzi
-- (un docente di Informatica puo' avere classi del biennio ovunque).
-- INSERT IGNORE: le righe gia' presenti si saltano, quelle nuove si aggiungono.
-- (Svuotare la tabella con DELETE FROM PIANO_STUDI non si puo' piu', se ci sono
-- materiali caricati: MATERIALE la usa come chiave esterna.)
INSERT IGNORE INTO PIANO_STUDI (indirizzo_id, materia_id, anno) VALUES
(1, 2, 1), (1, 2, 2),
(2, 2, 1), (2, 2, 2),
(3, 2, 1), (3, 2, 2),
(4, 2, 1), (4, 2, 2),
(5, 2, 1), (5, 2, 2),
(6, 2, 1), (6, 2, 2);

INSERT IGNORE INTO PIANO_STUDI (indirizzo_id, materia_id, anno) VALUES
(6, 1, 3), (6, 1, 4),
(6, 2, 3), (6, 2, 4), (6, 2, 5),
(6, 3, 3), (6, 3, 4), (6, 3, 5),
(6, 4, 3), (6, 4, 4), (6, 4, 5),
(6, 5, 5);
