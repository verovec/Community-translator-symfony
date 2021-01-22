<?php

namespace App\Manager;

use App\Entity\Translation;

class TranslationManager
{

    private $em;

    public function __construct($entityManager)
    {
        $this->em = $entityManager;
    }

    public function save_translation(Translation $translation)
    {
        $this->em->persist($translation);
        $this->em->flush();
    }
}