<?php
/**
 * Created by PhpStorm.
 * User: Sebastian
 * Date: 2016-10-15
 * Time: 21:08
 */

namespace AppBundle\Security\TwoFactor\Provider;


use AppBundle\Entity\Auth\U2FRegistration;
use AppBundle\Entity\Auth\User;
use AppBundle\Security\TwoFactor\U2FService;
use Doctrine\ORM\EntityManager;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use u2flib_server\SignRequest;

class U2FProvider implements TwoFactorProviderInterface
{
    /** @var TwigEngine */
    private $twigEngine;

    /** @var U2FService */
    private $u2fservice;

    /** @var FormFactory */
    private $formFactory;

    /** @var EntityManager */
    private $entityManager;

    public function __construct(TwigEngine $engine, U2FService $u2fService, FormFactory $formFactory, EntityManager $entityManager)
    {
        $this->twigEngine = $engine;
        $this->u2fservice = $u2fService;
        $this->formFactory = $formFactory;
        $this->entityManager = $entityManager;
    }

    public function beginAuthentication(AuthenticationContextInterface $context)
    {
        /** @var User $user */
        $user = $context->getUser();

        if (count($user->getU2fRegistrations()) <= 0) {
            return false;
        }

        $context->getSession()->set('u2f_login_challenge', $this->u2fservice->getAuthenticateData($user));

        return true;
    }

    public function requestAuthenticationCode(AuthenticationContextInterface $context)
    {
        /** @var User $user */
        $user = $context->getUser();
        $request = $context->getRequest();

        /** @var SignRequest $challenge */
        $challenge = $context->getSession()->get('u2f_login_challenge');

        $registration = new U2FRegistration();
        $form = $this->getU2FRegistrationForm($registration);

        $context->setAuthenticated(true);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $request->get($form->getName());
            
            $this->u2fservice->doAuthenticate($user, $registration, $challenge, json_decode($formData['response_input']));

            $authenticated = false;
            /** @var U2FRegistration $u2fRegistration */
            foreach ($user->getU2fRegistrations() as $u2fRegistration) {
                if ($u2fRegistration->getKeyHandle() === $registration->getKeyHandle()) {
                    $authenticated = true;
                    $u2fRegistration->setCounter($registration->getCounter());
                    $this->entityManager->persist($u2fRegistration);
                    $this->entityManager->flush();
                    $context->setAuthenticated(true);
                }
            }

            if (!$authenticated) {
                $context->getSession()->getBag('flash')->set('notice', 'Authentication unsuccessful');
            }
        }

        return $this->twigEngine->renderResponse('security/u2f_2fa.html.twig', array(
            'appId' => $this->u2fservice->getAppId(),
            'challenge' => $challenge,
            'signs' => $this->u2fservice->getRegisteredKeys($user),
            'form' => $form->createView()
        ));
    }

    private function getU2FRegistrationForm(U2FRegistration $registration) : FormInterface
    {
        return $this->formFactory->createBuilder(FormType::class, $registration)
            ->setMethod('PATCH')
            ->add('response_input', HiddenType::class, array(
                'mapped' => false
            ))
            ->getForm();
    }
}