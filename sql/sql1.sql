CREATE OR REPLACE VIEW vw_preinscription_pieces_list AS
SELECT
  pp.id AS piece_id,
  pp.preinscription_id,
  pt.id AS piece_type_id,
  pt.code,
  pt.libelle,
  pt.is_obligatoire,
  pp.fichier_nom_original,
  pp.fichier_path,
  pp.mime_type,
  pp.taille_octets,
  pp.uploaded_at
FROM preinscription_pieces pp
JOIN preinscription_piece_types pt ON pt.id = pp.piece_type_id;