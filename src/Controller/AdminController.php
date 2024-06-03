<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Ticket;
use App\Entity\Comment;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\TicketRepository;
use App\Repository\UsertRepository;
use App\Form\TicketsType;
use App\Form\CommentType;
use App\Form\UserType;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use Symfony\Component\Security\Core\Security;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(TicketRepository $ticketRepo, CommentRepository $commentRepo, ): Response
    {
        $user = $this->getUser();
        $ticket = $ticketRepo->findBy([], ['created_at' => 'ASC'], 20);
        $comment = $commentRepo->findBy([], ['created_at' => 'ASC'], 20);

        return $this->render('admin/index.html.twig', [
            'user' => $user,
            'tickets' => $ticket,
            'comments' => $comment,
        ]);
    }

    /**
     * PART: TAGS
     */

    #[Route('/admin/tags', name: 'app_admin_tags')]
    public function admin_tags(TagRepository $tagsRepo, ): Response
    {
        $user = $this->getUser();
        $tags = $tagsRepo->findAll();

        return $this->render('admin/tags.html.twig', [
            'user' => $user,
            'tags' => $tags,
        ]);
    }

    /**
     * PART: TICKETS
     */

    #[Route('/admin/tickets', name: 'app_admin_tickets')]
    public function admin_tickets(TicketRepository $ticketsRepo, ): Response
    {
        $user = $this->getUser();
        $tickets = $ticketsRepo->findAll();

        return $this->render('admin/tickets.html.twig', [
            'user' => $user,
            'tickets' => $tickets,
        ]);
    }
    
    #[Route('/admin/tickets/{id}/delete', name: 'app_admin_tickets_delete')]
    public function delete(Ticket $ticket, PersistenceManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $comments = $ticket->getComments();
        foreach ($comments as $comment) {
            $ticket->removeComment($comment);
            $entityManager->remove($comment);
        }
        $entityManager->remove($ticket);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_tickets');
    }

    /**
     * PART: COMMENTS
     */

    #[Route('/admin/comments', name: 'app_admin_comments')]
    public function admin_comments(CommentRepository $commentsRepo, ): Response
    {
        $user = $this->getUser();
        $comments = $commentsRepo->findAll();

        return $this->render('admin/comments.html.twig', [
            'user' => $user,
            'comments' => $comments,
        ]);
    }

    #[Route('/admin/comments/{id}/delete', name: 'app_admin_comment_delete')]
    public function admin_comment_delete(Comment $comment, PersistenceManagerRegistry $doctrine): RedirectResponse
    {

        $entityManager = $doctrine->getManager();
        $entityManager->remove($comment);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_comments');
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function admin_users(UserRepository $usersRepo, ): Response
    {
        $user = $this->getUser();
        $users = $usersRepo->findAll();
        dump($users);

        return $this->render('admin/users.html.twig', [
            'user' => $user,
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/{id}/modifier', name: 'app_admin_user_edit')]
    public function admin_user_edit(Request $request, User $user, PersistenceManagerRegistry $doctrine): Response
    {
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/edit_user.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/users/{id}/delete', name: 'app_admin_user_delete')]
    public function admin_user_delete(User $user, PersistenceManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_users');
    }
}
