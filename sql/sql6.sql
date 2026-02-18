-- =========================
-- MATIERES (par métier)
-- =========================
CREATE TABLE IF NOT EXISTS matieres (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  metier_id BIGINT UNSIGNED NOT NULL,
  nom VARCHAR(150) NOT NULL,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uq_metier_matiere (metier_id, nom),
  INDEX idx_matiere_metier (metier_id, is_active),

  CONSTRAINT fk_matiere_metier
    FOREIGN KEY (metier_id) REFERENCES metiers(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- AFFECTATION FORMATEUR <-> MATIERE
-- =========================
CREATE TABLE IF NOT EXISTS formateur_matieres (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  formateur_id BIGINT UNSIGNED NOT NULL,
  matiere_id BIGINT UNSIGNED NOT NULL,
  statut ENUM('ACTIF','INACTIF') NOT NULL DEFAULT 'ACTIF',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uq_formateur_matiere (formateur_id, matiere_id),
  INDEX idx_fm_formateur (formateur_id),
  INDEX idx_fm_matiere (matiere_id),

  CONSTRAINT fk_fm_formateur
    FOREIGN KEY (formateur_id) REFERENCES formateurs(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_fm_matiere
    FOREIGN KEY (matiere_id) REFERENCES matieres(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- VUE : liste matières
-- =========================
CREATE OR REPLACE VIEW vw_matieres_list AS
SELECT
  ma.id,
  ma.nom,
  ma.is_active,
  ma.created_at,
  me.id AS metier_id,
  me.nom AS metier_nom
FROM matieres ma
JOIN metiers me ON me.id = ma.metier_id;

-- =========================
-- VUE : affectations (formateur -> matières)
-- =========================
CREATE OR REPLACE VIEW vw_formateur_matieres_list AS
SELECT
  fm.id,
  fm.statut,
  fm.created_at,
  f.id AS formateur_id,
  f.matricule,
  CONCAT(f.nom,' ',f.prenoms) AS formateur_nom,
  COALESCE(me.nom,'Autre') AS formateur_metier,
  ma.id AS matiere_id,
  ma.nom AS matiere_nom,
  me2.id AS matiere_metier_id,
  me2.nom AS matiere_metier_nom
FROM formateur_matieres fm
JOIN formateurs f ON f.id = fm.formateur_id
LEFT JOIN metiers me ON me.id = f.metier_id
JOIN matieres ma ON ma.id = fm.matiere_id
JOIN metiers me2 ON me2.id = ma.metier_id;
