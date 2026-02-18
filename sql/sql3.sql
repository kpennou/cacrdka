-- =========================
-- CACRDKA - PACK FORMATEURS (corrigé)
-- =========================

-- 1) preinscriptions_formateurs
CREATE TABLE IF NOT EXISTS preinscriptions_formateurs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

  metier_id BIGINT UNSIGNED NULL, -- NULL si "Autre"
  nom VARCHAR(100) NOT NULL,
  prenoms VARCHAR(150) NOT NULL,
  telephone VARCHAR(30) NOT NULL,
  email VARCHAR(190) NULL,
  specialites VARCHAR(255) NULL, -- obligatoire côté app si metier_id IS NULL (Autre)

  statut ENUM('BROUILLON','SOUMIS','RETENU','REJETE','CONVERTI')
    NOT NULL DEFAULT 'BROUILLON',

  is_locked TINYINT(1) NOT NULL DEFAULT 0,

  submitted_at DATETIME NULL,
  retenu_at DATETIME NULL,
  rejected_at DATETIME NULL,
  converted_at DATETIME NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_form_tel (telephone),
  INDEX idx_form_statut (statut),
  INDEX idx_form_metier (metier_id),

  CONSTRAINT fk_form_metier
    FOREIGN KEY (metier_id) REFERENCES metiers(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) types pièces formateurs
CREATE TABLE IF NOT EXISTS formateur_piece_types (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(50) NOT NULL UNIQUE,
  libelle VARCHAR(150) NOT NULL,
  is_obligatoire TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  ordre INT NOT NULL DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) pièces formateurs
CREATE TABLE IF NOT EXISTS formateur_pieces (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  preinscription_id BIGINT UNSIGNED NOT NULL,
  piece_type_id BIGINT UNSIGNED NOT NULL,

  fichier_path VARCHAR(255) NOT NULL,
  fichier_nom_original VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NULL,
  taille_octets BIGINT UNSIGNED NULL,

  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uk_form_piece_unique (preinscription_id, piece_type_id),

  CONSTRAINT fk_form_piece_preinsc
    FOREIGN KEY (preinscription_id) REFERENCES preinscriptions_formateurs(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_form_piece_type
    FOREIGN KEY (piece_type_id) REFERENCES formateur_piece_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) seeds types
INSERT INTO formateur_piece_types (code, libelle, is_obligatoire, is_active, ordre)
SELECT 'CV', 'Curriculum Vitae (CV)', 1, 1, 10
WHERE NOT EXISTS (SELECT 1 FROM formateur_piece_types WHERE code='CV');

INSERT INTO formateur_piece_types (code, libelle, is_obligatoire, is_active, ordre)
SELECT 'LETTRE', 'Lettre de motivation', 0, 1, 20
WHERE NOT EXISTS (SELECT 1 FROM formateur_piece_types WHERE code='LETTRE');

-- 5) vues
CREATE OR REPLACE VIEW vw_formateur_completude AS
SELECT
  f.id AS preinscription_id,
  COUNT(DISTINCT t.id) AS nb_pieces_obligatoires,
  COUNT(DISTINCT p.piece_type_id) AS nb_pieces_fournies,
  (COUNT(DISTINCT t.id) = COUNT(DISTINCT p.piece_type_id)) AS is_complet
FROM preinscriptions_formateurs f
JOIN formateur_piece_types t
  ON t.is_obligatoire=1 AND t.is_active=1
LEFT JOIN formateur_pieces p
  ON p.preinscription_id=f.id AND p.piece_type_id=t.id
GROUP BY f.id;

CREATE OR REPLACE VIEW vw_formateur_pieces_list AS
SELECT
  p.id AS piece_id,
  p.preinscription_id,
  t.code,
  t.libelle,
  t.is_obligatoire,
  p.fichier_nom_original,
  p.fichier_path,
  p.mime_type,
  p.taille_octets,
  p.uploaded_at
FROM formateur_pieces p
JOIN formateur_piece_types t ON t.id=p.piece_type_id;
