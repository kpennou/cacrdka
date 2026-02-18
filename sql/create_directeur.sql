-- Remplace COLLE_ICI_LE_HASH par la valeur générée (public/hash.php)
INSERT INTO users (role_id, nom, prenoms, username, password_hash, is_active)
SELECT r.id, 'DIRECTEUR', 'CACRDKA', 'directeur', 'COLLE_ICI_LE_HASH', 1
FROM roles r WHERE r.code='DIRECTEUR';
