<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private SluggerInterface $slugger,
        private string $productImagesDirectory = '%kernel.project_dir%/public/uploads/products'
    ) {
    }

    /**
     * Get all active products
     *
     * @return array
     */
    public function getActiveProducts(): array
    {
        return $this->productRepository->findBy(['isActive' => true]);
    }

    /**
     * Get products by category
     *
     * @param Category $category
     * @param bool $includeInactive
     *
     * @return array
     */
    public function getProductsByCategory(Category $category, bool $includeInactive = false): array
    {
        $criteria = ['category' => $category];

        if (!$includeInactive) {
            $criteria['isActive'] = true;
        }

        return $this->productRepository->findBy($criteria);
    }

    /**
     * Get products by search term
     *
     * @param string $searchTerm
     *
     * @return array
     */
    public function searchProducts(string $searchTerm): array
    {
        return $this->productRepository->searchByTerm($searchTerm);
    }

    /**
     * Create a new product
     *
     * @param string $name
     * @param string $description
     * @param string $price
     * @param int $stock
     * @param string $sku
     * @param Category $category
     * @param string|null $discountPrice
     * @param bool $isActive
     *
     * @return Product
     */
    public function createProduct(
        string $name,
        string $description,
        string $price,
        int $stock,
        string $sku,
        Category $category,
        ?string $discountPrice = null,
        bool $isActive = true
    ): Product {
        $product = new Product();
        $product->setName($name);
        $product->setSlug($this->slugger->slug(strtolower($name)));
        $product->setDescription($description);
        $product->setPrice($price);
        $product->setDiscountPrice($discountPrice);
        $product->setStock($stock);
        $product->setSku($sku);
        $product->setIsActive($isActive);
        $product->setCategory($category);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    /**
     * Update product
     *
     * @param Product $product
     * @param array $data
     *
     * @return Product
     */
    public function updateProduct(Product $product, array $data): Product
    {
        if (isset($data['name'])) {
            $product->setName($data['name']);
            $product->setSlug($this->slugger->slug(strtolower($data['name'])));
        }

        if (isset($data['description'])) {
            $product->setDescription($data['description']);
        }

        if (isset($data['price'])) {
            $product->setPrice($data['price']);
        }

        if (array_key_exists('discountPrice', $data)) {
            $product->setDiscountPrice($data['discountPrice']);
        }

        if (isset($data['stock'])) {
            $product->setStock($data['stock']);
        }

        if (isset($data['sku'])) {
            $product->setSku($data['sku']);
        }

        if (isset($data['isActive'])) {
            $product->setIsActive($data['isActive']);
        }

        if (isset($data['category'])) {
            $category = $this->categoryRepository->find($data['category']);
            if ($category) {
                $product->setCategory($category);
            }
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    /**
     * Add image to product
     *
     * @param Product $product
     * @param UploadedFile $file
     * @param bool $isMain
     * @param int|null $position
     *
     * @return ProductImage
     */
    public function addProductImage(
        Product $product,
        UploadedFile $file,
        bool $isMain = false,
        ?int $position = null
    ): ProductImage {
        // Generate unique filename
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        // Move the file to the directory where product images are stored
        $file->move($this->productImagesDirectory, $newFilename);

        // If this is the main image and another main image exists, update it
        if ($isMain) {
            $mainImages = $product->getProductImages()->filter(
                fn(ProductImage $image) => $image->isIsMain()
            );

            foreach ($mainImages as $mainImage) {
                $mainImage->setIsMain(false);
                $this->entityManager->persist($mainImage);
            }
        }

        // If position is not provided, set it as the last position
        if ($position === null) {
            $position = count($product->getProductImages()) + 1;
        }

        // Create new product image
        $productImage = new ProductImage();
        $productImage->setProduct($product);
        $productImage->setFilename($newFilename);
        $productImage->setIsMain($isMain);
        $productImage->setPosition($position);

        $this->entityManager->persist($productImage);
        $this->entityManager->flush();

        return $productImage;
    }

    /**
     * Remove product image
     *
     * @param ProductImage $productImage
     *
     * @return bool
     */
    public function removeProductImage(ProductImage $productImage): bool
    {
        // Delete file from filesystem
        $filesystem = new Filesystem();
        $imagePath = $this->productImagesDirectory.'/'.$productImage->getFilename();

        if ($filesystem->exists($imagePath)) {
            $filesystem->remove($imagePath);
        }

        // Remove from database
        $this->entityManager->remove($productImage);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Get product by slug
     *
     * @param string $slug
     *
     * @return Product|null
     */
    public function getProductBySlug(string $slug): ?Product
    {
        return $this->productRepository->findOneBy(['slug' => $slug, 'isActive' => true]);
    }

    /**
     * Get related products
     *
     * @param Product $product
     * @param int $limit
     *
     * @return array
     */
    public function getRelatedProducts(Product $product, int $limit = 4): array
    {
        return $this->productRepository->findRelatedProducts($product, $limit);
    }

    /**
     * Save product to database
     *
     * @param Product $product
     *
     * @return Product
     */
    public function saveProduct(Product $product): Product
    {
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    /**
     * Delete product
     *
     * @param Product $product
     *
     * @return bool
     */
    public function deleteProduct(Product $product): bool
    {
        // Remove product images first
        foreach ($product->getProductImages() as $image) {
            $this->removeProductImage($image);
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Update product stock
     *
     * @param Product $product
     * @param int $quantity
     *
     * @return Product
     */
    public function updateStock(Product $product, int $quantity): Product
    {
        $newStock = $product->getStock() + $quantity;
        $product->setStock($newStock < 0 ? 0 : $newStock);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }
}
