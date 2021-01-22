<?php

namespace App\Manager;

use App\Entity\Project;
use App\Entity\Source;
use App\Entity\Translation;

class ProjectManager
{

    private $em;

    public function __construct($entityManager)
    {
        $this->em = $entityManager;
    }

    public function save_project(Project $project, Source $source)
    {
        $this->em->persist($source);
        $this->em->persist($project);
        $this->em->flush();
    }

    public function update_project(Project $project)
    {
        $this->em->persist($project);
        $this->em->flush();
    }

    public function delete_project(Project $project)
    {
        $sources = $project->getSource();
        foreach ($sources as $source) {
            $source = $this->em->getRepository(Source::class)->find($source->getID());
            $translations = $source->getTranslations();
            foreach ($translations as $translation) {
                $translation = $this->em->getRepository(Translation::class)->find($translation->getId());
                $this->em->remove($translation);
            }
            $this->em->remove($source);
        }
        $this->em->remove($project);
        $this->em->flush();
    }
}