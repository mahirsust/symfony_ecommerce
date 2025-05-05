<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/category')]
class CategoryController extends AbstractController
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {
    }

    #[Route('/', name: 'app_category_index', methods: ['GET'])]
    public function index(): Response
    {
        // Get only parent categories
        $categories = $this->categoryRepository->findBy(['parent' => null]);

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/{slug}', name: 'app_category_show', methods: ['GET'])]
    public function show(string $slug): Response
    {
        // Find the category by slug
        $category = $this->categoryRepository->findOneBy(['slug' => $slug]);

        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        // Get subcategories if any
        $subcategories = $this->categoryRepository->findBy(['parent' => $category]);

        return $this->render('category/show.html.twig', [
            'category' => $category,
            'subcategories' => $subcategories,
        ]);
    }
}
