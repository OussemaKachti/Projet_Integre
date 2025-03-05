<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

use App\Entity\Saison;
class SaisonFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $saison1 = new Saison();
        $saison1->setNomSaison('Spring Season');
        $saison1->setDescSaison('A fresh new start for competitions.');
        $saison1->setDateFin(new \DateTime('2025-02-18'));
        $manager->persist($saison1);

        $saison2 = new Saison();
        $saison2->setNomSaison('Summer Season');
        $saison2->setDescSaison('Intense and competitive season.');
        $saison2->setDateFin(new \DateTime('2025-02-18'));
        $manager->persist($saison2);

        $manager->flush();
    }
}
