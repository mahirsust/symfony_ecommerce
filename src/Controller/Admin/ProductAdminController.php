<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/product')]
#[IsGranted('ROLE_ADMIN')]
class ProductAdminController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductService $productService,
        private PaginatorInterface $paginator,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'app_admin_product_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = $this->productRepository->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery();

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/product/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_admin_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->productService->saveProduct($product);

            // Handle product images
            $images = $form->get('images')->getData();
            if ($images) {
                $isFirst = true; // First image will be the main image
                foreach ($images as $image) {
                    $this->productService->addProductImage($product, $image, $isFirst);
                    $isFirst = false;
                }
            }

            $this->addFlash('success', 'Product created successfully.');
            return $this->redirectToRoute('app_admin_product_index');
        }

        return $this->render('admin/product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_product_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        return $this->render('admin/product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->productService->saveProduct($product);

            // Handle product images
            $images = $form->get('images')->getData();
            if ($images) {
                // Check if product already has a main image
                $hasMainImage = false;
                foreach ($product->getProductImages() as $existingImage) {
                    if ($existingImage->isIsMain()) {
                        $hasMainImage = true;
                        break;
                    }
                }

                // Set first uploaded image as main if no main image exists
                $isFirst = !$hasMainImage;

                foreach ($images as $image) {
                    $this->productService->addProductImage($product, $image, $isFirst);
                    $isFirst = false;
                }
            }

            $this->addFlash('success', 'Product updated successfully.');
            return $this->redirectToRoute('app_admin_product_index');
        }

        return $this->render('admin/product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_product_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $this->productService->deleteProduct($product);
            $this->addFlash('success', 'Product deleted successfully.');
        }

        return $this->redirectToRoute('app_admin_product_index');
    }

    #[Route('/{id}/delete-image/{imageId}', name: 'app_admin_product_delete_image', methods: ['POST'])]
    public function deleteImage(Request $request, int $id, int $imageId): Response
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $image = $this->entityManager->getRepository(ProductImage::class)->find($imageId);

        if (!$image || $image->getProduct()->getId() !== $product->getId()) {
            throw $this->createNotFoundException('Image not found');
        }

        if ($this->isCsrfTokenValid('delete-image'.$imageId, $request->request->get('_token'))) {
            $this->productService->removeProductImage($image);
            $this->addFlash('success', 'Image deleted successfully.');
        }

        return $this->redirectToRoute('app_admin_product_edit', ['id' => $product->getId()]);
    }
}
