-- Script SQL pour ajouter la colonne result à la table task
ALTER TABLE task ADD COLUMN result LONGTEXT DEFAULT NULL;
