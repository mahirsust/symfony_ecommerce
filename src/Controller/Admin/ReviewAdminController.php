<?php

namespace App\Controller\Admin;

use App\Entity\ProductReview;
use App\Repository\ProductReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/reviews')]
#[IsGranted('ROLE_ADMIN')]
class ReviewAdminController extends AbstractController
{
    public function __construct(
        private ProductReviewRepository $reviewRepository,
        private EntityManagerInterface $entityManager,
        private PaginatorInterface $paginator
    ) {
    }

    #[Route('/', name: 'app_admin_product_reviews', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $status = $request->query->get('status');

        $queryBuilder = $this->reviewRepository->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC');

        if ($status === 'pending') {
            $queryBuilder->andWhere('r.isApproved = :isApproved')
                ->setParameter('isApproved', false);
        } elseif ($status === 'approved') {
            $queryBuilder->andWhere('r.isApproved = :isApproved')
                ->setParameter('isApproved', true);
        }

        $pagination = $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/review/index.html.twig', [
            'pagination' => $pagination,
            'status' => $status
        ]);
    }

    #[Route('/{id}', name: 'app_admin_product_review_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $review = $this->reviewRepository->find($id);

        if (!$review) {
            throw $this->createNotFoundException('Review not found');
        }

        return $this->render('admin/review/show.html.twig', [
            'review' => $review
        ]);
    }

    #[Route('/{id}/approve', name: 'app_admin_product_review_approve', methods: ['POST'])]
    public function approve(Request $request, int $id): Response
    {
        $review = $this->reviewRepository->find($id);

        if (!$review) {
            throw $this->createNotFoundException('Review not found');
        }

        if ($this->isCsrfTokenValid('approve'.$review->getId(), $request->request->get('_token'))) {
            $review->setIsApproved(true);
            $review->setApprovedAt(new \DateTimeImmutable());

            $this->entityManager->flush();

            $this->addFlash('success', 'Review approved successfully.');
        }

        return $this->redirectToRoute('app_admin_product_reviews', ['status' => 'pending']);
    }

    #[Route('/{id}/reject', name: 'app_admin_product_review_reject', methods: ['POST'])]
    public function reject(Request $request, int $id): Response
    {
        $review = $this->reviewRepository->find($id);

        if (!$review) {
            throw $this->createNotFoundException('Review not found');
        }

        if ($this->isCsrfTokenValid('reject'.$review->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($review);
            $this->entityManager->flush();

            $this->addFlash('success', 'Review rejected and removed successfully.');
        }

        return $this->redirectToRoute('app_admin_product_reviews', ['status' => 'pending']);
    }
}
