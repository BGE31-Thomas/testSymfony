<?php

namespace App\Controller;

use App\Form\ResetPassRequestFormType;
use App\Form\ResetPassFormType;
use App\Repository\UsersRepository;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'controller_name' => 'LoginController',
            'last_username' => $lastUsername,
            'error'         => $error
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path:'/forgetPass',name:'app_forgotten_password')]
    public function forgottenPassword(Request $request,UsersRepository $usersRepository,TokenGeneratorInterface $tgi, EntityManagerInterface $em,SendMailService $sms):Response
    {
        $form = $this->createForm(ResetPassRequestFormType::class);

        $form->handleRequest($request);

        if($form->isSubmitted() AND $form->isValid()){
            $user = $usersRepository->findOneByEmail($form->get('email')->getData());
            if ($user){
                $token = $tgi->generateToken();
                $user->setResetToken($token);
                $em->persist($user);
                $em->flush();

                $url = $this->generateUrl('app_reset_pass',['token'=>$token],UrlGeneratorInterface::ABSOLUTE_URL);

                $context= compact('url','user');
                $sms->send(
                    'no-reply@monsite.fr',
                    $user->getEmail(),
                    "Réinitialisation de votre mot de passe",
                    "password_reset",
                    $context
                );

                $this->addFlash('success','Email envoyé avec succès');
                return $this->redirectToRoute('app_login');
            }
            $this->addFlash('danger', "Un problème est survenu");
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('security/reset_pass_request.html.twig',[
            'requestPassForm' => $form->createView()
        ]);
    }

    #[Route('/forgetPass/{token}',name:'app_reset_pass')]
    public function resetPass(string $token,Request $request,UsersRepository $usersRepository,EntityManagerInterface $emi,UserPasswordHasherInterface $hash):Response
    {
        $user = $usersRepository->findOneByResetToken($token);
        if ($user){
            $form = $this->createForm(ResetPassFormType::class);

            $form->handleRequest($request);
            if($form->isSubmitted() AND $form->isValid()){
                $user->setResetToken('');
                $user->setPassword(
                    $hash->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
                $emi->persist($user);
                $emi->flush();

                $this->addFlash('success','Mot de passe changé avec succès');

                return $this->redirectToRoute('app_login');
            }
            return $this->render('security/reset_password.html.twig',[
                'passForm' => $form->createView()
            ]);
        }
        $this->addFlash('danger','Jeton invalide');
        return $this->redirectToRoute('app_login');
    }
}
