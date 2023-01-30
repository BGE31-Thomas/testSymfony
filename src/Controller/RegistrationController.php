<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use App\Repository\UsersRepository;
use App\Security\UsersAuthenticator;
use App\Service\JWTService;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, UsersAuthenticator $authenticator, EntityManagerInterface $entityManager, SendMailService $mail, JWTService $jwtservice, UsersRepository $usersRepository): Response
    {
        $user = new Users();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email
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
    public function verifyUser($token,JWTService $jwtservice,UsersRepository $usersRepository,EntityManagerInterface $em):Response
    {
        
        if($jwtservice->isValid($token) AND !$jwtservice->hasExpired($token) AND $jwtservice->check($token,$this->getParameter('app.jwtsecret'))){
            $payload = $jwtservice->getPayload($token);
            $user = $usersRepository->find($payload['user_id']);
            if ($user AND !$user->getIsVerified()){
                $user->setIsVerified(true);
                $em->flush($user);
                $this->addFlash('success',"Le token est valide.");
                return  $this->redirectToRoute('app_profile_index');
            }
        }

        $this->addFlash('danger',"Le token est invalide ou a expiré.");
        return $this->redirectToRoute('app_login');

    }

    #[Route('/renvoiVerif', name: 'resend_verif')]
    public function resendVerif(JWTService $jwtservice,SendMailService $sms,UsersRepository $usersRepository):Response
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
        $header = [
            'typ' => 'JWT',
            'alg' => 'H256'
        ];

        $payload = [
            'user_id' => $user->getId()
        ];

        $token = $jwtservice->generate($header,$payload, $this->getParameter('app.jwtsecret'));
        
        $mail->send(
            'from@monsite.com',
            $user->getEmail(),
            "Activation de votre compte",
            "register",
            compact('user','token')
        );
    }
}
