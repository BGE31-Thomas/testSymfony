<?php

namespace App\Controller\Admin;

use App\Entity\Products;
use App\Form\ProductsFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

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
    public function add(Request $request,EntityManagerInterface $em,SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = new Products();

        $productForm = $this->createForm(ProductsFormType::class, $product);

        $productForm->handleRequest($request);

        if($productForm->isSubmitted() AND $productForm->isValid()){
            $slug = $slugger->slug($product->getName());

            $product->setSlug($slug);
            $prix = $product->getPrice() * 100;
            $product->setPrice($prix);

            $em->persist($product);
            $em->flush();

            $this->addFlash('success','Produit ajouté avec succès');

            return $this->redirectToRoute('app_admin_products_index');
        }
        return $this->render('admin/products/add.html.twig', [
            'productForm' => $productForm->createView()
        ]);
    }

    #[Route('/edit/{id}', name: 'edit')]
    public function edit(Products $product,Request $request,EntityManagerInterface $em,SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('PRODUCT_EDIT',$product);

        $price = $product->getPrice() / 100;
        $product->setPrice($price);

        $productForm = $this->createForm(ProductsFormType::class, $product);

        $productForm->handleRequest($request);

        if($productForm->isSubmitted() AND $productForm->isValid()){
            $slug = $slugger->slug($product->getName());

            $product->setSlug($slug);
            $prix = $product->getPrice() * 100;
            $product->setPrice($prix);

            $em->persist($product);
            $em->flush();

            $this->addFlash('success','Produit ajouté avec succès');

            return $this->redirectToRoute('app_admin_products_index');
        }

        return $this->render('admin/products/edit.html.twig', [
            'productForm' => $productForm->createView()
        ]);
    }

    #[Route('/delete/{id}', name: 'delete')]
    public function delete(Products $product): Response
    {
        return $this->render('admin/products/delete.html.twig');
    }
}
