<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/order')]
#[IsGranted('ROLE_ADMIN')]
class OrderAdminController extends AbstractController
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderService $orderService,
        private PaginatorInterface $paginator
    ) {
    }

    #[Route('/', name: 'app_admin_order_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = $this->orderRepository->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery();

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/order/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_order_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        return $this->render('admin/order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/update-status', name: 'app_admin_order_update_status', methods: ['POST'])]
    public function updateStatus(Request $request, int $id): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        $status = $request->request->get('status');

        if ($status) {
            $this->orderService->updateOrderStatus($order, $status);
            $this->addFlash('success', 'Order status updated successfully.');
        }

        return $this->redirectToRoute('app_admin_order_show', ['id' => $order->getId()]);
    }

    #[Route('/{id}/update-payment-status', name: 'app_admin_order_update_payment_status', methods: ['POST'])]
    public function updatePaymentStatus(Request $request, int $id): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        $paymentStatus = $request->request->get('payment_status');

        if ($paymentStatus) {
            $this->orderService->updatePaymentStatus($order, $paymentStatus);
            $this->addFlash('success', 'Payment status updated successfully.');
        }

        return $this->redirectToRoute('app_admin_order_show', ['id' => $order->getId()]);
    }
}
