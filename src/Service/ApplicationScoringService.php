<?php

namespace App\Service;

use App\Entity\TenantApplication;
use Psr\Log\LoggerInterface;

/**
 * Service de scoring automatique des candidatures locataires
 */
class ApplicationScoringService
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * Calcule le score d'une candidature (sur 100)
     */
    public function calculateScore(TenantApplication $application): array
    {
        $scores = [];
        $totalScore = 0;

        // 1. Revenus par rapport au loyer (40 points max)
        $incomeScore = $this->scoreIncome($application);
        $scores['income'] = $incomeScore;
        $totalScore += $incomeScore['score'];

        // 2. Situation professionnelle (20 points max)
        $employmentScore = $this->scoreEmployment($application);
        $scores['employment'] = $employmentScore;
        $totalScore += $employmentScore['score'];

        // 3. Garanties (15 points max)
        $guarantorScore = $this->scoreGuarantor($application);
        $scores['guarantor'] = $guarantorScore;
        $totalScore += $guarantorScore['score'];

        // 4. Profil du foyer (10 points max)
        $householdScore = $this->scoreHousehold($application);
        $scores['household'] = $householdScore;
        $totalScore += $householdScore['score'];

        // 5. Âge et stabilité (10 points max)
        $stabilityScore = $this->scoreStability($application);
        $scores['stability'] = $stabilityScore;
        $totalScore += $stabilityScore['score'];

        // 6. Bonus/Malus additionnels (5 points max)
        $additionalScore = $this->scoreAdditional($application);
        $scores['additional'] = $additionalScore;
        $totalScore += $additionalScore['score'];

        // Score final (plafonné à 100)
        $finalScore = min(100, max(0, $totalScore));

        return [
            'total' => $finalScore,
            'grade' => $this->getGrade($finalScore),
            'recommendation' => $this->getRecommendation($finalScore),
            'details' => $scores
        ];
    }

    /**
     * Score basé sur les revenus (40 points max)
     * Ratio idéal: revenus >= 3x le loyer
     */
    private function scoreIncome(TenantApplication $application): array
    {
        $maxScore = 40;
        $property = $application->getProperty();

        if (!$property || !$property->getMonthlyRent() || !$application->getMonthlyIncome()) {
            return [
                'score' => 0,
                'max' => $maxScore,
                'label' => 'Revenus',
                'comment' => 'Informations sur les revenus manquantes'
            ];
        }

        $rentAmount = (float)$property->getMonthlyRent();
        $income = (float)$application->getMonthlyIncome();
        $ratio = $income / $rentAmount;

        // Calcul du score selon le ratio
        if ($ratio >= 4) {
            $score = $maxScore; // Excellent
            $comment = sprintf('Excellent: revenus %.1fx supérieurs au loyer', $ratio);
        } elseif ($ratio >= 3) {
            $score = $maxScore * 0.9; // Très bien
            $comment = sprintf('Très bien: revenus %.1fx supérieurs au loyer', $ratio);
        } elseif ($ratio >= 2.5) {
            $score = $maxScore * 0.7; // Bien
            $comment = sprintf('Bien: revenus %.1fx supérieurs au loyer', $ratio);
        } elseif ($ratio >= 2) {
            $score = $maxScore * 0.5; // Acceptable
            $comment = sprintf('Acceptable: revenus %.1fx supérieurs au loyer', $ratio);
        } else {
            $score = $maxScore * 0.2; // Faible
            $comment = sprintf('Faible: revenus seulement %.1fx supérieurs au loyer', $ratio);
        }

        return [
            'score' => round($score, 2),
            'max' => $maxScore,
            'label' => 'Revenus',
            'ratio' => round($ratio, 2),
            'comment' => $comment
        ];
    }

    /**
     * Score basé sur la situation professionnelle (20 points max)
     */
    private function scoreEmployment(TenantApplication $application): array
    {
        $maxScore = 20;
        $status = $application->getEmploymentStatus();
        $contractType = $application->getContractType();

        $statusScores = [
            'employed' => 1.0,
            'self_employed' => 0.8,
            'retired' => 0.9,
            'student' => 0.6,
            'unemployed' => 0.3
        ];

        $contractScores = [
            'cdi' => 1.0,
            'cdd' => 0.7,
            'interim' => 0.5,
            'freelance' => 0.6
        ];

        $statusMultiplier = $statusScores[$status] ?? 0.5;
        $contractMultiplier = $contractType ? ($contractScores[$contractType] ?? 0.5) : $statusMultiplier;

        $score = $maxScore * $statusMultiplier * $contractMultiplier;

        $comments = [
            'employed' => [
                'cdi' => 'Excellent: CDI stable',
                'cdd' => 'Bien: CDD',
                'interim' => 'Acceptable: Intérim',
                'freelance' => 'Bien: Indépendant'
            ],
            'self_employed' => 'Bien: Travailleur indépendant',
            'retired' => 'Très bien: Retraité (revenus stables)',
            'student' => 'Acceptable: Étudiant',
            'unemployed' => 'Faible: Sans emploi'
        ];

        $comment = is_array($comments[$status] ?? null) && $contractType
            ? ($comments[$status][$contractType] ?? $comments[$status]['cdi'])
            : ($comments[$status] ?? 'Situation à évaluer');

        return [
            'score' => round($score, 2),
            'max' => $maxScore,
            'label' => 'Situation professionnelle',
            'comment' => $comment
        ];
    }

    /**
     * Score basé sur les garanties (15 points max)
     */
    private function scoreGuarantor(TenantApplication $application): array
    {
        $maxScore = 15;

        if (!$application->isHasGuarantor()) {
            return [
                'score' => 0,
                'max' => $maxScore,
                'label' => 'Garanties',
                'comment' => 'Pas de garant'
            ];
        }

        $score = $maxScore * 0.6; // Base avec garant
        $comment = 'Garant présent';

        // Bonus si revenus du garant fournis
        if ($application->getGuarantorIncome()) {
            $guarantorIncome = (float)$application->getGuarantorIncome();
            $rentAmount = (float)$application->getProperty()->getMonthlyRent();

            if ($guarantorIncome >= $rentAmount * 3) {
                $score = $maxScore; // Garant avec revenus suffisants
                $comment = 'Excellent: garant avec revenus élevés';
            } else {
                $score = $maxScore * 0.8;
                $comment = 'Bien: garant avec revenus corrects';
            }
        }

        return [
            'score' => round($score, 2),
            'max' => $maxScore,
            'label' => 'Garanties',
            'comment' => $comment
        ];
    }

    /**
     * Score basé sur le profil du foyer (10 points max)
     */
    private function scoreHousehold(TenantApplication $application): array
    {
        $maxScore = 10;
        $property = $application->getProperty();
        $score = $maxScore;
        $comments = [];

        // Vérifier l'adéquation nombre d'occupants / taille logement
        $occupants = $application->getNumberOfOccupants();
        $bedrooms = $property ? $property->getBedrooms() : 1;

        if ($occupants > ($bedrooms * 2) + 1) {
            $score *= 0.6; // Trop d'occupants
            $comments[] = 'Nombre d\'occupants élevé pour la taille';
        } elseif ($occupants > $bedrooms * 2) {
            $score *= 0.8;
            $comments[] = 'Nombre d\'occupants correct';
        } else {
            $comments[] = 'Nombre d\'occupants adapté';
        }

        // Animaux
        if ($application->isHasPets()) {
            // Vérifier si la propriété accepte les animaux
            if ($property && method_exists($property, 'isPetsAllowed') && !$property->isPetsAllowed()) {
                $score *= 0.5;
                $comments[] = 'Animaux non acceptés';
            } else {
                $score *= 0.9;
                $comments[] = 'Animaux déclarés';
            }
        } else {
            $comments[] = 'Pas d\'animaux';
        }

        return [
            'score' => round($score, 2),
            'max' => $maxScore,
            'label' => 'Profil du foyer',
            'comment' => implode(', ', $comments)
        ];
    }

    /**
     * Score basé sur l'âge et la stabilité (10 points max)
     */
    private function scoreStability(TenantApplication $application): array
    {
        $maxScore = 10;
        $age = $application->getAge();
        $score = $maxScore * 0.7; // Score par défaut

        if ($age === null) {
            return [
                'score' => $score,
                'max' => $maxScore,
                'label' => 'Stabilité',
                'comment' => 'Âge non fourni'
            ];
        }

        if ($age >= 25 && $age <= 60) {
            $score = $maxScore; // Âge optimal
            $comment = 'Âge optimal pour la location';
        } elseif ($age >= 18 && $age < 25) {
            $score = $maxScore * 0.7; // Jeune
            $comment = 'Jeune locataire';
        } elseif ($age > 60 && $age <= 75) {
            $score = $maxScore * 0.9; // Senior
            $comment = 'Locataire senior stable';
        } else {
            $score = $maxScore * 0.6;
            $comment = 'Âge à considérer';
        }

        return [
            'score' => round($score, 2),
            'max' => $maxScore,
            'label' => 'Stabilité',
            'age' => $age,
            'comment' => $comment
        ];
    }

    /**
     * Scores additionnels et bonus/malus (5 points max)
     */
    private function scoreAdditional(TenantApplication $application): array
    {
        $maxScore = 5;
        $score = 0;
        $comments = [];

        // Bonus: durée de bail souhaitée longue
        $leaseDuration = $application->getDesiredLeaseDuration();
        if ($leaseDuration && $leaseDuration >= 24) {
            $score += 2;
            $comments[] = 'Durée de bail longue souhaitée (+2)';
        } elseif ($leaseDuration && $leaseDuration >= 12) {
            $score += 1;
            $comments[] = 'Durée de bail standard (+1)';
        }

        // Bonus: date d'emménagement flexible
        $moveInDate = $application->getDesiredMoveInDate();
        if ($moveInDate) {
            $daysUntilMoveIn = $moveInDate->diff(new \DateTime())->days;
            if ($daysUntilMoveIn > 30) {
                $score += 1;
                $comments[] = 'Date d\'emménagement flexible (+1)';
            }
        }

        // Bonus: informations complémentaires fournies
        if ($application->getAdditionalInfo()) {
            $score += 1;
            $comments[] = 'Informations complémentaires fournies (+1)';
        }

        // Bonus: visite effectuée
        if ($application->getVisit() && $application->getVisit()->getStatus() === 'completed') {
            $score += 1;
            $comments[] = 'Visite effectuée (+1)';
        }

        return [
            'score' => min($maxScore, round($score, 2)),
            'max' => $maxScore,
            'label' => 'Bonus additionnels',
            'comment' => implode(', ', $comments) ?: 'Aucun bonus'
        ];
    }

    /**
     * Détermine la note alphabétique (A à F)
     */
    private function getGrade(float $score): string
    {
        return match(true) {
            $score >= 90 => 'A+',
            $score >= 80 => 'A',
            $score >= 70 => 'B+',
            $score >= 60 => 'B',
            $score >= 50 => 'C',
            $score >= 40 => 'D',
            default => 'F'
        };
    }

    /**
     * Génère une recommandation basée sur le score
     */
    private function getRecommendation(float $score): string
    {
        return match(true) {
            $score >= 80 => 'Candidature excellente - Fortement recommandée',
            $score >= 70 => 'Très bonne candidature - Recommandée',
            $score >= 60 => 'Bonne candidature - À considérer favorablement',
            $score >= 50 => 'Candidature acceptable - Nécessite examen approfondi',
            $score >= 40 => 'Candidature faible - Garanties supplémentaires recommandées',
            default => 'Candidature insuffisante - Risque élevé'
        };
    }

    /**
     * Applique le scoring à une candidature et enregistre le résultat
     */
    public function scoreApplication(TenantApplication $application): TenantApplication
    {
        $scoreData = $this->calculateScore($application);

        $application->setScore((string)$scoreData['total']);
        $application->setScoreDetails($scoreData);
        $application->setUpdatedAt(new \DateTime());

        $this->logger->info('Candidature scorée', [
            'application_id' => $application->getId(),
            'score' => $scoreData['total'],
            'grade' => $scoreData['grade'],
            'applicant' => $application->getFullName()
        ]);

        return $application;
    }
}
