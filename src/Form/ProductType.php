<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a product name']),
                    new Length(['min' => 3, 'max' => 255])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a product description']),
                    new Length(['min' => 10])
                ]
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Price',
                'currency' => 'USD',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a price']),
                    new Positive(['message' => 'Price must be positive'])
                ]
            ])
            ->add('discountPrice', MoneyType::class, [
                'label' => 'Discount Price (optional)',
                'currency' => 'USD',
                'required' => false,
                'constraints' => [
                    new Positive(['message' => 'Discount price must be positive'])
                ]
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock Quantity',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter stock quantity']),
                    new PositiveOrZero(['message' => 'Stock must be zero or positive'])
                ]
            ])
            ->add('sku', TextType::class, [
                'label' => 'SKU (Stock Keeping Unit)',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a SKU']),
                    new Length(['min' => 3, 'max' => 255])
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active (visible in store)',
                'required' => false
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Category',
                'constraints' => [
                    new NotBlank(['message' => 'Please select a category'])
                ]
            ])
            ->add('images', FileType::class, [
                'label' => 'Product Images',
                'multiple' => true,
                'mapped' => false,
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
