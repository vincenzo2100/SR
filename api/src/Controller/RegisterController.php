<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;


use App\Entity\User;
use App\Entity\Teacher;
use App\Entity\Student;

class RegisterController extends AbstractController
{


    #[Route('api/register', name: 'register', methods: 'post')]
    public function index(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        
        $em = $doctrine->getManager();
        $decoded = json_decode($request->getContent());
        $requiredFields = ['email', 'password', 'firstName', 'lastName', 'role'];
        foreach ($requiredFields as $field) {
            if (!property_exists($decoded, $field)) {
                return $this->json(['error' => "Missing required field: $field"], Response::HTTP_BAD_REQUEST);
            }
        }

       
        $email = $decoded->email;
        $plaintextPassword = $decoded->password;
        $firstName = $decoded->firstName;
        $lastName = $decoded->lastName;
        $user = new User();
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($decoded ->role, ['teacher', 'student'])) {
            return $this->json(['error' => 'Invalid role'], Response::HTTP_BAD_REQUEST);
        }
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $this->json(['error' => 'Email already exists'], Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($hashedPassword);
        $user->setEmail($email);
        $user->setUsername($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        if ($decoded->role === 'teacher') {
            $user->setRoles(['ROLE_TEACHER']);
            $teacher = new Teacher();
            $teacher->setPassword($hashedPassword);
        $teacher->setEmail($email);
        $teacher->setUsername($email);
        $teacher->setFirstName($firstName);
        $teacher->setLastName($lastName);
        $teacher->setUser($user);
        $em->persist($teacher);
            
        } elseif ($decoded->role === 'student') {
            $user->setRoles(['ROLE_STUDENT']);
            $student = new Student();
            $student->setPassword($hashedPassword);
        $student->setMail($email);
        $student->setUsername($email);
        $student->setFirstName($firstName);
        $student->setLastName($lastName);
        $student->setUser($user);
        $em->persist($student);
        }
        $em->persist($user);
        $em->flush();
   
        return $this->json(['message' => 'Registered Successfully']);
    }

    #[Route('api/login', name: 'app_login', methods: ['POST'])]
    public function login(#[CurrentUser] $user = null,ManagerRegistry $doctrine, Request $request,UserPasswordHasherInterface $passwordHasher,JWTTokenManagerInterface $jwtManager): Response
    {
        $em = $doctrine->getManager();
        $decoded = json_decode($request->getContent());
        $email = $decoded->email;
        $plaintextPassword = $decoded->password;
        
        // Find user by email
        $userRepository = $em->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $plaintextPassword )) {
            return $this->json(['error' => 'Invalid email or password.'], Response::HTTP_UNAUTHORIZED);
        }

        
        
        // You can proceed with login here
        $token = $jwtManager->create($user);
        return $this->json([
            'message' => 'Logged in successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                // Add any other user data you want to return
            ],
            "token" => $token
        ]);
    }
}
