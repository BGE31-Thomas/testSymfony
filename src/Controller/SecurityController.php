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
    public function forgottenPassword(
        Request $request,
        UsersRepository $usersRepository,
        TokenGeneratorInterface $tgi,
        EntityManagerInterface $em,
        SendMailService $sms):Response
    {
        //Création du formulaire
        $form = $this->createForm(ResetPassRequestFormType::class);

        $form->handleRequest($request);

        //Vérification que le formulaire a été soumis et est bien valide.
        if($form->isSubmitted() AND $form->isValid()){
            //Recherche de l'utilisateur en fonction de l'email renseigné
            $user = $usersRepository->findOneByEmail($form->get('email')->getData());
            if ($user){
                //On génère un token
                $token = $tgi->generateToken();
                //Le token est associé à l'utilisateur
                $user->setResetToken($token);
                //Enregistrement du token
                $em->persist($user);
                $em->flush();

                //Création de l'url avec la route pour réinitialiser le mdp et le token
                $url = $this->generateUrl('app_reset_pass',['token'=>$token],UrlGeneratorInterface::ABSOLUTE_URL);

                //ENvoi des variables utiles : $url,$user
                $context= compact('url','user');
                //Envoi du mail à l'utilisateur
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
    public function resetPass(
        string $token,
        Request $request,
        UsersRepository $usersRepository,
        EntityManagerInterface $emi,
        UserPasswordHasherInterface $hash):Response
    {
        //On retrouve l'utilisateur avec le token
        $user = $usersRepository->findOneByResetToken($token);
        if ($user){
            //Création du formulaire
            $form = $this->createForm(ResetPassFormType::class);

            $form->handleRequest($request);
            if($form->isSubmitted() AND $form->isValid()){
                //Réinitialisation du token
                $user->setResetToken('');
                //On crypte et associe le mdp saisi à l'utilisateur
                $user->setPassword(
                    $hash->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
                //Enregistrement des données dans la base
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
