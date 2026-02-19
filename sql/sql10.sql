-- SQL : vues “Finance par cohorte” + Top retards par cohorte
-- =========================
-- KPI Finance par cohorte
-- =========================
CREATE OR REPLACE VIEW vw_kpi_finance_par_cohorte AS
SELECT
  c.id AS cohorte_id,
  c.libelle AS cohorte,
  c.statut,
  ct.montant_total AS tarif_cohorte,
  ct.date_limite_paiement,

  -- Nombre d'inscriptions (même sans snapshot)
  (SELECT COUNT(*) FROM inscriptions i WHERE i.cohorte_id=c.id) AS nb_inscrits,

  -- Nombre de snapshots finance existants
  (SELECT COUNT(*) FROM inscriptions i
    JOIN inscription_finance f ON f.inscription_id=i.id
    WHERE i.cohorte_id=c.id AND f.statut!='ANNULE'
  ) AS nb_snapshots,

  -- Montant net total à encaisser (somme des nets)
  (SELECT COALESCE(SUM(f.montant_net),0) FROM inscriptions i
    JOIN inscription_finance f ON f.inscription_id=i.id
    WHERE i.cohorte_id=c.id AND f.statut!='ANNULE'
  ) AS total_a_encaisser,

  -- Total encaissé (paiements des inscriptions de la cohorte)
  (SELECT COALESCE(SUM(p.montant),0)
   FROM inscriptions i
   JOIN paiements p ON p.inscription_id=i.id
   WHERE i.cohorte_id=c.id
  ) AS total_encaisse,

  -- Total bourses (réductions)
  (SELECT COALESCE(SUM(f.bourse_montant),0) FROM inscriptions i
    JOIN inscription_finance f ON f.inscription_id=i.id
    WHERE i.cohorte_id=c.id AND f.statut!='ANNULE'
  ) AS total_bourses,

  -- Reste à encaisser
  (
    (SELECT COALESCE(SUM(f.montant_net),0) FROM inscriptions i
      JOIN inscription_finance f ON f.inscription_id=i.id
      WHERE i.cohorte_id=c.id AND f.statut!='ANNULE'
    )
    -
    (SELECT COALESCE(SUM(p.montant),0)
     FROM inscriptions i
     JOIN paiements p ON p.inscription_id=i.id
     WHERE i.cohorte_id=c.id
    )
  ) AS total_restant,

  -- Nb retards (date limite dépassée + reste>0)
  (SELECT COUNT(*)
   FROM inscriptions i
   JOIN inscription_finance f ON f.inscription_id=i.id
   WHERE i.cohorte_id=c.id
     AND f.statut!='ANNULE'
     AND f.date_limite_paiement IS NOT NULL
     AND CURDATE() > f.date_limite_paiement
     AND (
       f.montant_net - COALESCE((SELECT SUM(p.montant) FROM paiements p WHERE p.inscription_id=i.id),0)
     ) > 0
  ) AS nb_retards

FROM cohortes c
LEFT JOIN cohorte_tarifs ct ON ct.cohorte_id=c.id;

-- =========================
-- Top 10 cohortes par reste à encaisser
-- =========================
CREATE OR REPLACE VIEW vw_kpi_finance_cohortes_top_restant AS
SELECT *
FROM vw_kpi_finance_par_cohorte
ORDER BY total_restant DESC, cohorte_id DESC
LIMIT 10;


