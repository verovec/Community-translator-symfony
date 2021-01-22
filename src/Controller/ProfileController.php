<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Manager\UserManager;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ProfileController extends AbstractController
{
    /**
     * @Route("/profile/{id_user}", name="get_profile")
     */
    public function get_profile($id_user)
    {
        $profile = $this->getDoctrine()
                        ->getRepository(User::class)
                        ->findOneById($id_user);

        if (!$profile) {
            throw $this->createNotFoundException(
                'No user found for id '.$id_user
            );
        }

        $edit_button = ($profile == $this->getUser()) ? TRUE : FALSE;
        $description = ($profile->getDescription()) ? $profile->getDescription() : 'FALSE';
        $project = $profile->getProjects();

        return $this->render('profile/index.html.twig', [
            'controller_name' => 'ProfileController',
            'edit' => FALSE,
            'edit_button' => $edit_button,
            'form' => FALSE,
            'profile' => $profile,
            'description' => $description,
            'projects' => $project,
        ]);
    }

    /**
     * @Route("/profile/edit/{id_user}", name="edit_profile")
     */
    public function edit_profile($id_user, Request $request)
    {
        $profile = $this->getDoctrine()
                        ->getRepository(User::class)
                        ->findOneById($id_user);

        if ($profile == $this->getUser()) {
            $form = $this->createFormBuilder($profile)
                ->add('description', TextType::class,['label' => 'description', 'action' => $profile->getDescription()])
                ->add('email', EmailType::class,['label' => 'email', 'action' => $profile->getEmail()])
                ->add('save', SubmitType::class, ['label' => 'save'])
                ->getForm();

            if ($request->isMethod('post')) {
                $form->handleRequest($request);
                if ($profile->getId() == $this->getUser()->getId()) {
                    $data = $form->getData();
                    $profile->setDescription($data->getDescription());
                    $profile->setEmail($data->getEmail());
                    
                    $entityManager = $this->getDoctrine()->getManager();
                    $manager = new UserManager($entityManager);
                    $manager->save_user($profile);

                    return $this->redirectToRoute('get_profile', array('id_user' => $profile->getId()));
                }
            }

            return $this->render('profile/index.html.twig', [
                'controller_name' => 'ProfileController',
                'edit' => TRUE,
                'edit_button' => FALSE,
                'form' => $form->createView(),
                'profile' => $profile,
                'projects' => FALSE,
            ]);
        }

        throw $this->createNotFoundException('Permission denied');
    }
}
