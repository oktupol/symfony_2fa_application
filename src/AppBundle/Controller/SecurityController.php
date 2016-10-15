<?php
/**
 * Created by PhpStorm.
 * User: Sebastian
 * Date: 2016-10-15
 * Time: 16:19
 */

namespace AppBundle\Controller;


use AppBundle\Entity\Auth\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityController extends Controller
{
    /**
     * @param Request $request
     * @Route("/security/login", name="security_login")
     * @return Response
     */
    public function loginAction(Request $request)
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        $lastError = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', array(
            'last_error' => $lastError,
            'last_username' => $lastUsername,
        ));
    }

    /**
     * @param Request $request
     * @Route("/security/register", name="security_register")
     * @return Response
     */
    public function registerAction(Request $request)
    {
        $user = new User();

        $form = $this->getRegisterForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $encodedPassword = $this->get('security.password_encoder')->encodePassword($user, $user->getPassword());
            $user->setPassword($encodedPassword);

            $role = $this->getDoctrine()->getManager()->getRepository('AppBundle:Auth\Role')->findOneBy(array(
                'name' => 'ROLE_USER'
            ));

            $user->addRole($role);

            try {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('notice', 'Registration complete. You can now log in.');
                return $this->redirectToRoute('security_login');
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('notice', 'This username is already taken. Please choose a different one.');
            }
        }

        return $this->render('security/register.html.twig', array(
            'form' => $form->createView()
        ));
    }

    private function getRegisterForm(User $user) : FormInterface
    {
        return $this->createFormBuilder($user)
            ->add('username', TextType::class, array(
                'label' => 'Username'
            ))
            ->add('password', PasswordType::class, array(
                'label' => 'Password'
            ))
            ->add('register', SubmitType::class, array(
                'label' => 'Register'
            ))
            ->getForm();
    }
}