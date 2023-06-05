<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class UserController extends AbstractController
{
    final function verifyString(string $string) {

        // Check if the string is longer than 8 characters
        if (strlen($string) < 8) {
            return false;
        }

        // Check if the string contains an uppercase letter
        if (!preg_match('/[A-Z]/', $string)) {
            return false;
        }

        // Check if the string contains a number
        if (!preg_match('/\d/', $string)) {
            return false;
        }

        // The string passed all the checks, so it is valid
        return true;
    }

    #[Route('/api/register', name: 'register',methods: ['POST'])]
    public function register(Request $request, SerializerInterface $serializer,EntityManagerInterface $entityManager,UserRepository $userRepository): Response
    {
        $user= $serializer->deserialize($request->getContent(), User::class, 'json');
        $userEmail = $user->getEmail();
        if($userRepository->findOneBy(["email"=>$userEmail])==null){
            $password = $user->getPassword();
            if($this->verifyString($user->getPassword())){
                $entityManager->persist($user);
                $entityManager->flush();
                return  new Response("Registration done with success",Response::HTTP_CREATED,[]);
            }else{
                return  new Response("Non secure Password: The password must be contains one Uppercase letter , number and must be greater than 8 characters.",Response::HTTP_NOT_FOUND,[]);
            }
        }else{
            return  new Response("This Email is already registered",Response::HTTP_NOT_FOUND,[]);
        }
    }
    #[Route('/api/login', name: 'login',methods: ['POST'])]
    public function login(Request $request, SerializerInterface $serializer,EntityManagerInterface $entityManager,UserRepository $userRepository, UrlGeneratorInterface $urlGenerator): Response
    {
        $user= $serializer->deserialize($request->getContent(), User::class, 'json');
        $userEmail = $user->getEmail();
        $userPassword = $user->getPassword();
        if($userRepository->findOneBy(["email"=>$userEmail])!=null){
            $currentUser = $userRepository->findOneBy(["email"=>$userEmail]);
            if ($currentUser->getPassword()==$userPassword){
                $jsonCurrentUser = $serializer->serialize($currentUser,'json');
                $location = $urlGenerator->generate('infoUser', ['id' => $currentUser->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                return  new JsonResponse($jsonCurrentUser,Response::HTTP_OK,["location"=>$location],true);
            }else{
                return  new Response("Invalid credentials",Response::HTTP_NOT_FOUND,[]);
            }
        }else{
            return  new Response("This Email is not registered",Response::HTTP_NOT_FOUND,[]);
        }
    }
}
