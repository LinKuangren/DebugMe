<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use App\Entity\Ticket;
use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Repository\TicketRepository;
use App\Form\TicketsType;
use App\Form\CommentType;
use Knp\Component\Pager\PaginatorInterface;

class TicketsController extends AbstractController
{
    #[Route('/tickets', name: 'app_tickets')]
    public function index(TicketRepository $ticketRepo, PaginatorInterface $paginator, Request $request): Response
    {
        $pagination = $paginator->paginate(
            $ticketRepo->paginationTicket(),
            $request->query->get('page', 1),
            10
        );

        return $this->render('tickets/index.html.twig', [
            'tickets' => $pagination,
        ]);
    }

    #[Route('/tickets/ajout', name: 'add_tickets')]
    public function add(Request $request, PersistenceManagerRegistry $doctrine, Security $security): Response
    {
        $ticket = new Ticket;

        $form = $this->createForm(TicketsType::class, $ticket);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            // Récupérer l'utilisateur connecté
            $user = $security->getUser();
            $user->addTicket($ticket);

            // Associer l'utilisateur au ticket
            $ticket->setAuthor($user);

            $em = $doctrine->getManager();
            $em->persist($ticket);
            $em->flush();

            return $this->redirectToRoute('app_home');
        }
        
        return $this->render('tickets/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/tickets/{id}/modifier', name: 'edit_tickets')]
    public function edit(Request $request, Ticket $ticket, PersistenceManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();
        
        if (!($this->isGranted('ROLE_ADMIN') || $user === $ticket->getCreator())) {
            throw new AccessDeniedException('Vous n\'avez pas la permission de modifier ce ticket.');
        }
        
        $form = $this->createForm(ticketsType::class, $ticket);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $entityManager->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('tickets/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /** ADMIN PART : EDIT */
    #[Route('/admin/tickets/{id}/modifier', name: 'app_admin_tickets_edit')]
    public function admin_edit(Request $request, Ticket $ticket, PersistenceManagerRegistry $doctrine): Response
    {
        $form = $this->createForm(ticketsType::class, $ticket);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_tickets');
        }

        return $this->render('admin/tickets/edit_ticket.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/tickets/{id}/detail', name: 'show_tickets')]
    public function show(TicketRepository $ticketRepo, int $id, Request $request, PersistenceManagerRegistry $doctrine): Response
    {
        $ticket = $ticketRepo->findOneBy(['id' => $id]);

        if (!$ticket) {
            throw $this->createNotFoundException('Le ticket demandé n\'a pas été trouvé.');
        }

        $comments = $ticket->getComments();

        $newComment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $newComment);

        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            // Définir le ticket et l'utilisateur associés pour le nouveau commentaire.
            $newComment->setTicket($ticket);
            $newComment->setAuthor($this->getUser());

            $this->getUser()->addComment($newComment);

            $em = $doctrine->getManager();
            $em->persist($newComment);
            $em->flush();

            return $this->redirectToRoute('show_tickets', ['id' => $id,]);
        }

        return $this->render('tickets/show.html.twig', ['ticket' => $ticket, 'comments' => $comments, 'commentForm' => $commentForm->createView(),]);
    }

    #[Route('/tickets/{id}', name: 'delete_tickets')]
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

        return $this->redirectToRoute('app_tickets');
    }
}
