<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private RouterInterface $router,
        private string $senderEmail = 'noreply@symfony-ecommerce.com',
        private string $senderName = 'Symfony E-Commerce'
    ) {
    }

    /**
     * Send order confirmation email
     *
     * @param Order $order
     * @return bool
     */
    public function sendOrderConfirmation(Order $order): bool
    {
        $user = $order->getUserRef();
        $orderUrl = $this->router->generate('app_order_show', [
            'id' => $order->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $subject = 'Order Confirmation - #' . $order->getOrderNumber();
        $htmlContent = $this->twig->render('emails/order_confirmation.html.twig', [
            'order' => $order,
            'user' => $user,
            'orderUrl' => $orderUrl
        ]);

        return $this->sendEmail($user->getEmail(), $subject, $htmlContent);
    }

    /**
     * Send order status update email
     *
     * @param Order $order
     * @param string $previousStatus
     * @return bool
     */
    public function sendOrderStatusUpdate(Order $order, string $previousStatus): bool
    {
        $user = $order->getUserRef();
        $orderUrl = $this->router->generate('app_order_show', [
            'id' => $order->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $subject = 'Order Status Update - #' . $order->getOrderNumber();
        $htmlContent = $this->twig->render('emails/order_status_update.html.twig', [
            'order' => $order,
            'user' => $user,
            'previousStatus' => $previousStatus,
            'orderUrl' => $orderUrl
        ]);

        return $this->sendEmail($user->getEmail(), $subject, $htmlContent);
    }

    /**
     * Send welcome email to new user
     *
     * @param User $user
     * @return bool
     */
    public function sendWelcomeEmail(User $user): bool
    {
        $subject = 'Welcome to Symfony E-Commerce';
        $htmlContent = $this->twig->render('emails/welcome.html.twig', [
            'user' => $user
        ]);

        return $this->sendEmail($user->getEmail(), $subject, $htmlContent);
    }

    /**
     * Send password reset email
     *
     * @param User $user
     * @param string $resetToken
     * @return bool
     */
    public function sendPasswordResetEmail(User $user, string $resetToken): bool
    {
        $resetUrl = $this->router->generate('app_reset_password', [
            'token' => $resetToken
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $subject = 'Password Reset Request';
        $htmlContent = $this->twig->render('emails/password_reset.html.twig', [
            'user' => $user,
            'resetUrl' => $resetUrl
        ]);

        return $this->sendEmail($user->getEmail(), $subject, $htmlContent);
    }

    /**
     * Send a generic email
     *
     * @param string $to
     * @param string $subject
     * @param string $htmlContent
     * @param string|null $textContent
     * @return bool
     */
    private function sendEmail(string $to, string $subject, string $htmlContent, ?string $textContent = null): bool
    {
        $email = (new Email())
            ->from(sprintf('"%s" <%s>', $this->senderName, $this->senderEmail))
            ->to($to)
            ->subject($subject)
            ->html($htmlContent);

        if ($textContent) {
            $email->text($textContent);
        }

        try {
            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            // Log the error
            return false;
        }
    }
}
