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
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\Course;
use App\Entity\Student;



class StudentController extends AbstractController 
{
    #[Route('api/student/showCourses', name: 'app_student_show', methods:['GET'])]
    public function showCourses(ManagerRegistry $registry) : JsonResponse
    {
        $courses = $registry->getRepository(Course::class)->findAll();
        $data = [];
        foreach ($courses as $course) {
            $data[] = [
                'id'=> $course->getId(),
                'name'=> $course->getName(),
                'teacher'=> $course->getTeacher()->getFirstName() . " " . $course -> getTeacher()->getLastName(),
                'howManyPeopleAreSigned' => $course->getCurrentCapacity() . " / " . $course->getMaxCapacity(),
                'isRegisterAvailable' => $course->isRegisterAvailable()
                ];
       
    }
        return $this->json($data);
    }


    #[Route('api/student/enroll-course/{courseId}', name: 'app_student_enroll', methods:['POST'])]
    public function enrollCourse(#[CurrentUser] $user = null,Request $request, ManagerRegistry $registry, $courseId): Response
    {
        $entityManager = $registry->getManager();
        $student = $entityManager->getRepository(Student::class)->find($user->getStudent());
        
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
    public function withdrawCourse(#[CurrentUser] $user = null,Request $request, ManagerRegistry $registry, $courseId): Response
    {
        $entityManager = $registry->getManager();
        $student = $entityManager->getRepository(Student::class)->find($user->getStudent());

        
        
        $course = $entityManager->getRepository(Course::class)->find($courseId);

       
        if (!$course) {
            return new Response('Course not found', Response::HTTP_NOT_FOUND);
        }

       
        if (!$course->getStudents()->contains($student)) {
            return new Response('Student is not enrolled in this course', Response::HTTP_BAD_REQUEST);
        }

        
        $course->removeStudent($student);

        $course->setCurrentCapacity($course->getCurrentCapacity() - 1);

        
        $entityManager->flush();

       
        return new Response('Withdrawn from the course successfully', Response::HTTP_OK);
    }
    
    #[Route('api/student/showGrade/{courseId}', name: 'showGrade', methods: ['GET'])]
    public function showGrade(#[CurrentUser] $user = null,Request $request, ManagerRegistry $registry,$courseId) : JsonResponse
    {
        $entityManager = $registry->getManager();
        $student = $entityManager->getRepository(Student::class)->find($user->getStudent());
        if (!$student) {
            return new JsonResponse(['message' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }
        $grades = [];
        foreach ($student->getGrades() as $grade) {
            if ($grade->getCourse()->getId() == $courseId) {
                $grades[] = [
                    'course' => $grade->getCourse()->getName(),
                    'grade' => $grade->getValue()
                ];
            }
        }

        return new JsonResponse(['grades' => $grades], Response::HTTP_OK);
    }


}
