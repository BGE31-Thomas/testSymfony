<?php

namespace App\Controller\Admin;

use App\Entity\Products;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/products', name: 'app_admin_products_')]
class ProductsController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('admin/products/index.html.twig', [
            'controller_name' => 'UsersController',
        ]);
    }

    #[Route('/add', name: 'add')]
    public function add(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('admin/products/index.html.twig', [
            'controller_name' => 'UsersController',
        ]);
    }

    #[Route('/edit/{id}', name: 'edit')]
    public function edit(Products $product): Response
    {
        $this->denyAccessUnlessGranted('PRODUCT_EDIT',$product);
        
        return $this->render('admin/products/index.html.twig', [
            'controller_name' => 'UsersController',
        ]);
    }

    #[Route('/delete/{id}', name: 'delete')]
    public function delete(Products $product): Response
    {
        return $this->render('admin/products/index.html.twig', [
            'controller_name' => 'UsersController',
        ]);
    }
}
