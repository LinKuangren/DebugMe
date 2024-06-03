<?php

namespace App\Controller;

use App\Form\SearchType;
use App\Model\SearchData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use App\Entity\Ticket;
use App\Repository\TicketRepository;
use App\Form\TicketsType;
use Knp\Component\Pager\PaginatorInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(TicketRepository $ticketRepo, PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->getUser();

        $pagi_all = $paginator->paginate(
            $ticketRepo->paginationTicket(),
            $request->query->get('page', 1),
            3
        );

        $pagi_user = $user ? $paginator->paginate(
            $ticketRepo->findTicketsByUser($user),
            $request->query->get('page', 1),
            3
        ) : null;

        $searchData = new SearchData();
        $form = $this->createForm(SearchType::class, $searchData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            
            $pagi_all_searches = $paginator->paginate(
                $ticketRepo->findBySearch($searchData),
                $request->query->get('page', 1),
                3
            );

            return $this->render('home/index.html.twig', [
                'form' => $form,
                'tickets' => $pagi_all_searches,
                'user_ticket' => $pagi_user,
            ]);
        }

        return $this->render('home/index.html.twig', [
            'form' => $form->createView(),
            'tickets' => $pagi_all,
            'user_ticket' => $pagi_user,
        ]);
    }
}
