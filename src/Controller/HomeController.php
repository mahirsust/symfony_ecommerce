<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        // Get featured products (newest active products)
        $featuredProducts = $this->productRepository->findBy(
            ['isActive' => true],
            ['createdAt' => 'DESC'],
            8
        );
        
        // Get main categories
        $mainCategories = $this->categoryRepository->findBy(['parent' => null]);

        return $this->render('home/index.html.twig', [
            'featuredProducts' => $featuredProducts,
            'mainCategories' => $mainCategories,
        ]);
    }
}
