<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class StripeService
{
    private string $stripeSecretKey;
    private string $stripePublicKey;
    private string $webhookSecret;

    public function __construct(
        private ParameterBagInterface $params,
        private RouterInterface $router
    ) {
        $this->stripeSecretKey = $this->params->get('stripe_secret_key');
        $this->stripePublicKey = $this->params->get('stripe_public_key');
        $this->webhookSecret = $this->params->get('stripe_webhook_secret');
        
        // Initialize Stripe with the secret key
        Stripe::setApiKey($this->stripeSecretKey);
    }

    /**
     * Get Stripe public key
     *
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->stripePublicKey;
    }

    /**
     * Create a Stripe Checkout Session for an order
     *
     * @param Order $order
     * @return Session
     * @throws ApiErrorException
     */
    public function createCheckoutSession(Order $order): Session
    {
        $lineItems = [];
        
        // Add order items to line items
        foreach ($order->getOrderItems() as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item->getProductName(),
                        'description' => $item->getProduct()->getDescription(),
                        'images' => $this->getProductImages($item->getProduct()),
                    ],
                    'unit_amount' => $this->convertToCents($item->getUnitPrice()),
                ],
                'quantity' => $item->getQuantity(),
            ];
        }
        
        // Create Checkout Session
        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'customer_email' => $order->getUserRef()->getEmail(),
            'success_url' => $this->router->generate('app_payment_success', [
                'orderId' => $order->getId(),
                'sessionId' => '{CHECKOUT_SESSION_ID}'
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->router->generate('app_payment_cancel', [
                'orderId' => $order->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'metadata' => [
                'order_id' => $order->getId(),
                'order_number' => $order->getOrderNumber()
            ],
        ]);
    }

    /**
     * Verify Stripe webhook signature
     *
     * @param string $payload
     * @param string $sigHeader
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, string $sigHeader): bool
    {
        try {
            \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->webhookSecret
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get product images for Stripe
     *
     * @param \App\Entity\Product $product
     * @return array
     */
    private function getProductImages(\App\Entity\Product $product): array
    {
        $images = [];
        
        if ($product->getProductImages()->count() > 0) {
            foreach ($product->getProductImages() as $image) {
                $images[] = $this->router->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL) . 
                            'uploads/products/' . $image->getFilename();
                break; // Only use the first image
            }
        }
        
        return $images;
    }

    /**
     * Convert price from dollars to cents for Stripe
     *
     * @param string $amount
     * @return int
     */
    private function convertToCents(string $amount): int
    {
        // Convert from decimal (e.g., 10.99) to cents (1099)
        return (int) ($amount * 100);
    }
}
