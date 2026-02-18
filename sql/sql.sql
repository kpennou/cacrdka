USE cacrdka;

SET FOREIGN_KEY_CHECKS=0;

DROP VIEW IF EXISTS vw_preinscription_completude;
DROP VIEW IF EXISTS vw_directeur_viviers_kpi;

DROP TABLE IF EXISTS preinscription_pieces;
DROP TABLE IF EXISTS preinscription_piece_types;
DROP TABLE IF EXISTS preinscriptions_apprenants;

DROP TABLE IF EXISTS conversions;
DROP TABLE IF EXISTS inscriptions;
DROP TABLE IF EXISTS apprenants;

DROP TABLE IF EXISTS cohortes;
DROP TABLE IF EXISTS metiers;

DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS=1;

-- ROLES / USERS
CREATE TABLE roles (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(50) NOT NULL UNIQUE,
  libelle VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  role_id BIGINT UNSIGNED NOT NULL,
  nom VARCHAR(100) NOT NULL,
  prenoms VARCHAR(150) NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (code, libelle)
SELECT 'DIRECTEUR','Directeur' UNION ALL
SELECT 'ADMIN','Administrateur système';

-- METIERS / COHORTES
CREATE TABLE metiers (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(150) NOT NULL UNIQUE,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cohortes (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  metier_id BIGINT UNSIGNED NOT NULL,
  libelle VARCHAR(160) NOT NULL,
  date_debut DATE NOT NULL,
  date_fin DATE NOT NULL,
  capacite INT NULL,
  statut ENUM('PLANIFIEE','EN_COURS','CLOTUREE') NOT NULL DEFAULT 'PLANIFIEE',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cohorte (metier_id, libelle),
  INDEX idx_cohorte_metier (metier_id, statut),
  CONSTRAINT fk_cohorte_metier FOREIGN KEY (metier_id) REFERENCES metiers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- APPRENANTS / INSCRIPTIONS / CONVERSIONS
CREATE TABLE apprenants (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  matricule VARCHAR(40) NOT NULL UNIQUE,
  nom VARCHAR(100) NOT NULL,
  prenoms VARCHAR(150) NULL,
  telephone VARCHAR(30) NULL,
  date_naissance DATE NULL,
  sexe ENUM('M','F') NULL,
  niveau_etude VARCHAR(120) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inscriptions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  apprenant_id BIGINT UNSIGNED NOT NULL,
  cohorte_id BIGINT UNSIGNED NOT NULL,
  statut ENUM('INSCRIT','SUSPENDU','ABANDON','TERMINE') NOT NULL DEFAULT 'INSCRIT',
  date_inscription DATE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_inscription (apprenant_id, cohorte_id),
  CONSTRAINT fk_insc_apprenant FOREIGN KEY (apprenant_id) REFERENCES apprenants(id),
  CONSTRAINT fk_insc_cohorte FOREIGN KEY (cohorte_id) REFERENCES cohortes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE conversions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  type ENUM('APPRENANT') NOT NULL,
  source_preinscription_id BIGINT UNSIGNED NOT NULL,
  cible_apprenant_id BIGINT UNSIGNED NOT NULL,
  converted_by BIGINT UNSIGNED NULL,
  converted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_conv (type, source_preinscription_id),
  CONSTRAINT fk_conv_user FOREIGN KEY (converted_by) REFERENCES users(id),
  CONSTRAINT fk_conv_apprenant FOREIGN KEY (cible_apprenant_id) REFERENCES apprenants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- VIVIER APPRENANTS
CREATE TABLE preinscriptions_apprenants (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

  nom VARCHAR(100) NOT NULL,
  prenoms VARCHAR(150) NOT NULL,
  telephone VARCHAR(30) NOT NULL,
  date_naissance DATE NOT NULL,
  sexe ENUM('M','F') NOT NULL,
  niveau_etude VARCHAR(100) NOT NULL,

  metier_id BIGINT UNSIGNED NOT NULL,

  statut ENUM('BROUILLON','SOUMIS','SELECTIONNE','REJETE','CONVERTI')
    NOT NULL DEFAULT 'BROUILLON',

  is_locked TINYINT(1) NOT NULL DEFAULT 0,

  submitted_at DATETIME NULL,
  selected_at DATETIME NULL,
  rejected_at DATETIME NULL,
  converted_at DATETIME NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_preinsc_tel (telephone),
  INDEX idx_preinsc_statut (statut),
  INDEX idx_preinsc_metier (metier_id),

  CONSTRAINT fk_preinsc_metier
    FOREIGN KEY (metier_id) REFERENCES metiers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE preinscription_piece_types (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(50) NOT NULL UNIQUE,
  libelle VARCHAR(150) NOT NULL,
  is_obligatoire TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  ordre INT NOT NULL DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE preinscription_pieces (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  preinscription_id BIGINT UNSIGNED NOT NULL,
  piece_type_id BIGINT UNSIGNED NOT NULL,

  fichier_path VARCHAR(255) NOT NULL,
  fichier_nom_original VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NULL,
  taille_octets BIGINT UNSIGNED NULL,

  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uk_preinsc_piece_unique (preinscription_id, piece_type_id),
  CONSTRAINT fk_piece_preinsc
    FOREIGN KEY (preinscription_id) REFERENCES preinscriptions_apprenants(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_piece_type
    FOREIGN KEY (piece_type_id) REFERENCES preinscription_piece_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- VUES
CREATE OR REPLACE VIEW vw_preinscription_completude AS
SELECT
  p.id AS preinscription_id,
  COUNT(DISTINCT pt.id) AS nb_pieces_obligatoires,
  COUNT(DISTINCT pp.piece_type_id) AS nb_pieces_fournies,
  (COUNT(DISTINCT pt.id) = COUNT(DISTINCT pp.piece_type_id)) AS is_complet
FROM preinscriptions_apprenants p
JOIN preinscription_piece_types pt
  ON pt.is_obligatoire = 1 AND pt.is_active = 1
LEFT JOIN preinscription_pieces pp
  ON pp.preinscription_id = p.id
  AND pp.piece_type_id = pt.id
GROUP BY p.id;

CREATE OR REPLACE VIEW vw_directeur_viviers_kpi AS
SELECT
  (SELECT COUNT(*) FROM preinscriptions_apprenants) AS vivier_apprenants_total,
  (SELECT COUNT(*) FROM preinscriptions_apprenants WHERE statut='BROUILLON') AS vivier_apprenants_brouillons,
  (SELECT COUNT(*) FROM preinscriptions_apprenants WHERE statut='SOUMIS') AS vivier_apprenants_soumis,
  (SELECT COUNT(*) FROM preinscriptions_apprenants WHERE statut='SELECTIONNE') AS vivier_apprenants_selectionnes,
  (SELECT COUNT(*) FROM preinscriptions_apprenants WHERE statut='CONVERTI') AS vivier_apprenants_convertis,
  (SELECT COUNT(*)
     FROM preinscriptions_apprenants p
     JOIN vw_preinscription_completude c ON c.preinscription_id=p.id
    WHERE p.statut IN ('BROUILLON','SOUMIS') AND c.is_complet=0
  ) AS vivier_apprenants_incomplets;

-- SEEDS
INSERT INTO metiers (nom)
SELECT 'Informatique' UNION ALL
SELECT 'Couture' UNION ALL
SELECT 'Electricité bâtiment et industrielle';

INSERT INTO cohortes (metier_id, libelle, date_debut, date_fin, capacite, statut)
SELECT m.id, 'Informatique 2026-A', '2026-01-15', '2026-06-30', 30, 'PLANIFIEE' FROM metiers m WHERE m.nom='Informatique'
UNION ALL
SELECT m.id, 'Couture 2026-A', '2026-02-01', '2026-07-31', 20, 'PLANIFIEE' FROM metiers m WHERE m.nom='Couture';

INSERT INTO preinscription_piece_types (code, libelle, is_obligatoire, is_active, ordre)
SELECT 'PIECE_IDENTITE', 'Pièce d’identité', 1, 1, 10 UNION ALL
SELECT 'DIPLOME', 'Dernier diplôme / attestation', 1, 1, 20 UNION ALL
SELECT 'PHOTO', 'Photo d’identité', 1, 1, 30;
