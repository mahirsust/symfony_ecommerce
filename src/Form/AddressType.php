<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('streetAddress', TextType::class, [
                'label' => 'Street Address',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your street address']),
                    new Length(['min' => 5, 'max' => 255])
                ]
            ])
            ->add('city', TextType::class, [
                'label' => 'City',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your city']),
                    new Length(['min' => 2, 'max' => 255])
                ]
            ])
            ->add('state', TextType::class, [
                'label' => 'State/Province',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your state or province']),
                    new Length(['min' => 2, 'max' => 255])
                ]
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Postal/ZIP Code',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your postal code']),
                    new Length(['min' => 3, 'max' => 20])
                ]
            ])
            ->add('country', CountryType::class, [
                'label' => 'Country',
                'constraints' => [
                    new NotBlank(['message' => 'Please select your country'])
                ]
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Phone Number',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your phone number']),
                    new Length(['min' => 5, 'max' => 20])
                ]
            ])
            ->add('isDefault', CheckboxType::class, [
                'label' => 'Set as default address',
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
