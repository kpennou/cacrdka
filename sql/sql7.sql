-- =========================
-- EVALUATIONS (cohorte + matière)
-- =========================
CREATE TABLE IF NOT EXISTS evaluations (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  cohorte_id BIGINT UNSIGNED NOT NULL,
  matiere_id BIGINT UNSIGNED NOT NULL,
  libelle VARCHAR(150) NOT NULL,
  date_eval DATE NULL,
  note_sur DECIMAL(6,2) NOT NULL DEFAULT 20.00,
  statut ENUM('BROUILLON','PUBLIEE','CLOTUREE') NOT NULL DEFAULT 'BROUILLON',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_eval_cohorte (cohorte_id),
  INDEX idx_eval_matiere (matiere_id),
  INDEX idx_eval_statut (statut),

  CONSTRAINT fk_eval_cohorte
    FOREIGN KEY (cohorte_id) REFERENCES cohortes(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_eval_matiere
    FOREIGN KEY (matiere_id) REFERENCES matieres(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- NOTES (une note par apprenant par évaluation)
-- =========================
CREATE TABLE IF NOT EXISTS notes (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  evaluation_id BIGINT UNSIGNED NOT NULL,
  apprenant_id BIGINT UNSIGNED NOT NULL,
  note DECIMAL(6,2) NULL,          -- NULL = non saisi
  absent TINYINT(1) NOT NULL DEFAULT 0,
  remarque VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_note (evaluation_id, apprenant_id),
  INDEX idx_note_eval (evaluation_id),

  CONSTRAINT fk_note_eval
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_note_apprenant
    FOREIGN KEY (apprenant_id) REFERENCES apprenants(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- VUE : liste évaluations (avec cohorte + matière)
-- =========================
CREATE OR REPLACE VIEW vw_evaluations_list AS
SELECT
  e.id,
  e.cohorte_id,
  c.libelle AS cohorte,
  e.matiere_id,
  m.nom AS matiere,
  e.libelle,
  e.date_eval,
  e.note_sur,
  e.statut,
  e.created_at
FROM evaluations e
JOIN cohortes c ON c.id = e.cohorte_id
JOIN matieres m ON m.id = e.matiere_id;


-- =========================
-- VUE KPI : nb inscrits vs notes saisies (par évaluation)
-- =========================
CREATE OR REPLACE VIEW vw_eval_kpi AS
SELECT
  e.id AS evaluation_id,
  e.cohorte_id,
  c.libelle AS cohorte,
  e.matiere_id,
  m.nom AS matiere,
  e.libelle,
  e.statut,

  -- Nombre inscrits dans la cohorte
  (
    SELECT COUNT(*)
    FROM inscriptions i
    WHERE i.cohorte_id = e.cohorte_id
  ) AS nb_inscrits,

  -- Notes réellement saisies
  (
    SELECT COUNT(*)
    FROM notes n
    WHERE n.evaluation_id = e.id
      AND (n.note IS NOT NULL OR n.absent = 1)
  ) AS nb_notes_saisies,

  -- Notes manquantes
  (
    (
      SELECT COUNT(*)
      FROM inscriptions i
      WHERE i.cohorte_id = e.cohorte_id
    )
    -
    (
      SELECT COUNT(*)
      FROM notes n
      WHERE n.evaluation_id = e.id
        AND (n.note IS NOT NULL OR n.absent = 1)
    )
  ) AS nb_notes_manquantes

FROM evaluations e
JOIN cohortes c ON c.id = e.cohorte_id
JOIN matieres m ON m.id = e.matiere_id;



-- Top 10 évaluations avec notes manquantes (directeur)
CREATE OR REPLACE VIEW vw_kpi_notes_manquantes_top10 AS
SELECT *
FROM vw_eval_kpi
WHERE nb_notes_manquantes > 0
ORDER BY nb_notes_manquantes DESC, evaluation_id DESC
LIMIT 10;
