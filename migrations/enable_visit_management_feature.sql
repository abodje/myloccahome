-- Script SQL pour activer la fonctionnalité "visit_management" pour une organisation
-- Cette fonctionnalité permet la gestion des visites, réservations et scoring de candidatures

-- ========================================
-- ACTIVER POUR UNE ORGANISATION SPÉCIFIQUE
-- ========================================
-- Remplacez 'ID_ORGANIZATION' par l'ID de votre organisation

-- Exemple : Activer pour l'organisation ID 1
UPDATE organization
SET features = JSON_ARRAY_APPEND(COALESCE(features, '[]'), '$', 'visit_management')
WHERE id = 1
AND NOT JSON_CONTAINS(COALESCE(features, '[]'), '"visit_management"');

-- ========================================
-- ACTIVER POUR TOUTES LES ORGANISATIONS
-- ========================================
-- Décommentez la requête ci-dessous si vous voulez activer pour TOUTES les organisations

-- UPDATE organization
-- SET features = JSON_ARRAY_APPEND(COALESCE(features, '[]'), '$', 'visit_management')
-- WHERE NOT JSON_CONTAINS(COALESCE(features, '[]'), '"visit_management"');

-- ========================================
-- VÉRIFIER LES ORGANISATIONS AVEC LA FEATURE
-- ========================================
SELECT
    id,
    name,
    features
FROM organization
WHERE JSON_CONTAINS(COALESCE(features, '[]'), '"visit_management"');

-- ========================================
-- DÉSACTIVER LA FEATURE POUR UNE ORGANISATION
-- ========================================
-- Si besoin de désactiver la feature pour une organisation spécifique :

-- UPDATE organization
-- SET features = JSON_REMOVE(
--     features,
--     JSON_UNQUOTE(JSON_SEARCH(features, 'one', 'visit_management'))
-- )
-- WHERE id = 1;
