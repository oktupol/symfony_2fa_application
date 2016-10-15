<?php
/**
 * Created by PhpStorm.
 * User: Sebastian
 * Date: 2016-10-15
 * Time: 17:12
 */

namespace AppBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountController extends Controller
{
    /**
     * @param Request $request
     * @Route("/account/index", name="account_index")
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $user = $this->getUser();
        return $this->render('account/index.html.twig', array(
            'user' => $user
        ));
    }
}