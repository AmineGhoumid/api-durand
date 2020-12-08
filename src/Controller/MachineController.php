<?php

namespace App\Controller;

use App\Entity\Machine;
use App\Repository\MachineRepository;
use App\Repository\UserRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $request->request->replace(is_array($data) ? $data : array());
        }

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

}
