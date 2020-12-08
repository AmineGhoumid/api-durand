<?php

namespace App\Controller;

use App\Entity\Machine;
use App\Repository\MachineRepository;
use App\Repository\UserRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MachineController extends AbstractController
{
    /**
     * @Route("/api/createMachine", name="createMachine")
     * @param Request $request
     * @param UserRepository $userRepository
     * @return Response
     */
    public function createMachine(Request $request, UserRepository $userRepository): Response
    {
        $em = $this->getDoctrine()->getManager();

        // parse request content
        $request = $this->parseRequest($request);

        try {
            $post = array(
                'username' => $request->request->get('username'),
                'machinename' => $request->request->get('machinename'),
                'description' => $request->request->get('description')
            );
            $this->checkInputInfo($post['username'], $post['machinename'], $post['description']);

            $user = $userRepository->findOneByUsername($post['username']);
            $this->checkUserExistence($user);

            $machine = new Machine($post['machinename'], $post['description'], $user->getId());
            $em->persist($machine);
            $em->flush();

        }catch (Exception $e){
            $post['return']=["status" => "aborted", "message" => $e->getMessage()];
            return new Response(json_encode($post, 201));
        }

        $post['return']=["status" => "created", "message"=>"This machine was successfully created"];
        return new Response(json_encode($post, 201));
    }

    /**
     * @Route("/api/getUserMachine", name="getUserMachine")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param MachineRepository $machineRepository
     * @return Response
     */
    public function getUserMachines(Request $request, UserRepository $userRepository,MachineRepository $machineRepository) :Response
    {
        // parse request content
        $request = $this->parseRequest($request);

        try {
            // post data and verification
            $post = array(
                'username' => $request->request->get('username')
            );
            $this->checkInputInfo($post['username'], "unused", "unused");

            // user existence verification
            $user = $userRepository->findOneByUsername($post['username']);
            $this->checkUserExistence($user);

            // token match verification
            $tokenUsername = $this->tokenLinkedUser($request->headers->get('Authorization'))->username;
            if($tokenUsername != $post['username']){
                $post['return']=["status" => "aborted", "message"=>"You cannot access this user's machines. Your token doesn't match to his account."];
                return new Response(json_encode($post, 201));
            }

            // machine list gathering
            $machines = $machineRepository->getAllByUserId($user->getId());
            $post['machines'] = $machines;


        }catch (Exception $e){
            $post['return']=["status" => "aborted", "message" => $e->getMessage()];
            return new Response(json_encode($post, 201));
        }
        $post['return']=["status" => "gathered", "message"=>"This machines were successfully gathered"];
        return new Response(json_encode($post, 201));
    }

    /**
     * @param $username
     * @param $machinename
     * @param $description
     * @throws Exception
     */
    private function checkInputInfo($username, $machinename, $description){

        if (empty($username))
            throw new Exception('Please specify a username.');

        if (empty($machinename))
            throw new Exception('Please specify a machine name.');

        if (empty($description))
            throw new Exception('Please specify a machine description.');
    }

    /**
     * @param $user
     * @throws Exception
     */
    private function checkUserExistence($user){
        if (empty($user))
            throw new Exception('Selected user doesn\'t exists!');
    }

    private function parseRequest($request){
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $request->request->replace(is_array($data) ? $data : array());
        }
        return $request;
    }

    /**
     * @param $authHeader
     * @throws Exception
     */
    private function tokenLinkedUser($authHeader){
        // get only the token by getting rid of "BEARER"
        $token = explode(" ", $authHeader);

        // explode token to access info
        $tokenParts = explode(".", $token[1]);

        // jwt are base64 encoded, decode to access further info
        $tokenPayload = base64_decode($tokenParts[1]);

        return json_decode($tokenPayload);
    }
}
