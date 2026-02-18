-- SQL complet V1 Finance (sans tranches)
-- =========================
-- TARIF PAR COHORTE
-- =========================
CREATE TABLE IF NOT EXISTS cohorte_tarifs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  cohorte_id BIGINT UNSIGNED NOT NULL UNIQUE,
  montant_total DECIMAL(12,2) NOT NULL,
  date_limite_paiement DATE NULL, -- optionnel : utile pour les retards
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_ct_cohorte
    FOREIGN KEY (cohorte_id) REFERENCES cohortes(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- SNAPSHOT FINANCE PAR INSCRIPTION (figé)
-- =========================
CREATE TABLE IF NOT EXISTS inscription_finance (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  inscription_id BIGINT UNSIGNED NOT NULL UNIQUE,

  montant_total DECIMAL(12,2) NOT NULL,
  bourse_montant DECIMAL(12,2) NOT NULL DEFAULT 0.00, -- réduction
  montant_net DECIMAL(12,2) NOT NULL,                 -- total - bourse

  date_limite_paiement DATE NULL,
  statut ENUM('EN_COURS','SOLDE','ANNULE') NOT NULL DEFAULT 'EN_COURS',

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_if_inscription
    FOREIGN KEY (inscription_id) REFERENCES inscriptions(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- PAIEMENTS (plusieurs versements possibles)
-- =========================
CREATE TABLE IF NOT EXISTS paiements (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  inscription_id BIGINT UNSIGNED NOT NULL,
  montant DECIMAL(12,2) NOT NULL,
  date_paiement DATE NOT NULL,
  mode ENUM('ESPECES','MOBILE_MONEY','VIREMENT','CHEQUE','AUTRE') NOT NULL DEFAULT 'MOBILE_MONEY',
  reference VARCHAR(100) NULL,
  commentaire VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_pay_insc (inscription_id),
  INDEX idx_pay_date (date_paiement),

  CONSTRAINT fk_pay_inscription
    FOREIGN KEY (inscription_id) REFERENCES inscriptions(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- VUE : finance par inscription (encaissé, reste, retard)
-- Adapte à tes colonnes: cohortes.libelle existe
-- =========================
CREATE OR REPLACE VIEW vw_inscriptions_finance AS
SELECT
  i.id AS inscription_id,
  i.apprenant_id,
  i.cohorte_id,
  c.libelle AS cohorte,

  f.montant_total,
  f.bourse_montant,
  f.montant_net,

  COALESCE((SELECT SUM(p.montant) FROM paiements p WHERE p.inscription_id=i.id), 0) AS total_paye,
  (f.montant_net - COALESCE((SELECT SUM(p.montant) FROM paiements p WHERE p.inscription_id=i.id), 0)) AS reste_a_payer,

  f.date_limite_paiement,
  (f.date_limite_paiement IS NOT NULL AND CURDATE() > f.date_limite_paiement AND
    (f.montant_net - COALESCE((SELECT SUM(p.montant) FROM paiements p WHERE p.inscription_id=i.id), 0)) > 0
  ) AS en_retard,

  f.statut
FROM inscriptions i
JOIN cohortes c ON c.id=i.cohorte_id
JOIN inscription_finance f ON f.inscription_id=i.id;

-- =========================
-- KPI directeur : Top 10 retards
-- =========================
CREATE OR REPLACE VIEW vw_kpi_impayes_top10 AS
SELECT *
FROM vw_inscriptions_finance
WHERE en_retard=1
ORDER BY reste_a_payer DESC, inscription_id DESC
LIMIT 10;

-- =========================
-- KPI directeur : global
-- =========================
CREATE OR REPLACE VIEW vw_kpi_finance_global AS
SELECT
  (SELECT COALESCE(SUM(montant_net),0) FROM inscription_finance WHERE statut='EN_COURS')
    AS total_a_encaisser,
  (SELECT COALESCE(SUM(p.montant),0) FROM paiements p)
    AS total_encaisse,
  (
    (SELECT COALESCE(SUM(montant_net),0) FROM inscription_finance WHERE statut='EN_COURS')
    -
    (SELECT COALESCE(SUM(p.montant),0) FROM paiements p)
  ) AS total_restant;
