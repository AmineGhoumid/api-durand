<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class AuthController extends AbstractController
{
    /**
     * @Route("/api/register", name="register")
     * @param Request $request
     * @param UserRepository $userRepository
     * @return Response
     * used to register the user in the database
     */
    public function register(Request $request, UserRepository $userRepository): Response
    {
        $em = $this->getDoctrine()->getManager();

        // parse request content
        $request = $this->parseRequest($request);

        try {
            // post data and verification
            $post = array(
                'username' => $request->request->get('username'),
                'password' => $request->request->get('password'),
            );
            $this->checkInputInfo($post['username'], $post['password']);

            // user existence verification
            $user = $userRepository->findOneByUsername($post['username']);
            $this->checkUserExistence($user);

            // user creation
            $user = new User($post['username']);
            $user->setPassword(crypt($post['password'], $user->getSalt()));
            $em->persist($user);
            $em->flush();

        }catch (Exception $e){
            $post['return']=["status" => "aborted", "message" => $e->getMessage()];
            return new Response(json_encode($post, 201));
        }

        $post['return']=["status" => "created", "message"=>"Account successfully created !"];
        return new Response(json_encode($post, 201));
    }

    /**
     * @param $username
     * @param $password
     * @throws Exception
     * used to check that every user input are given
     */
    private function checkInputInfo($username, $password){

        if (empty($username))
            throw new Exception('Please specify a username.');

        if (empty($password))
            throw new Exception('Please specify a password.');
    }

    /**
     * @param $user
     * @throws Exception
     * used to check if the user doesn't already exists
     */
    private function checkUserExistence($user){
        if (!empty($user))
            throw new Exception('User already exists !');
    }

    /**
     * @param $request
     * @return mixed
     * used to parse the incoming request
     * //TODO put it in a middleware
     */
    private function parseRequest($request){
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $request->request->replace(is_array($data) ? $data : array());
        }
        return $request;
    }
}
