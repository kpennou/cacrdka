CREATE OR REPLACE VIEW vw_paiements_journal AS
SELECT
  p.id AS paiement_id,
  p.date_paiement,
  p.montant,
  p.mode,
  p.reference,
  p.commentaire,
  p.inscription_id,

  c.id AS cohorte_id,
  c.libelle AS cohorte,

  a.id AS apprenant_id,
  a.matricule,
  CONCAT(a.nom,' ',a.prenoms) AS apprenant,
  a.telephone

FROM paiements p
JOIN inscriptions i ON i.id=p.inscription_id
JOIN cohortes c ON c.id=i.cohorte_id
JOIN apprenants a ON a.id=i.apprenant_id;
