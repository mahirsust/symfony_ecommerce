<?php

namespace App\Form;

use App\Entity\ProductReview;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Review Title',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a title for your review']),
                    new Length(['min' => 3, 'max' => 255])
                ]
            ])
            ->add('rating', ChoiceType::class, [
                'label' => 'Rating',
                'choices' => [
                    '5 Stars' => 5,
                    '4 Stars' => 4,
                    '3 Stars' => 3,
                    '2 Stars' => 2,
                    '1 Star' => 1
                ],
                'expanded' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please select a rating'])
                ]
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Your Review',
                'attr' => ['rows' => 5],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your review']),
                    new Length(['min' => 10, 'max' => 1000])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductReview::class,
        ]);
    }
}
