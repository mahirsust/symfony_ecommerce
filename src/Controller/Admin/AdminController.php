<?php

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private OrderRepository $orderRepository,
        private UserRepository $userRepository
    ) {
    }

    #[Route('/', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        // Get some statistics for the dashboard
        $totalProducts = $this->productRepository->count([]);
        $totalOrders = $this->orderRepository->count([]);
        $totalUsers = $this->userRepository->count([]);
        
        // Get recent orders
        $recentOrders = $this->orderRepository->findBy([], ['createdAt' => 'DESC'], 5);
        
        // Get low stock products
        $lowStockProducts = $this->productRepository->findLowStockProducts();

        return $this->render('admin/dashboard.html.twig', [
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,
            'totalUsers' => $totalUsers,
            'recentOrders' => $recentOrders,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }
}
