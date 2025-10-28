<?php

namespace App\Controller\Admin;

use App\Service\ImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/import')]
#[IsGranted('ROLE_ADMIN')]
class ImportController extends AbstractController
{
    private ImportService $importService;
    private EntityManagerInterface $entityManager;

    public function __construct(ImportService $importService, EntityManagerInterface $entityManager)
    {
        $this->importService = $importService;
        $this->entityManager = $entityManager;
    }

    /**
     * Page d'upload du fichier CSV
     */
    #[Route('', name: 'admin_import_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/import/index.html.twig');
    }

    /**
     * Traitement du fichier CSV uploadé
     */
    #[Route('/process', name: 'admin_import_process', methods: ['POST'])]
    public function process(Request $request): Response
    {
        $uploadedFile = $request->files->get('csv_file');

        if (!$uploadedFile) {
            $this->addFlash('error', 'Aucun fichier n\'a été uploadé');
            return $this->redirectToRoute('admin_import_index');
        }

        // Vérifier l'extension
        $extension = $uploadedFile->getClientOriginalExtension();
        if (!in_array(strtolower($extension), ['csv', 'txt'])) {
            $this->addFlash('error', 'Le fichier doit être au format CSV');
            return $this->redirectToRoute('admin_import_index');
        }

        try {
            // Déplacer le fichier temporaire
            $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/var/uploads';
            if (!is_dir($uploadsDirectory)) {
                mkdir($uploadsDirectory, 0777, true);
            }

            $fileName = 'import_' . uniqid() . '.' . $extension;
            $uploadedFile->move($uploadsDirectory, $fileName);
            $filePath = $uploadsDirectory . '/' . $fileName;

            // Déterminer le délimiteur
            $delimiter = $request->request->get('delimiter', ',');
            if ($delimiter === 'semicolon') {
                $delimiter = ';';
            } elseif ($delimiter === 'tab') {
                $delimiter = "\t";
            } else {
                $delimiter = ',';
            }

            // Récupérer l'organisation de l'utilisateur
            $user = $this->getUser();
            $organization = $user->getOrganization();

            if (!$organization) {
                $this->addFlash('error', 'Vous devez être associé à une organisation pour importer des données');
                return $this->redirectToRoute('admin_import_index');
            }

            // Lancer l'import
            $result = $this->importService->importFromCSV($filePath, $organization, $delimiter);

            // Supprimer le fichier temporaire
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Afficher les résultats
            if ($result['success']) {
                $this->addFlash('success', 'Import réussi ! Consultez le rapport ci-dessous pour les détails.');
            } else {
                $this->addFlash('error', 'L\'import a rencontré des erreurs. Consultez le rapport ci-dessous.');
            }

            return $this->render('admin/import/result.html.twig', [
                'result' => $result,
            ]);

        } catch (FileException $e) {
            $this->addFlash('error', 'Erreur lors de l\'upload du fichier: ' . $e->getMessage());
            return $this->redirectToRoute('admin_import_index');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'import: ' . $e->getMessage());
            return $this->redirectToRoute('admin_import_index');
        }
    }

    /**
     * Télécharger le template CSV
     */
    #[Route('/template', name: 'admin_import_template', methods: ['GET'])]
    public function downloadTemplate(): Response
    {
        $csvContent = $this->generateTemplateCSV();

        $response = new Response($csvContent);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'mylocca_import_template.csv'
        );

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * Télécharger un exemple avec données
     */
    #[Route('/example', name: 'admin_import_example', methods: ['GET'])]
    public function downloadExample(): Response
    {
        $csvContent = $this->generateExampleCSV();

        $response = new Response($csvContent);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'mylocca_import_exemple.csv'
        );

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * Génère le contenu du template CSV (vide avec en-têtes)
     */
    private function generateTemplateCSV(): string
    {
        // BOM UTF-8 pour Excel
        $csv = "\xEF\xBB\xBF";

        // En-têtes
        $headers = [
            // Bien
            'adresse_bien',
            'ville_bien',
            'code_postal_bien',
            'type_bien',
            'nb_pieces',
            'surface',
            'loyer_mensuel',
            'charges',
            'statut_bien',

            // Locataire
            'prenom_locataire',
            'nom_locataire',
            'email_locataire',
            'telephone_locataire',
            'adresse_locataire',
            'ville_locataire',
            'code_postal_locataire',

            // Bail
            'date_debut_bail',
            'date_fin_bail',
            'depot_garantie',
            'statut_bail',

            // Paiements
            'date_premier_paiement',
            'nb_echeances',
        ];

        $csv .= implode(',', $headers) . "\n";

        return $csv;
    }

    /**
     * Génère un exemple CSV avec données
     */
    private function generateExampleCSV(): string
    {
        // BOM UTF-8 pour Excel
        $csv = "\xEF\xBB\xBF";

        // En-têtes
        $headers = [
            'adresse_bien', 'ville_bien', 'code_postal_bien', 'type_bien', 'nb_pieces', 'surface',
            'loyer_mensuel', 'charges', 'statut_bien',
            'prenom_locataire', 'nom_locataire', 'email_locataire', 'telephone_locataire',
            'adresse_locataire', 'ville_locataire', 'code_postal_locataire',
            'date_debut_bail', 'date_fin_bail', 'depot_garantie', 'statut_bail',
            'date_premier_paiement', 'nb_echeances'
        ];

        $csv .= implode(',', $headers) . "\n";

        // Exemples de données
        $examples = [
            [
                // Bien 1
                '15 Rue de la Paix', 'Paris', '75002', 'Appartement', '3', '65.5',
                '1200', '100', 'Loué',
                // Locataire 1
                'Jean', 'Dupont', 'jean.dupont@example.com', '+33612345678',
                '15 Rue de la Paix', 'Paris', '75002',
                // Bail 1
                '2024-01-01', '2025-12-31', '2400', 'Actif',
                // Paiements
                '2024-01-01', '12'
            ],
            [
                // Bien 2
                '28 Avenue des Champs', 'Lyon', '69001', 'Studio', '1', '25',
                '600', '50', 'Loué',
                // Locataire 2
                'Marie', 'Martin', 'marie.martin@example.com', '+33623456789',
                '28 Avenue des Champs', 'Lyon', '69001',
                // Bail 2
                '2024-03-01', '', '1200', 'Actif',
                // Paiements
                '2024-03-01', '12'
            ],
            [
                // Bien 3
                '42 Boulevard Victor Hugo', 'Marseille', '13001', 'Appartement', '4', '85',
                '1500', '120', 'Loué',
                // Locataire 3
                'Pierre', 'Durand', 'pierre.durand@example.com', '+33634567890',
                '42 Boulevard Victor Hugo', 'Marseille', '13001',
                // Bail 3
                '2023-09-01', '2025-08-31', '3000', 'Actif',
                // Paiements
                '2023-09-01', '24'
            ],
        ];

        foreach ($examples as $row) {
            // Échapper les valeurs qui contiennent des virgules
            $escapedRow = array_map(function($value) {
                if (strpos($value, ',') !== false || strpos($value, '"') !== false) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }
                return $value;
            }, $row);

            $csv .= implode(',', $escapedRow) . "\n";
        }

        return $csv;
    }
}
