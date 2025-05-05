<?php

namespace App\Controller;

use App\Entity\Order;
use App\Service\OrderService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/payment')]
class PaymentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StripeService $stripeService,
        private OrderService $orderService
    ) {
    }

    #[Route('/checkout/{id}', name: 'app_payment_checkout')]
    #[IsGranted('ROLE_USER')]
    public function checkout(Order $order): Response
    {
        // Check if the order belongs to the current user
        if ($order->getUserRef() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot access this order.');
        }
        
        // Check if the order is already paid
        if ($order->getPaymentStatus() === 'paid') {
            $this->addFlash('info', 'This order has already been paid.');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }
        
        try {
            // Create Stripe checkout session
            $session = $this->stripeService->createCheckoutSession($order);
            
            // Update order with Stripe session ID
            $order->setStripeSessionId($session->id);
            $this->entityManager->flush();
            
            // Redirect to Stripe Checkout
            return $this->redirect($session->url);
        } catch (ApiErrorException $e) {
            $this->addFlash('error', 'An error occurred while processing your payment: ' . $e->getMessage());
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }
    }

    #[Route('/success/{orderId}', name: 'app_payment_success')]
    #[IsGranted('ROLE_USER')]
    public function success(Request $request, string $orderId): Response
    {
        $sessionId = $request->query->get('sessionId');
        $order = $this->entityManager->getRepository(Order::class)->find($orderId);
        
        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }
        
        // Check if the order belongs to the current user
        if ($order->getUserRef() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot access this order.');
        }
        
        // Verify that the session ID matches
        if ($order->getStripeSessionId() !== $sessionId) {
            $this->addFlash('error', 'Invalid payment session.');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }
        
        // Update order status
        $this->orderService->updatePaymentStatus($order, 'paid');
        $this->orderService->updateOrderStatus($order, 'processing');
        
        $this->addFlash('success', 'Your payment was successful! Your order is now being processed.');
        return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
    }

    #[Route('/cancel/{orderId}', name: 'app_payment_cancel')]
    #[IsGranted('ROLE_USER')]
    public function cancel(string $orderId): Response
    {
        $order = $this->entityManager->getRepository(Order::class)->find($orderId);
        
        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }
        
        // Check if the order belongs to the current user
        if ($order->getUserRef() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot access this order.');
        }
        
        $this->addFlash('info', 'Your payment was cancelled. You can try again later.');
        return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
    }

    #[Route('/webhook', name: 'app_payment_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');
        
        if (!$sigHeader) {
            return new JsonResponse(['error' => 'Stripe signature missing'], Response::HTTP_BAD_REQUEST);
        }
        
        // Verify webhook signature
        if (!$this->stripeService->verifyWebhookSignature($payload, $sigHeader)) {
            return new JsonResponse(['error' => 'Invalid signature'], Response::HTTP_BAD_REQUEST);
        }
        
        $event = json_decode($payload, true);
        
        // Handle the event
        switch ($event['type']) {
            case 'checkout.session.completed':
                $session = $event['data']['object'];
                $orderId = $session['metadata']['order_id'] ?? null;
                
                if ($orderId) {
                    $order = $this->entityManager->getRepository(Order::class)->find($orderId);
                    
                    if ($order && $order->getPaymentStatus() !== 'paid') {
                        // Update order status
                        $this->orderService->updatePaymentStatus($order, 'paid');
                        $this->orderService->updateOrderStatus($order, 'processing');
                    }
                }
                break;
                
            case 'charge.refunded':
                $charge = $event['data']['object'];
                $orderId = null;
                
                // Try to find the order from metadata
                if (isset($charge['payment_intent'])) {
                    // You might need to fetch the payment intent to get the metadata
                    // This is a simplified example
                    $paymentIntent = \Stripe\PaymentIntent::retrieve($charge['payment_intent']);
                    $orderId = $paymentIntent->metadata->order_id ?? null;
                }
                
                if ($orderId) {
                    $order = $this->entityManager->getRepository(Order::class)->find($orderId);
                    
                    if ($order) {
                        // Update order status
                        $this->orderService->updatePaymentStatus($order, 'refunded');
                    }
                }
                break;
        }
        
        return new JsonResponse(['status' => 'success']);
    }
}
