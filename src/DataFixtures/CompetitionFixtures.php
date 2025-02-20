<?php

namespace App\DataFixtures;

use App\Entity\Competition;
use App\Repository\SaisonRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;


class CompetitionFixtures extends Fixture 
{
    private SaisonRepository $saisonRepository;

    public function __construct(SaisonRepository $saisonRepository)
    {
        $this->saisonRepository = $saisonRepository;
    }
    

    public function load(ObjectManager $manager): void
    {

        // Récupérer une saison existante en base
        $saison = $this->saisonRepository->findOneBy([]);

        
        

        // Création de quelques compétitions (missions)
        $competition1 = new Competition();
        $competition1->setNomComp('Organiser un événement');
        $competition1->setDescComp('Organiser au moins un événement durant cette saison.');
        $competition1->setStartDate(new \DateTime('2024-03-01'));
        $competition1->setEndDate(new \DateTime('2024-06-30'));
        $competition1->setPoints(50);
        $competition1->setSaison($saison); // Associe une saison aléatoire

        $competition2 = new Competition();
        $competition2->setNomComp('Recruter des membres');
        $competition2->setDescComp('Obtenir 5 nouveaux membres.');
        $competition2->setStartDate(new \DateTime('2024-03-01'));
        $competition2->setEndDate(new \DateTime('2024-06-30'));
        $competition2->setPoints(30);
        $competition2->setSaison($saison); // Associe une saison aléatoire

        // Persister en base
        $manager->persist($competition1);
        $manager->persist($competition2);

        $manager->flush();

        echo "✅ Compétitions insérées avec succès !\n";
    }
}

