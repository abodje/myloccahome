-- Ajouter le champ photos à la table property
-- Ce champ stocke un tableau JSON de noms de fichiers photos

ALTER TABLE property
ADD COLUMN photos JSON NULL COMMENT 'Photos de la propriété (tableau de noms de fichiers)';

-- Exemple de données pour tester (optionnel)
-- UPDATE property
-- SET photos = JSON_ARRAY('photo1.jpg', 'photo2.jpg', 'photo3.jpg')
-- WHERE id = 1;

-- Vérifier que la colonne a été ajoutée
DESCRIBE property;
