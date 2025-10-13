<?php

namespace App\Command;

use App\Entity\Plan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-default-plans',
    description: 'Crée les plans d\'abonnement par défaut pour le système SaaS',
)]
class CreateDefaultPlansCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création des Plans d\'Abonnement Par Défaut');

        $defaultPlans = [
            [
                'name' => 'Freemium',
                'slug' => 'freemium',
                'description' => 'Testez gratuitement pour toujours',
                'monthly_price' => '0',
                'yearly_price' => '0',
                'currency' => 'FCFA',
                'max_properties' => 2,
                'max_tenants' => 3,
                'max_users' => 1,
                'max_documents' => 10,
                'features' => [
                    'dashboard',
                    'properties_management',
                    'tenants_management',
                    'lease_management',
                    'payment_tracking',
                ],
                'sort_order' => 1,
                'is_popular' => false,
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Parfait pour débuter dans la gestion locative',
                'monthly_price' => '9900',
                'yearly_price' => '99000',
                'currency' => 'FCFA',
                'max_properties' => 5,
                'max_tenants' => 10,
                'max_users' => 2,
                'max_documents' => 50,
                'features' => [
                    'dashboard',
                    'properties_management',
                    'tenants_management',
                    'lease_management',
                    'payment_tracking',
                    'documents',
                ],
                'sort_order' => 2,
                'is_popular' => false,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Pour les gestionnaires professionnels',
                'monthly_price' => '24900',
                'yearly_price' => '249000',
                'currency' => 'FCFA',
                'max_properties' => 20,
                'max_tenants' => 50,
                'max_users' => 5,
                'max_documents' => 200,
                'features' => [
                    'dashboard',
                    'properties_management',
                    'tenants_management',
                    'lease_management',
                    'payment_tracking',
                    'documents',
                    'accounting',
                    'maintenance_requests',
                    'online_payments',
                    'advance_payments',
                    'reports',
                    'email_notifications',
                ],
                'sort_order' => 3,
                'is_popular' => true,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Solution complète pour grandes entreprises',
                'monthly_price' => '49900',
                'yearly_price' => '499000',
                'currency' => 'FCFA',
                'max_properties' => null, // Illimité
                'max_tenants' => null, // Illimité
                'max_users' => null, // Illimité
                'max_documents' => null, // Illimité
                'features' => [
                    'dashboard',
                    'properties_management',
                    'tenants_management',
                    'lease_management',
                    'payment_tracking',
                    'documents',
                    'accounting',
                    'maintenance_requests',
                    'online_payments',
                    'advance_payments',
                    'reports',
                    'email_notifications',
                    'sms_notifications',
                    'custom_branding',
                    'api_access',
                    'priority_support',
                    'multi_currency',
                ],
                'sort_order' => 4,
                'is_popular' => false,
            ],
        ];

        $created = 0;
        foreach ($defaultPlans as $planData) {
            $existingPlan = $this->entityManager->getRepository(Plan::class)
                ->findOneBy(['slug' => $planData['slug']]);

            if (!$existingPlan) {
                $plan = new Plan();
                $plan->setName($planData['name'])
                     ->setSlug($planData['slug'])
                     ->setDescription($planData['description'])
                     ->setMonthlyPrice($planData['monthly_price'])
                     ->setYearlyPrice($planData['yearly_price'])
                     ->setCurrency($planData['currency'])
                     ->setMaxProperties($planData['max_properties'])
                     ->setMaxTenants($planData['max_tenants'])
                     ->setMaxUsers($planData['max_users'])
                     ->setMaxDocuments($planData['max_documents'])
                     ->setFeatures($planData['features'])
                     ->setSortOrder($planData['sort_order'])
                     ->setIsPopular($planData['is_popular'])
                     ->setIsActive(true);

                $this->entityManager->persist($plan);
                $created++;

                $io->success("✅ Plan '{$planData['name']}' créé");
            } else {
                $io->info("ℹ️  Plan '{$planData['name']}' existe déjà");
            }
        }

        if ($created > 0) {
            $this->entityManager->flush();
        }

        $io->success(sprintf('✨ %d plan(s) d\'abonnement créé(s)', $created));

        return Command::SUCCESS;
    }
}

