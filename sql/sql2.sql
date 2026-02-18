-- 1) Top métiers (volume dossiers)
CREATE OR REPLACE VIEW vw_kpi_top_metiers AS
SELECT
  m.id AS metier_id,
  m.nom AS metier,
  COUNT(*) AS nb_dossiers,
  SUM(p.statut='SOUMIS') AS nb_soumis,
  SUM(p.statut='BROUILLON') AS nb_brouillons,
  SUM(p.statut='SELECTIONNE') AS nb_selectionnes,
  SUM(p.statut='REJETE') AS nb_rejetes,
  SUM(p.statut='CONVERTI') AS nb_convertis
FROM preinscriptions_apprenants p
JOIN metiers m ON m.id=p.metier_id
GROUP BY m.id, m.nom
ORDER BY nb_dossiers DESC;

-- 2) Incomplets (brouillons + soumis incomplets) par métier
CREATE OR REPLACE VIEW vw_kpi_incomplets_par_metier AS
SELECT
  m.id AS metier_id,
  m.nom AS metier,
  COUNT(*) AS nb_incomplets
FROM preinscriptions_apprenants p
JOIN metiers m ON m.id=p.metier_id
JOIN vw_preinscription_completude c ON c.preinscription_id=p.id
WHERE p.statut IN ('BROUILLON','SOUMIS') AND c.is_complet=0
GROUP BY m.id, m.nom
ORDER BY nb_incomplets DESC;

-- 3) Derniers dossiers soumis (10)
CREATE OR REPLACE VIEW vw_kpi_derniers_soumis AS
SELECT
  p.id,
  p.nom,
  p.prenoms,
  p.telephone,
  m.nom AS metier,
  p.submitted_at
FROM preinscriptions_apprenants p
JOIN metiers m ON m.id=p.metier_id
WHERE p.statut='SOUMIS'
ORDER BY p.submitted_at DESC
LIMIT 10;
