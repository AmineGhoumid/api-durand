<?php

namespace App\Controller;

use App\Entity\Machine;
use App\Repository\MachineRepository;
use App\Repository\UserRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

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
            $this->checkLinkedToken($tokenUsername, $user->getUsername());

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
     * @Route("/api/deleteMachine", name="deleteMachine")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param MachineRepository $machineRepository
     * @return Response
     */
    public function deleteMachine(Request $request, UserRepository $userRepository,MachineRepository $machineRepository) :Response
    {
        $em = $this->getDoctrine()->getManager();

        // parse request content
        $request = $this->parseRequest($request);

        try {
            // post data and verification
            $post = array(
                'machine_name' => $request->request->get('machine_name')
            );
            $this->checkInputInfo("unused", $post['machine_name'], "unused");

            // machine existence verification
            $machine = $machineRepository->findOneByMachineName($post['machine_name']);
            $this->checkMachineExistence($machine);

            // token match verification
            $tokenUsername = $this->tokenLinkedUser($request->headers->get('Authorization'))->username;
            $this->checkLinkedToken($tokenUsername, $userRepository->findOneById($machine->getUserId())->getUsername());

            // machine suppression
            $em->remove($machine);
            $em->flush();

        }catch (Exception $e){
            $post['return']=["status" => "aborted", "message" => $e->getMessage()];
            return new Response(json_encode($post, 201));
        }
        $post['return']=["status" => "deleted", "message"=>"This machines was successfully deleted"];
        return new Response(json_encode($post, 201));
    }

    /**
     * @Route("/api/editMachine", name="editMachine")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param MachineRepository $machineRepository
     * @return Response
     */
    public function editMachine(Request $request, UserRepository $userRepository,MachineRepository $machineRepository) :Response
    {
        $em = $this->getDoctrine()->getManager();

        // parse request content
        $request = $this->parseRequest($request);

        try {
            // post data and verification
            $post = array(
                'machine_name' => $request->request->get('machine_name'),
                'new_machine_name' => $request->request->get('new_machine_name'),
                'new_machine_description' => $request->request->get('new_machine_description')
            );

            // machine existence verification
            $machine = $machineRepository->findOneByMachineName($post['machine_name']);
            $this->checkMachineExistence($machine);

            // token match verification
            $tokenUsername = $this->tokenLinkedUser($request->headers->get('Authorization'))->username;
            $this->checkLinkedToken($tokenUsername, $userRepository->findOneById($machine->getUserId())->getUsername());

            // machine edition
            if (!empty($post['new_machine_name']))
                $machine->setName($post['new_machine_name']);

            if (!empty($post['new_machine_description']))
                $machine->setName($post['new_machine_description']);

            $em->flush();

        }catch (Exception $e){
            $post['return']=["status" => "aborted", "message" => $e->getMessage()];
            return new Response(json_encode($post, 201));
        }
        $post['return']=["status" => "edited", "message"=>"This machines was successfully edited"];
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

    /**
     * @param $machine
     * @throws Exception
     */
    private function checkMachineExistence($machine){
        if (empty($machine))
            throw new Exception('Selected machine doesn\'t exists!');
    }

    /**
     * @param $tokenUsername
     * @param $postUsername
     * @throws Exception
     */
    private function checkLinkedToken($tokenUsername, $postUsername){
        if($tokenUsername != $postUsername)
            throw new Exception('You cannot access this user\'s machines. Your token doesn\'t match to his account.');
    }

    /**
     * @param $request
     * @return mixed
     */
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
