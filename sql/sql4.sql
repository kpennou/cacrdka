-- =========================
-- TABLE EQUIPE FORMATEURS
-- =========================
CREATE TABLE IF NOT EXISTS formateurs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  matricule VARCHAR(40) NOT NULL UNIQUE,
  metier_id BIGINT UNSIGNED NULL, -- NULL si "Autre"
  nom VARCHAR(100) NOT NULL,
  prenoms VARCHAR(150) NOT NULL,
  telephone VARCHAR(30) NULL,
  email VARCHAR(190) NULL,
  specialites VARCHAR(255) NULL,
  statut ENUM('ACTIF','INACTIF') NOT NULL DEFAULT 'ACTIF',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_formateurs_metier
    FOREIGN KEY (metier_id) REFERENCES metiers(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- VUE KPI FORMATEURS (360°)
-- =========================
CREATE OR REPLACE VIEW vw_directeur_formateurs_kpi AS
SELECT
  (SELECT COUNT(*) FROM preinscriptions_formateurs) AS vivier_formateurs_total,
  (SELECT COUNT(*) FROM preinscriptions_formateurs WHERE statut='BROUILLON') AS vivier_formateurs_brouillons,
  (SELECT COUNT(*) FROM preinscriptions_formateurs WHERE statut='SOUMIS') AS vivier_formateurs_soumis,
  (SELECT COUNT(*) FROM preinscriptions_formateurs WHERE statut='RETENU') AS vivier_formateurs_retenus,
  (SELECT COUNT(*) FROM preinscriptions_formateurs WHERE statut='REJETE') AS vivier_formateurs_rejetes,
  (SELECT COUNT(*) FROM preinscriptions_formateurs WHERE statut='CONVERTI') AS vivier_formateurs_convertis,
  (SELECT COUNT(*) FROM formateurs WHERE statut='ACTIF') AS equipe_formateurs_actifs,
  (SELECT COUNT(*)
     FROM preinscriptions_formateurs f
     JOIN vw_formateur_completude c ON c.preinscription_id=f.id
    WHERE f.statut IN ('BROUILLON','SOUMIS') AND c.is_complet=0
  ) AS vivier_formateurs_incomplets;

-- Top métiers côté formateurs (vivier)
CREATE OR REPLACE VIEW vw_kpi_formateurs_top_metiers AS
SELECT
  COALESCE(m.nom,'Autre') AS metier,
  COUNT(*) AS nb_dossiers,
  SUM(f.statut='SOUMIS') AS nb_soumis,
  SUM(f.statut='RETENU') AS nb_retenus,
  SUM(f.statut='CONVERTI') AS nb_convertis
FROM preinscriptions_formateurs f
LEFT JOIN metiers m ON m.id=f.metier_id
GROUP BY COALESCE(m.nom,'Autre')
ORDER BY nb_dossiers DESC;

-- Derniers formateurs soumis
CREATE OR REPLACE VIEW vw_kpi_formateurs_derniers_soumis AS
SELECT
  f.id,
  f.nom,
  f.prenoms,
  f.telephone,
  COALESCE(m.nom,'Autre') AS metier,
  f.submitted_at
FROM preinscriptions_formateurs f
LEFT JOIN metiers m ON m.id=f.metier_id
WHERE f.statut='SOUMIS'
ORDER BY f.submitted_at DESC
LIMIT 10;
