<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Uuid;
use App\Entity\Address;

class OrderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CartService $cartService,
        private ProductRepository $productRepository,
        private OrderRepository $orderRepository,
        private SluggerInterface $slugger,
        private ?EmailService $emailService = null
    ) {
    }

    /**
     * Create an order from cart items
     *
     * @param User $user
     * @param Address $address
     * @param string $paymentMethod
     *
     * @return Order|null
     */
    public function createOrderFromCart(
        User $user,
        Address $address,
        string $paymentMethod = 'cash_on_delivery'
    ): ?Order {
        $cart = $this->cartService->getCart();

        if (empty($cart)) {
            return null;
        }

        // Format address as string
        $addressStr = $address->getStreetAddress() . ', ' .
                      $address->getCity() . ', ' .
                      $address->getState() . ' ' .
                      $address->getPostalCode() . ', ' .
                      $address->getCountry() . ' - ' .
                      $address->getPhoneNumber();

        // Create new order
        $order = new Order();
        $order->setUserRef($user);
        $order->setOrderNumber($this->generateOrderNumber());
        $order->setStatus('pending');
        $order->setShippingAddress($addressStr);
        $order->setBillingAddress($addressStr); // Using same address for billing
        $order->setPaymentMethod($paymentMethod);
        $order->setPaymentStatus('unpaid');
        $order->setTotalAmount($this->cartService->getTotalPrice());

        $this->entityManager->persist($order);

        // Create order items
        foreach ($cart as $productId => $item) {
            $product = $this->productRepository->find($productId);

            if (!$product) {
                continue;
            }

            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setProduct($product);
            $orderItem->setQuantity($item['quantity']);
            $orderItem->setUnitPrice($item['price']);
            $totalPrice = bcmul($item['price'], (string)$item['quantity'], 2);
            $orderItem->setTotalPrice($totalPrice);
            $orderItem->setProductName($product->getName());
            $orderItem->setProductSku($product->getSku());

            $this->entityManager->persist($orderItem);

            // Update product stock
            $product->setStock($product->getStock() - $item['quantity']);
            $this->entityManager->persist($product);
        }

        $this->entityManager->flush();

        // Clear the cart after order is created
        $this->cartService->clearCart();

        // Send order confirmation email
        if ($this->emailService !== null) {
            $this->emailService->sendOrderConfirmation($order);
        }

        return $order;
    }

    /**
     * Generate a unique order number
     *
     * @return string
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'ORD-';

        return $prefix . strtoupper(str_replace('-', '', Uuid::v4()->toRfc4122()));
    }

    /**
     * Update order status
     *
     * @param Order $order
     * @param string $status
     *
     * @return Order
     */
    public function updateOrderStatus(Order $order, string $status): Order
    {
        $previousStatus = $order->getStatus();

        // Only update if status has changed
        if ($previousStatus !== $status) {
            $order->setStatus($status);
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            // Send order status update email
            if ($this->emailService !== null) {
                $this->emailService->sendOrderStatusUpdate($order, $previousStatus);
            }
        }

        return $order;
    }

    /**
     * Update payment status
     *
     * @param Order $order
     * @param string $paymentStatus
     *
     * @return Order
     */
    public function updatePaymentStatus(Order $order, string $paymentStatus): Order
    {
        $order->setPaymentStatus($paymentStatus);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    /**
     * Get user orders
     *
     * @param User $user
     *
     * @return array
     */
    public function getUserOrders(User $user): array
    {
        return $this->orderRepository->findBy(['userRef' => $user], ['createdAt' => 'DESC']);
    }

    /**
     * Get order by order number
     *
     * @param string $orderNumber
     *
     * @return Order|null
     */
    public function getOrderByNumber(string $orderNumber): ?Order
    {
        return $this->orderRepository->findOneBy(['orderNumber' => $orderNumber]);
    }
}