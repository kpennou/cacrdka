-- SQL : vue KPI bourses (optionnelle mais utile)

CREATE OR REPLACE VIEW vw_kpi_bourses_global AS
SELECT
  COALESCE(SUM(bourse_montant),0) AS total_bourses,
  COALESCE(COUNT(*),0) AS nb_inscriptions_avec_snapshot
FROM inscription_finance
WHERE statut != 'ANNULE';
