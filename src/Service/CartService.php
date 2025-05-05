<?php

namespace App\Service;

use App\Entity\Product;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    private SessionInterface $session;
    
    public function __construct(private RequestStack $requestStack)
    {
        $this->session = $this->requestStack->getSession();
    }
    
    /**
     * Get the current cart data
     * 
     * @return array
     */
    public function getCart(): array
    {
        return $this->session->get('cart', []);
    }
    
    /**
     * Add a product to cart
     * 
     * @param int $productId
     * @param int $quantity
     * @param string $productName
     * @param string $sku
     * @param string $price
     * 
     * @return void
     */
    public function addToCart(int $productId, int $quantity = 1, string $productName = '', string $sku = '', string $price = '0.00'): void
    {
        $cart = $this->getCart();
        
        if (!isset($cart[$productId])) {
            $cart[$productId] = [
                'quantity' => 0,
                'productName' => $productName,
                'sku' => $sku,
                'price' => $price
            ];
        }
        
        $cart[$productId]['quantity'] += $quantity;
        
        $this->session->set('cart', $cart);
    }
    
    /**
     * Remove a product from cart
     * 
     * @param int $productId
     * 
     * @return void
     */
    public function removeFromCart(int $productId): void
    {
        $cart = $this->getCart();
        
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
        }
        
        $this->session->set('cart', $cart);
    }
    
    /**
     * Update product quantity in cart
     * 
     * @param int $productId
     * @param int $quantity
     * 
     * @return void
     */
    public function updateQuantity(int $productId, int $quantity): void
    {
        $cart = $this->getCart();
        
        if (isset($cart[$productId])) {
            if ($quantity <= 0) {
                $this->removeFromCart($productId);
                return;
            }
            
            $cart[$productId]['quantity'] = $quantity;
        }
        
        $this->session->set('cart', $cart);
    }
    
    /**
     * Clear the cart
     * 
     * @return void
     */
    public function clearCart(): void
    {
        $this->session->remove('cart');
    }
    
    /**
     * Get total number of items in cart
     * 
     * @return int
     */
    public function getItemCount(): int
    {
        $cart = $this->getCart();
        $count = 0;
        
        foreach ($cart as $item) {
            $count += $item['quantity'];
        }
        
        return $count;
    }
    
    /**
     * Get total price of items in cart
     * 
     * @return float
     */
    public function getTotalPrice(): string
    {
        $cart = $this->getCart();
    
        if (empty($cart)) {
            return '0.00';
        }
        
        $total = '0.00';
        $scale = 2;
        
        foreach ($cart as $item) {
            
            $price = $item['price'] ?? '0.00';
            $quantity = (string)($item['quantity'] ?? 0);
            
            $itemTotal = bcmul($price, $quantity, $scale);
            $total = bcadd($total, $itemTotal, $scale);
        }
        
        return $total;
    }
    
    /**
     * Check if product is in cart
     * 
     * @param int $productId
     * 
     * @return bool
     */
    public function hasItem(int $productId): bool
    {
        $cart = $this->getCart();
        
        return isset($cart[$productId]);
    }
}