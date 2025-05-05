<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductReview;
use App\Form\ProductReviewType;
use App\Repository\OrderRepository;
use App\Repository\ProductReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/review')]
class ProductReviewController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductReviewRepository $productReviewRepository,
        private OrderRepository $orderRepository
    ) {
    }

    #[Route('/product/{id}', name: 'app_product_review_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, int $id): Response
    {
        $product = $this->entityManager->getRepository(Product::class)->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        // Check if user has already reviewed this product
        if ($this->productReviewRepository->hasUserReviewedProduct($product, $this->getUser())) {
            $this->addFlash('error', 'You have already reviewed this product.');
            return $this->redirectToRoute('app_product_show', ['slug' => $product->getSlug()]);
        }

        // Check if user has purchased this product
        $hasPurchased = $this->orderRepository->hasUserPurchasedProduct($this->getUser(), $product);

        $review = new ProductReview();
        $review->setProduct($product);
        $review->setUser($this->getUser());

        // If user has purchased the product, auto-approve the review
        if ($hasPurchased) {
            $review->setIsApproved(true);
            $review->setApprovedAt(new \DateTimeImmutable());

            // Find the order for this product
            $order = $this->orderRepository->findOrderWithProduct($this->getUser(), $product);
            if ($order) {
                $review->setOrder($order);
            }
        }

        $form = $this->createForm(ProductReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($review);
            $this->entityManager->flush();

            if ($hasPurchased) {
                $this->addFlash('success', 'Thank you for your review! It has been published.');
            } else {
                $this->addFlash('success', 'Thank you for your review! It will be published after moderation.');
            }

            return $this->redirectToRoute('app_product_show', ['slug' => $product->getSlug()]);
        }

        return $this->render('product_review/new.html.twig', [
            'product' => $product,
            'form' => $form,
            'hasPurchased' => $hasPurchased
        ]);
    }

    #[Route('/my-reviews', name: 'app_my_reviews', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myReviews(): Response
    {
        $reviews = $this->productReviewRepository->findByUser($this->getUser());

        return $this->render('product_review/my_reviews.html.twig', [
            'reviews' => $reviews
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_review_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, int $id): Response
    {
        $review = $this->productReviewRepository->find($id);

        if (!$review) {
            throw $this->createNotFoundException('Review not found');
        }

        // Security check - only allow users to edit their own reviews
        if ($review->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot edit this review.');
        }

        $form = $this->createForm(ProductReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // If the review was already approved, keep it approved
            if ($review->isIsApproved()) {
                $review->setUpdatedAt(new \DateTimeImmutable());
            } else {
                // If not approved, it needs to go through moderation again
                $review->setIsApproved(false);
                $review->setApprovedAt(null);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Your review has been updated successfully.');
            return $this->redirectToRoute('app_my_reviews');
        }

        return $this->render('product_review/edit.html.twig', [
            'review' => $review,
            'form' => $form
        ]);
    }

    #[Route('/{id}/delete', name: 'app_product_review_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, int $id): Response
    {
        $review = $this->productReviewRepository->find($id);

        if (!$review) {
            throw $this->createNotFoundException('Review not found');
        }

        // Security check - only allow users to delete their own reviews
        if ($review->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You cannot delete this review.');
        }

        if ($this->isCsrfTokenValid('delete'.$review->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($review);
            $this->entityManager->flush();

            $this->addFlash('success', 'Your review has been deleted successfully.');
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_product_reviews');
        }

        return $this->redirectToRoute('app_my_reviews');
    }
}
