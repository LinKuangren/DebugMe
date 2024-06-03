<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use App\Entity\Ticket;
use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Form\CommentType;

class CommentsController extends AbstractController
{
    #[Route('/comment/{id}/delete', name: 'delete_comment')]
    public function delete(Comment $comment, PersistenceManagerRegistry $doctrine): RedirectResponse
    {
        $ticketId = $comment->getTicket()->getId();

        $entityManager = $doctrine->getManager();
        $entityManager->remove($comment);
        $entityManager->flush();

        return $this->redirectToRoute('show_tickets', ['id' => $ticketId]);
    }

    #[Route('/admin/comments/{id}/modifier', name: 'edit_comment')]
    public function edit(Request $request, Comment $comment, PersistenceManagerRegistry $doctrine): Response
    {
        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_comments');
        }

        return $this->render('admin/comments/edit_comment.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
