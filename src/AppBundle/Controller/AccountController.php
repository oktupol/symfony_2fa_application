<?php
/**
 * Created by PhpStorm.
 * User: Sebastian
 * Date: 2016-10-15
 * Time: 17:12
 */

namespace AppBundle\Controller;


use AppBundle\Entity\Auth\TwoFactorAuthentication\BackupCode;
use AppBundle\Entity\Auth\U2FRegistration;
use AppBundle\Entity\Auth\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountController extends Controller
{
    /**
     * @param Request $request
     * @Route("/account/index", name="account_index")
     * @return Response
     */
    public function indexAction(Request $request) : Response
    {
        $user = $this->getUser();
        return $this->render('account/index.html.twig', array(
            'user' => $user
        ));
    }

    /**
     * @param Request $request
     * @Route("/account/set_up_google_authenticator", name="account_set_up_google_authenticator")
     * @return Response
     */
    public function setUpGoogleAuthenticatorAction(Request $request) : Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user->getGoogleAuthenticatorSecret() === null) {
            $secret = $this->get('scheb_two_factor.security.google_authenticator')->generateSecret();
            $user->setGoogleAuthenticatorSecret($secret);
        } else {
            $this->addFlash('notice', 'There is already an authenticator associated to this account. Please remove it first');
            return $this->redirectToRoute('account_index');
        }

        $form = $this->getGoogleAuthenticatorForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $authenticatorCode = $request->get($form->getName())['authenticator_code'];
            if ($this->get('scheb_two_factor.security.google_authenticator')->checkCode($user, $authenticatorCode)) {
                for ($i = count($user->getBackupCodes()), $l = 8; $i < $l; $i++) {
                    $backupCode = (new BackupCode())
                        ->setUser($user)
                        ->setCode(substr(md5(mt_rand()), 0, 12));

                    $user->addBackupCode($backupCode);
                }

                $this->getDoctrine()->getManager()->persist($user);
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('notice', 'Successfully set up Google 2fa');
                return $this->redirectToRoute('account_index');
            } else {
                $this->addFlash('notice', 'Could not verify code');
            }
        }

        return $this->render('account/set_up_google_authenticator.html.twig', array(
            'qr_url' => $this->get('scheb_two_factor.security.google_authenticator')->getUrl($user),
            'form' => $form->createView(),
            'user' => $user
        ));
    }

    private function getGoogleAuthenticatorForm(User $user) : FormInterface
    {
        return $this->createFormBuilder($user)
            ->setMethod('PATCH')
            ->add('googleAuthenticatorSecret', HiddenType::class)
            ->add('authenticator_code', TextType::class, array(
                'mapped' => false
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Add authenticator'
            ))
            ->getForm();
    }

    /**
     * @param Request $request
     * @Route("/account/set_up_u2f_registration", name="account_set_up_u2f_registration")
     * @return Response
     */
    public function setUpU2FRegistrationAction(Request $request) : Response
    {
        $u2fService = $this->get('app.two_factor.u2f');

        /** @var User $user */
        $user = $this->getUser();

        $registration = new U2FRegistration();
        $form = $this->getU2FRegistrationForm($registration);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $request->get($form->getName());
            $challenge = $this->get('session')->get('u2f_register_challenge');

            $u2fService->doRegister($registration, $challenge, json_decode($formData['response_input']));

            $registration->setUser($user);

            $this->getDoctrine()->getManager()->persist($registration);
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('notice', 'Key successfully added');
            
            return $this->redirectToRoute('account_index');
        }

        list($challenge, $signs) = $u2fService->getRegisterData($user);

        $this->get('session')->set('u2f_register_challenge', $challenge);

        return $this->render('account/set_up_u2f_registration.html.twig', array(
            'appId' => $u2fService->getAppId(),
            'challenge' => $challenge,
            'signs' => $signs,
            'user' => $user,
            'form' => $form->createView()
        ));
    }

    private function getU2FRegistrationForm(U2FRegistration $registration) : FormInterface
    {
        return $this->createFormBuilder($registration)
            ->setMethod('PATCH')
            ->add('response_input', HiddenType::class, array(
                'mapped' => false
            ))
            ->getForm();
    }
}