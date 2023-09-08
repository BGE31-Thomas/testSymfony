<?php

namespace App\Controller\Admin;

use App\Entity\Images;
use App\Entity\Products;
use App\Form\ProductsFormType;
use App\Service\PicturesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    public function add(
            Request $request,
            EntityManagerInterface $em,
            SluggerInterface $slugger,
            PicturesService $picturesService
        ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = new Products();

        $productForm = $this->createForm(ProductsFormType::class, $product);

        $productForm->handleRequest($request);

        if($productForm->isSubmitted() AND $productForm->isValid()){

            $images = $productForm->get('images')->getData();

            foreach($images as $image){
                $folder = 'products';
                $fichier = $picturesService->add($image,$folder,300,300);
                $img = new Images();
                $img->setName($fichier);
                $product->addImage($img);

            }
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
    public function edit(
        Products $product,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        PicturesService $picturesService): Response
    {
        $this->denyAccessUnlessGranted('PRODUCT_EDIT',$product);

        $price = $product->getPrice() / 100;
        $product->setPrice($price);

        $productForm = $this->createForm(ProductsFormType::class, $product);

        $productForm->handleRequest($request);

        if($productForm->isSubmitted() AND $productForm->isValid()){

            $images = $productForm->get('images')->getData();

            foreach($images as $image){
                $folder = 'products';
                $fichier = $picturesService->add($image,$folder,300,300);
                $img = new Images();
                $img->setName($fichier);
                $product->addImage($img);

            }

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
            'productForm' => $productForm->createView(),
            'product' => $product
        ]);
    }

    #[Route('/delete/{id}', name: 'delete')]
    public function delete(Products $product,EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('PRODUCT_DELETE',$product,);
        
        $em->remove($product);
        $em->flush(); 
        return $this->redirectToRoute('app_admin_products_index');
        
    }

    #[Route('/delete/image/{id}', name: 'delete_image')]
    public function deleteImage(
        Images $image,
        Request $request,
        EntityManagerInterface $em,
        PicturesService $picturesService): JsonResponse
    {
        $data = json_decode($request->getContent(),true);
        if($this->isCsrfTokenValid('delete'.$image->getId(),$data['_token'])){
            $nom = $image->getName();
            if($picturesService->delete($nom,'products',300,300)){
                $em->remove($image);
                $em->flush();
                return new JsonResponse(['success' => true],200);
            }
            return new JsonResponse(['error' => 'Erreur de suppression'],400);
        }
        return new JsonResponse(['error' => 'Token invalide'],400);
    }
}
