<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use App\Repository\UsersRepository;
use App\Security\UsersAuthenticator;
use App\Service\JWTService;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher, 
        UserAuthenticatorInterface $userAuthenticator, 
        UsersAuthenticator $authenticator, 
        EntityManagerInterface $entityManager, 
        SendMailService $mail, 
        JWTService $jwtservice): Response
    {
        //Création d'un nouvel utilisateur
        $user = new Users();
        //Création du formulaire
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //Le mdp est crypté et associé à l'utilisateur
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            //Enregistrement des données dans la base
            $entityManager->persist($user);
            $entityManager->flush();
            //Envoi de l'email d'activation de compte
            $this->sendMail($user,$jwtservice,$mail);
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/{token}',name: 'verify_user')]
    public function verifyUser(
        $token,
        JWTService $jwtservice,
        UsersRepository $usersRepository,
        EntityManagerInterface $em):Response
    {
        //On vérifie que le token est valide, n'a pas expiré et s'il n'a pas été modifié
        if($jwtservice->isValid($token) AND !$jwtservice->hasExpired($token) AND $jwtservice->check($token,$this->getParameter('app.jwtsecret'))){
            //On récupère le payload
            $payload = $jwtservice->getPayload($token);
            //On récupère l'utilisateur en fonction de l'user_id du payload
            $user = $usersRepository->find($payload['user_id']);
            //On vérifie que l'utilisateur existe et qu'il n'a pas déjà été activé
            if ($user AND !$user->getIsVerified()){
                //On l'active
                $user->setIsVerified(true);
                //On enregistre les données dans la base
                $em->flush($user);
                $this->addFlash('success',"Le token est valide.");
                return  $this->redirectToRoute('app_profile_index');
            }
        }

        $this->addFlash('danger',"Le token est invalide ou a expiré.");
        return $this->redirectToRoute('app_login');

    }

    #[Route('/renvoiVerif', name: 'resend_verif')]
    public function resendVerif(
        JWTService $jwtservice,
        SendMailService $sms):Response
    {
        $user = new Users();
        $user = $this->getUser();
        
        if(!$user){
            $this->addFlash('danger',"Vous devez être connecté.e pour accéder à cette page.");
            return $this->redirectToRoute('app_login');
        }
        if($user->getIsVerified()){
            $this->addFlash('warning',"Cet utilisateur est déjà activé.");
            return $this->redirectToRoute('app_profile_index');
        }
        $this->sendMail($user,$jwtservice,$sms);
        $this->addFlash('success',"Email renvoyé.");
        return $this->redirectToRoute('app_profile_index');
    }

   
    public function sendMail(Users $user,JWTService $jwtservice,SendMailService $mail): void
    {
        //Création du header
        $header = [
            'typ' => 'JWT',
            'alg' => 'H256'
        ];

        //Création du payload
        $payload = [
            'user_id' => $user->getId()
        ];

        //Cryptation du token
        $token = $jwtservice->generate($header,$payload, $this->getParameter('app.jwtsecret'));
        
        //Envoi de l'email
        $mail->send(
            //Exp:
            'from@monsite.com',
            //Dest
            $user->getEmail(),
            //Sujet
            "Activation de votre compte",
            //Route à utiliser
            "register",
            //Variables utiles : $user,$token
            compact('user','token')
        );
    }
}
