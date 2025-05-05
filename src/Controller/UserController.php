<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\User;
use App\Form\AddressType;
use App\Form\UserProfileType;
use App\Repository\AddressRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderRepository $orderRepository,
        private AddressRepository $addressRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/', name: 'app_user_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Your profile has been updated successfully.');
            return $this->redirectToRoute('app_user_profile');
        }
        
        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
    
    #[Route('/change-password', name: 'app_user_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');
            
            // Validate current password
            if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->redirectToRoute('app_user_change_password');
            }
            
            // Validate new password
            if (strlen($newPassword) < 8) {
                $this->addFlash('error', 'New password must be at least 8 characters long.');
                return $this->redirectToRoute('app_user_change_password');
            }
            
            // Validate password confirmation
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'New password and confirmation do not match.');
                return $this->redirectToRoute('app_user_change_password');
            }
            
            // Update password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Your password has been changed successfully.');
            return $this->redirectToRoute('app_user_profile');
        }
        
        return $this->render('user/change_password.html.twig', [
            'user' => $user,
        ]);
    }
    
    #[Route('/addresses', name: 'app_user_addresses', methods: ['GET'])]
    public function addresses(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $addresses = $this->addressRepository->findBy(['user' => $user]);
        
        return $this->render('user/addresses.html.twig', [
            'addresses' => $addresses,
        ]);
    }
    
    #[Route('/addresses/new', name: 'app_user_address_new', methods: ['GET', 'POST'])]
    public function newAddress(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $address = new Address();
        $address->setUser($user);
        
        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // If this is the first address or set as default, make it the default
            if ($address->isIsDefault() || count($user->getAddresses()) === 0) {
                // Reset default flag on all other addresses
                foreach ($user->getAddresses() as $existingAddress) {
                    $existingAddress->setIsDefault(false);
                }
                $address->setIsDefault(true);
            }
            
            $this->entityManager->persist($address);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Address added successfully.');
            return $this->redirectToRoute('app_user_addresses');
        }
        
        return $this->render('user/address_form.html.twig', [
            'form' => $form,
            'address' => $address,
        ]);
    }
    
    #[Route('/addresses/{id}/edit', name: 'app_user_address_edit', methods: ['GET', 'POST'])]
    public function editAddress(Request $request, Address $address): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Security check - only allow users to edit their own addresses
        if ($address->getUser() !== $user) {
            throw $this->createAccessDeniedException('You cannot edit this address.');
        }
        
        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // If set as default, make it the default
            if ($address->isIsDefault()) {
                // Reset default flag on all other addresses
                foreach ($user->getAddresses() as $existingAddress) {
                    if ($existingAddress->getId() !== $address->getId()) {
                        $existingAddress->setIsDefault(false);
                    }
                }
            }
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Address updated successfully.');
            return $this->redirectToRoute('app_user_addresses');
        }
        
        return $this->render('user/address_form.html.twig', [
            'form' => $form,
            'address' => $address,
        ]);
    }
    
    #[Route('/addresses/{id}/delete', name: 'app_user_address_delete', methods: ['POST'])]
    public function deleteAddress(Request $request, Address $address): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Security check - only allow users to delete their own addresses
        if ($address->getUser() !== $user) {
            throw $this->createAccessDeniedException('You cannot delete this address.');
        }
        
        if ($this->isCsrfTokenValid('delete'.$address->getId(), $request->request->get('_token'))) {
            $wasDefault = $address->isIsDefault();
            
            $this->entityManager->remove($address);
            $this->entityManager->flush();
            
            // If the deleted address was the default, set another address as default
            if ($wasDefault) {
                $addresses = $user->getAddresses();
                if (count($addresses) > 0) {
                    $addresses[0]->setIsDefault(true);
                    $this->entityManager->flush();
                }
            }
            
            $this->addFlash('success', 'Address deleted successfully.');
        }
        
        return $this->redirectToRoute('app_user_addresses');
    }
    
    #[Route('/addresses/{id}/set-default', name: 'app_user_address_set_default', methods: ['POST'])]
    public function setDefaultAddress(Request $request, Address $address): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Security check - only allow users to modify their own addresses
        if ($address->getUser() !== $user) {
            throw $this->createAccessDeniedException('You cannot modify this address.');
        }
        
        if ($this->isCsrfTokenValid('set-default'.$address->getId(), $request->request->get('_token'))) {
            // Reset default flag on all addresses
            foreach ($user->getAddresses() as $existingAddress) {
                $existingAddress->setIsDefault(false);
            }
            
            // Set the selected address as default
            $address->setIsDefault(true);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Default address updated successfully.');
        }
        
        return $this->redirectToRoute('app_user_addresses');
    }
}
