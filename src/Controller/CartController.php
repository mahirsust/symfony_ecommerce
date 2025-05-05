<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
class CartController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private ProductRepository $productRepository
    ) {
    }

    #[Route('/', name: 'app_cart_index', methods: ['GET'])]
    public function index(): Response
    {
        $cart = $this->cartService->getCart();
        $total = $this->cartService->getTotalPrice();

        // Get product details for each item in cart
        $cartWithDetails = [];
        foreach ($cart as $productId => $item) {
            $product = $this->productRepository->find($productId);
            if ($product) {
                $cartWithDetails[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'total' => bcmul($product->getActualPrice(), (string)$item['quantity'], 2)
                ];
            }
        }

        return $this->render('cart/index.html.twig', [
            'items' => $cartWithDetails,
            'total' => $total,
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(int $id, Request $request): Response
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $quantity = (int)$request->request->get('quantity', 1);

        if ($quantity <= 0) {
            $quantity = 1;
        }

        // Check if product is active and in stock
        if (!$product->getIsActive() || $product->getStock() < $quantity) {
            $this->addFlash('error', 'Product is not available or not enough in stock.');
            return $this->redirectToRoute('app_product_show', ['slug' => $product->getSlug()]);
        }

        $this->cartService->addToCart(
            $product->getId(),
            $quantity,
            $product->getName(),
            $product->getSku(),
            $product->getActualPrice()
        );

        $this->addFlash('success', 'Product added to cart.');

        // Redirect back to the referring page or to the cart
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(int $id): Response
    {
        $this->cartService->removeFromCart($id);

        $this->addFlash('success', 'Item removed from cart.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/update/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(int $id, Request $request): Response
    {
        $quantity = (int)$request->request->get('quantity', 1);

        if ($quantity <= 0) {
            return $this->redirectToRoute('app_cart_remove', ['id' => $id]);
        }

        // Check if product is in stock
        $product = $this->productRepository->find($id);
        if ($product && $product->getStock() < $quantity) {
            $this->addFlash('error', 'Not enough items in stock.');
            return $this->redirectToRoute('app_cart_index');
        }

        $this->cartService->updateQuantity($id, $quantity);

        $this->addFlash('success', 'Cart updated.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(): Response
    {
        $this->cartService->clearCart();

        $this->addFlash('success', 'Cart cleared.');

        return $this->redirectToRoute('app_cart_index');
    }
}
