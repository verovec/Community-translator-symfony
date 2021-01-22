<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Translation;
use App\Entity\User;
use App\Entity\Source;
use App\Entity\Project;
use App\Manager\TranslationManager;

class TranslationController extends AbstractController
{
    /**
     * @Route("/translation/add/source_id={source_id}", name="translation")
     */
    public function index($source_id, Request $request)
    {   
        $content = $request->request->get('content');
        $lang = $request->request->get('lang');

        $source = $this->getDoctrine()
            ->getRepository(Source::class)
            ->findOneById($source_id);

        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->findOneById($this->getUser()->getId());

        $project = $this->getDoctrine()
            ->getRepository(Project::class)
            ->find($source->getProject());

        $translation = new Translation();
        $translation->setSource($source);
        $translation->setUser($user);
        $translation->setContent($content);
        $translation->setLang($lang);

        $entityManager = $this->getDoctrine()->getManager();
        $manager = new TranslationManager($entityManager);
        $manager->save_translation($translation);

        return $this->redirectToRoute('get_project', array('id' => $project->getId()));
    }
}
