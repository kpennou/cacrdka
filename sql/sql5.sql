-- Vue liste équipe formateurs
CREATE OR REPLACE VIEW vw_formateurs_list AS
SELECT
  f.id,
  f.matricule,
  COALESCE(m.nom, 'Autre') AS metier_nom,
  f.metier_id,
  f.nom,
  f.prenoms,
  f.telephone,
  f.email,
  f.specialites,
  f.statut,
  f.created_at
FROM formateurs f
LEFT JOIN metiers m ON m.id = f.metier_id;
