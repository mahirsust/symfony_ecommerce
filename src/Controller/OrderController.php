<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Order;
use App\Form\AddressType;
use App\Repository\OrderRepository;
use App\Service\CartService;
use App\Service\OrderService;
use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/order')]
#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    public function __construct(
        private OrderService $orderService,
        private CartService $cartService,
        private OrderRepository $orderRepository,
        private StripeService $stripeService
    ) {
    }

    #[Route('/', name: 'app_order_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $orders = $this->orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/checkout', name: 'app_order_checkout', methods: ['GET', 'POST'])]
    public function checkout(Request $request): Response
    {
        // Check if cart is empty
        if ($this->cartService->getItemCount() === 0) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart_index');
        }

        $user = $this->getUser();

        // Get user's addresses or create a new one
        $address = new Address();
        $address->setUser($user);

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get payment method
            $paymentMethod = $request->request->get('paymentMethod', 'cash_on_delivery');

            // Create order
            $order = $this->orderService->createOrderFromCart($user, $address, $paymentMethod);

            // Clear cart
            $this->cartService->clearCart();

            $this->addFlash('success', 'Your order has been placed successfully.');

            // If payment method is Stripe, redirect to checkout
            if ($paymentMethod === 'stripe') {
                return $this->redirectToRoute('app_payment_checkout', ['id' => $order->getId()]);
            }

            return $this->redirectToRoute('app_order_confirmation', ['id' => $order->getId()]);
        }

        return $this->render('order/checkout.html.twig', [
            'form' => $form,
            'cart' => $this->cartService->getCart(),
            'total' => $this->cartService->getTotalPrice(),
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        // Security check - only allow users to view their own orders
        if ($order->getUserRef() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot access this order.');
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/confirmation/{id}', name: 'app_order_confirmation', methods: ['GET'])]
    public function confirmation(int $id): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        // Security check - only allow users to view their own orders
        if ($order->getUserRef() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot access this order.');
        }

        return $this->render('order/confirmation.html.twig', [
            'order' => $order,
        ]);
    }
}
