<?php

namespace App\Controller\Api;

use App\Entity\SaleEntry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SalesController extends AbstractController
{
    #[Route('/api/sales', name: 'app_api_sales')]
    public function index(EntityManagerInterface $em): Response
    {
        $data = $em->createQuery(
            'SELECT s.date, p.name, se.quantity, c.name as category ' .
            'FROM ' . SaleEntry::class . ' se ' .
            'JOIN se.sale s ' . 
            'JOIN se.product p ' . 
            'JOIN p.category c ' .
            ''
        )->getArrayResult();
        return $this->json($data, context: ['json_encode_options' => JSON_UNESCAPED_UNICODE]);
    }
}
