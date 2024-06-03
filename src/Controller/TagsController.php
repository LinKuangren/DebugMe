<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use App\Entity\Tag;
use App\Repository\TagRepository;
use App\Form\TagsType;
use Knp\Component\Pager\PaginatorInterface;

class TagsController extends AbstractController
{
    #[Route('/admin/tags', name: 'app_tags')]
    public function index(TagRepository $tagRepo, PaginatorInterface $paginator, Request $request): Response
    {
        $pagination = $paginator->paginate(
            $tagRepo->paginationTag(),
            $request->query->get('page', 1),
            10
        );
        
        return $this->render('tags/index.html.twig', [
            'controller_name' => 'TagsController',
            'tags' => $pagination,
        ]);
    }

    #[Route('/admin/tags/ajout', name: 'add_tags')]
    public function add(Request $request, PersistenceManagerRegistry $doctrine): Response
    {
        $tag = new Tag;

        $form = $this->createForm(TagsType::class, $tag);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $em = $doctrine->getManager();
            $em->persist($tag);
            $em->flush();

            return $this->redirectToRoute('app_tags');
        }
        
        return $this->render('admin/tags/add_tag.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/tags/{id}/modifier', name: 'edit_tags')]
    public function edit(Request $request, Tag $tag, PersistenceManagerRegistry $doctrine): Response
    {
        $form = $this->createForm(tagsType::class, $tag);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $entityManager->flush();

            return $this->redirectToRoute('app_tags');
        }

        return $this->render('admin/tags/edit_tag.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/tags/{id}', name: 'delete_tag')]
    public function delete(Tag $tag, PersistenceManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $entityManager->remove($tag);
        $entityManager->flush();

        return $this->redirectToRoute('app_tags');
    }
}
