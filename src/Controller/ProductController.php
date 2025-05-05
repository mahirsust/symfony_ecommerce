<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/product')]
class ProductController extends AbstractController
{
    public function __construct(
        private ProductService $productService,
        private ProductRepository $productRepository,
        private PaginatorInterface $paginator
    ) {
    }

    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = $this->productRepository->createQueryBuilder('p')
            ->where('p.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery();

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('product/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/{slug}', name: 'app_product_show', methods: ['GET'])]
    public function show(string $slug): Response
    {
        // Find the product by slug
        $product = $this->productRepository->findOneBy(['slug' => $slug]);

        // Check if product exists and is active
        if (!$product || !$product->getIsActive()) {
            throw $this->createNotFoundException('Product not found');
        }

        // Get related products from the same category
        $relatedProducts = $this->productRepository->findRelatedProducts($product);

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }

    #[Route('/category/{slug}', name: 'app_product_by_category', methods: ['GET'])]
    public function byCategory(string $slug, Request $request): Response
    {
        $products = $this->productRepository->findByCategory($slug);

        $pagination = $this->paginator->paginate(
            $products,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('product/by_category.html.twig', [
            'pagination' => $pagination,
            'category' => $slug,
        ]);
    }

    #[Route('/search', name: 'app_product_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $query = $request->query->get('q');

        if (empty($query)) {
            return $this->redirectToRoute('app_product_index');
        }

        $products = $this->productRepository->searchProducts($query);

        $pagination = $this->paginator->paginate(
            $products,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('product/search.html.twig', [
            'pagination' => $pagination,
            'query' => $query,
        ]);
    }
}
