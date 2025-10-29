<?php

namespace App\DataFixtures;

use App\Entity\Lease;
use App\Entity\Organization;
use App\Entity\Owner;
use App\Entity\Payment;
use App\Entity\Property;
use App\Entity\Tenant;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_CI'); // Utilisation de la locale Ivoirienne
        $slugger = new AsciiSlugger();

        $organizations = [];
        for ($i = 0; $i < 5; $i++) {
            $companyName = $faker->company;
            $slugValue = strtolower($slugger->slug($companyName));

            $organization = new Organization();
            $organization->setName($companyName);
            $organization->setAddress($faker->address);
            $organization->setPhone($faker->e164PhoneNumber);
            $organization->setSlug($slugValue);
            $organization->setSubdomain($slugValue);
            $manager->persist($organization);
            $organizations[] = $organization;

            // Création d'un manager pour chaque organisation
            $user = new User();
            $user->setFirstName($faker->firstName);
            $user->setLastName($faker->lastName);
            $user->setEmail('manager' . $i . '@yopmail.com');
            $user->setRoles(['ROLE_MANAGER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setOrganization($organization);
            $manager->persist($user);
        }

        $owners = [];
        foreach ($organizations as $organization) {
            for ($i = 0; $i < 2; $i++) {
                $owner = new Owner();
                $owner->setFirstName($faker->firstName);
                $owner->setLastName($faker->lastName);
                $owner->setEmail($faker->unique()->safeEmail);
                $owner->setPhone($faker->e164PhoneNumber);
                $owner->setAddress($faker->address);
                $owner->setOrganization($organization);
                $manager->persist($owner);
                $owners[] = $owner;
            }
        }

        $properties = [];
        foreach ($owners as $owner) {
            // Récupérer le manager de l'organisation du propriétaire
            $managerUser = null;
            foreach($owner->getOrganization()->getUsers() as $user) {
                if(in_array('ROLE_MANAGER', $user->getRoles())) {
                    $managerUser = $user;
                    break;
                }
            }

            for ($i = 0; $i < 3; $i++) {
                $property = new Property();
                $property->setAddress($faker->streetAddress);
                $property->setCity($faker->city);
                $property->setPostalCode($faker->postcode);
                $property->setPropertyType('Appartement');
                $property->setSurface($faker->numberBetween(20, 200));
                $property->setRooms($faker->numberBetween(1, 10));
                $property->setMonthlyRent($faker->numberBetween(150000, 1000000));
                $property->setCharges($faker->numberBetween(25000, 100000));
                $property->setStatus($faker->randomElement(['Libre', 'Occupé']));
                $property->setOwner($owner);
                $property->setOrganization($owner->getOrganization());

                // Associer le manager au bien
                if ($managerUser) {
                    $property->addManager($managerUser);
                }

                $manager->persist($property);
                $properties[] = $property;
            }
        }

        $tenants = [];
        foreach ($organizations as $organization) {
            for ($i = 0; $i < 10; $i++) {
                $tenant = new Tenant();
                $tenant->setFirstName($faker->firstName);
                $tenant->setLastName($faker->lastName);
                $tenant->setEmail($faker->unique()->safeEmail);
                $tenant->setPhone($faker->e164PhoneNumber);
                $tenant->setOrganization($organization);
                $manager->persist($tenant);
                $tenants[] = $tenant;
            }
        }

        foreach ($properties as $property) {
            if ($property->getStatus() === 'Occupé') {
                $lease = new Lease();
                $lease->setProperty($property);
                $lease->setTenant($faker->randomElement($tenants));
                $lease->setStartDate($faker->dateTimeBetween('-2 years', '-1 month'));
                $lease->setEndDate($faker->dateTimeBetween('+1 month', '+2 years'));
                $lease->setMonthlyRent((string)$property->getMonthlyRent());
                $lease->setCharges((string)$property->getCharges());
                $lease->setOrganization($property->getOrganization());
                $manager->persist($lease);

                // Créer quelques paiements pour les mois passés
                $paymentDate = clone $lease->getStartDate();
                for ($j = 0; $j < 5; $j++) {
                    $dueDate = (clone $paymentDate)->modify("+$j month");

                    // Ne pas créer de paiement dans le futur
                    if ($dueDate > new \DateTime()) {
                        break;
                    }

                    $payment = new Payment();
                    $payment->setLease($lease);
                    $payment->setAmount((string)$lease->getMonthlyRent());
                    $payment->setDueDate($dueDate);
                    $payment->setPaidDate(clone $dueDate);
                    $payment->setPaymentMethod('CinetPay');
                    $payment->setStatus('Payé');
                    $payment->setOrganization($lease->getOrganization());
                    $manager->persist($payment);
                }
            }
        }

        $manager->flush();
    }
}
