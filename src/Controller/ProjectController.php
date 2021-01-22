<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ProjectType;
use App\Form\UploadProjectType;
use App\Form\TranslationType;
use App\Entity\Project;
use App\Entity\Source;
use App\Entity\Translation;
use App\Entity\User;
use App\Manager\ProjectManager;
// use App\Repository\ProjectRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ProjectController extends AbstractController
{

    function save_project($data, $project)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $manager = new ProjectManager($entityManager);

        $project->setName('untitled');
        $project->setLang('unknow');
        $project->setUser($this->getUser());
        $project->setState(1);

        foreach ($data as $value){
            $tmp = explode(";", $value['key;traduction']);
            $source = new Source();
            $source->setContent(utf8_encode($tmp[1]));
            $source->setKey(utf8_encode($tmp[0]));
            $project->addSource($source);
            $manager->save_project($project, $source);
        }
    }
    /**
     * @Route("/project/add", name="add_project")
     */
    public function post_project(Request $request)
    {
        if ($this->getUser()) {
            $project = new Project();
            $form = $this->createForm(UploadProjectType::class, $project);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $file = $form->get('file')->getData();
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $filename.'-'.uniqid().'.'.$file->guessExtension();

                try 
                {
                    $file->move(
                        $this->getParameter('project_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw $e;
                }

                $project->setFile($newFilename);
                $project->setPath($this->getParameter('project_directory'));
                $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
                $data = $serializer->decode(file_get_contents($this->getParameter('project_directory')."/".$newFilename), 'csv');
                $data = $this->save_project($data, $project);
                
                return $this->redirectToRoute('get_profile', array('id_user' => $this->getUser()->getId()));
            }
            else {
                return $this->render('project/index.html.twig', [
                    'form' => $form->createView(),
                    'controller_name' => 'controller_name']
                );
            }            
        }
        else
        {
            return $this->redirectToRoute('/project/all');
        }
    }

     /**
     * @Route("/project/all", name="all_projects")
     */
    public function get_all_project()
    {
        $projects = $this->getDoctrine()
                         ->getRepository(Project::class)
                         ->findAll();

        if (!$projects) {
            throw $this->createNotFoundException(
                'No project can be find. '
            );
        }
        return $this->render('project/all_project.html.twig', [
            'controller_name' => 'ProjectController',
            'projects' => $projects,
        ]);
    }

     /**
     * @Route("/project/{id}", name="get_project")
     */
    public function get_project(Request $request, $id)
    {
        $project = $this->getDoctrine()
                         ->getRepository(Project::class)
                         ->findOneById($id);

        if (!$project) {
            throw $this->createNotFoundException(
                'No project found for id '.$id
            );
        }

        if ($project->getUser() == $this->getUser()){
            return $this->render('project/details_project.html.twig', [
                'controller_name' => 'ProjectController',
                'project' => $project,
                'form' => TRUE,
                'form_spec' => FALSE,
                'edit_button' => TRUE,
                'delete_button' => TRUE,
            ]);
        }

        if ($this->getUser()) {
            return $this->render('project/details_project.html.twig', [
                'controller_name' => 'ProjectController',
                'project' => $project,
                'form' => TRUE,
                'form_spec' => FALSE,
                'edit_button' => FALSE,
                'delete_button' => FALSE,
            ]);
        }

        return $this->render('project/details_project.html.twig', [
            'controller_name' => 'ProjectController',
            'project' => $project,
            'form' => FALSE,
            'form_spec' => FALSE,
            'edit_button' => FALSE,
            'delete_button' => FALSE,
        ]);
    }

    /**
     * @Route("/project/user/{id}", name="get_all_user_project")
     */
    public function get_user_project($id)
    {
        $projects = $this->getDoctrine()
                        ->getRepository(Project::class)
                        ->findByUser($id);

        if (!$projects) {
            throw $this->createNotFoundException(
                'No project can be find. '
            );
        }
        return $this->render('project/all_project.html.twig', [
            'controller_name' => 'ProjectController',
            'projects' => $projects,
        ]);
    }

    /**
     * @Route("/project/edit/{project_id}", name="edit_user_project")
     */
    public function edit_project($project_id, Request $request)
    {
        $project = $this->getDoctrine()
                        ->getRepository(Project::class)
                        ->findOneById($project_id);

        if ($project->getUser() == $this->getUser()) {
            $form = $this->createFormBuilder($project)
                ->add('name', TextType::class,['label' => 'name', 'action' => $project->getName()])
                ->add('lang', TextType::class,['label' => 'lang', 'action' => $project->getLang()])
                ->add('save', SubmitType::class, ['label' => 'save'])
                ->getForm();

            if ($request->isMethod('post')) {
                $form->handleRequest($request);
                $data = $form->getData();
                $project->setName($data->getName());
                $project->setLang($data->getLang());

                $entityManager = $this->getDoctrine()->getManager();
                $manager = new ProjectManager($entityManager);
                $manager->update_project($project);

                return $this->redirectToRoute('get_project', array('id' => $project->getId()));
            }

            return $this->render('project/details_project.html.twig', [
                'controller_name' => 'ProfileController',
                'form_spec' => $form->createView(),
                'project' => $project,
                'form' => TRUE,
                'edit_button' => FALSE,
                'delete_button' => TRUE,
            ]);
        }

        throw $this->createNotFoundException('Permission denied');
    }

    /**
     * @Route("/project/delete/{id}", name="delete_project")
     */
    public function delete_project($id)
    {
        $entityManager = $this->getDoctrine()
                              ->getManager();

        $project = $entityManager->getRepository(Project::class)
                                 ->find($id);
                                 
        $user = $entityManager->getRepository(User::class)
                              ->find($project->getUser());

        if ($this->getUser() == $user) {
            $manager = new ProjectManager($entityManager);
            $manager->delete_project($project);

            return $this->redirectToRoute('get_profile', array('id_user' => $this->getUser()->getId()));
        }
        throw $this->createNotFoundException('Permission denied');
    }
}
