<?php

namespace App\Controller;

use App\Entity\Grade;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Course;
use App\Entity\Student;



class StudentController extends AbstractController 
{
    #[Route('api/student/register', name: 'app_student_register', methods:['POST'])]
    public function register(Request $request, ManagerRegistry $registry): JsonResponse
    {
        // Pobierz dane z żądania
        $data = json_decode($request->getContent(), true);

        // Utwórz nowego studenta
        $student = new Student();
        $student->setUsername($data['username']);
        $student->setMail($data['email']);
        $student->setPassword($data['password']);
        $student->setFirstname($data['firstname']);
        $student->setLastname($data['lastname']);
        // Dodaj inne dane studenta, jeśli potrzebne

        // Zapisz studenta do bazy danych
        $entityManager =$registry->getManager(); 
        $entityManager->persist($student);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Student registered successfully']);
    }

    

    #[Route('api/student/enroll-course/{courseId}', name: 'app_student_enroll', methods:['POST'])]
    public function enrollCourse(Request $request, ManagerRegistry $registry, $courseId): Response
    {
        $student = $registry -> getRepository(Student::class)->find(2);
        $entityManager = $registry->getManager();
        $course = $entityManager->getRepository(Course::class)->find($courseId);
        if (!$course) {
            return new Response('Course not found', Response::HTTP_NOT_FOUND);
        }
        if ($course->getStudents()->contains($student)) {
            return new Response('Student is already enrolled in this course', Response::HTTP_BAD_REQUEST);
        }
        if ($course->getMaxCapacity() !== null && $course->getCurrentCapacity() >= $course->getMaxCapacity()) {
            return new Response('Course is full', Response::HTTP_BAD_REQUEST);
        }
        if ($course->getMaxCapacity() !== null && !$course-> isRegisterAvailable()) {
            return new Response('Registration for this course is closed now', Response::HTTP_BAD_REQUEST);
        }
        $course->addStudent($student);
        $course->setCurrentCapacity($course->getCurrentCapacity() + 1);
        $entityManager->flush();
        return new Response('Enrolled in the course successfully', Response::HTTP_OK);
    }

    #[Route('api/student/withdraw-course/{courseId}', name: 'withdraw_course', methods: ['POST'])]
    public function withdrawCourse(Request $request, ManagerRegistry $registry, $courseId): Response
    {
        // Retrieve the logged-in student (you might have your own authentication logic)
        $student =  $registry -> getRepository(Student::class)->find(2);

        // Retrieve the course from the database
        $entityManager = $registry->getManager();
        $course = $entityManager->getRepository(Course::class)->find($courseId);

        // Check if the course exists
        if (!$course) {
            return new Response('Course not found', Response::HTTP_NOT_FOUND);
        }

        // Check if the student is enrolled in the course
        if (!$course->getStudents()->contains($student)) {
            return new Response('Student is not enrolled in this course', Response::HTTP_BAD_REQUEST);
        }

        // Withdraw the student from the course
        $course->removeStudent($student);

        // Decrement the currentCapacity of the course
        $course->setCurrentCapacity($course->getCurrentCapacity() - 1);

        // Persist changes to the database
        $entityManager->flush();

        // Return a success response
        return new Response('Withdrawn from the course successfully', Response::HTTP_OK);
    }
    
    #[Route('api/student/showGrade/{courseId}', name: 'showGrade', methods: ['GET'])]
    public function showGrade(Request $request, ManagerRegistry $registry,$courseId) : JsonResponse
    {
        $entityManager = $registry->getManager();
        $student = $entityManager->getRepository(Student::class)->find(2);
        if (!$student) {
            return new JsonResponse(['message' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }
        $grades = [];
        foreach ($student->getGrades() as $grade) {
            $grades[] = [
                'course' => $grade->getCourse()->getName(),
                'grade' => $grade->getValue()
            ];
        }

        return new JsonResponse(['grades' => $grades], Response::HTTP_OK);
    }


}
