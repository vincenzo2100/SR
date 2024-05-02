<?php

namespace App\Controller;


use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\Course;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\Grade;

class TeacherController extends AbstractController
{
    #[Route('api/teacher', name: 'app_teacher', methods: ['get'])]
    public function getCourses(ManagerRegistry $registry): JsonResponse
    {
        $courses = $registry->getRepository(Course::class)->findAll();
        $data = [];
        foreach ($courses as $course) {
            $data[] = [
                'id'=> $course->getId(),
                'name'=> $course->getName(),
                'currentCapacity' => $course->getCurrentCapacity(),
                'maxCapacity' => $course->getMaxCapacity(),
                'teacher'=> $course->getTeacher()->getId() . " " . $course->getTeacher()->getFirstName() . " " . $course->getTeacher()->getLastName(),
                ];
       
    }
        return $this->json($data);
    }
    #[Route('api/teacher/new', name: 'app_teacher_new', methods: ['post'])]
    public function createCourse(#[CurrentUser] $user = null,Request $request, ManagerRegistry $registry): Response
    {
        $entityManager = $registry->getManager();
        $teacher = $entityManager->getRepository(Teacher::class)->find($user->getTeacher());
        $requestData = json_decode($request->getContent(), true);
        $course = new Course();
        $course->setTeacher($teacher);
        $course->setName($requestData['name']);
        $course->setMaxCapacity($requestData['maxCapacity']);
        $course->setCurrentCapacity(0);
        $course->setRegisterAvailable(true);
        
        $entityManager->persist($course);
        $entityManager->flush();
        return new Response('Course created successfully', Response::HTTP_CREATED);
    }

    #[Route('api/teacher/update/{id}', name: 'app_teacher_update', methods: ['PUT'])]
    public function updateCourse(Request $request, ManagerRegistry $registry,$id): Response
    {
        $requestData = json_decode($request->getContent(), true);
        $entityManager = $registry->getManager();
        $course = $entityManager->getRepository(Course::class)->find($id);
        if (!$course) {
            return new Response('Course not found', Response::HTTP_NOT_FOUND);
        }

        if (isset($requestData['name'])) {
            $course->setName($requestData['name']);
        }
        if (isset($requestData['maxCapacity'])) {
            $course->setMaxCapacity($requestData['maxCapacity']);
        }
        $entityManager->flush();
        return new Response('Course updated successfully', Response::HTTP_OK);
    }

    #[Route('api/teacher/delete/{id}', name:'app_teacher_delete', methods: ['delete'])]
    public function deleteCourse(Request $request, ManagerRegistry $registry,$id): Response
    {
        $entityManager = $registry->getManager();
        $course = $entityManager->getRepository(Course::class)->find($id);

        if (!$course) {
            return new Response('Course not found', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($course);
        $entityManager->flush();

        return new Response('Course deleted successfully', Response::HTTP_OK);
    }

    #[Route('api/teacher/block-registration/{id}', name: 'app_toggle_registration', methods: ['PUT'])]
    public function blockRegistration(ManagerRegistry $registry, $id): Response
    {
        // Retrieve the course from the database
        $entityManager = $registry->getManager();
        $course = $entityManager->getRepository(Course::class)->find($id);

        // Check if the course exists
        if (!$course) {
            return new Response('Course not found', Response::HTTP_NOT_FOUND);
        }

        // Block registration by setting maxCapacity to currentCapacity
        $course->setRegisterAvailable(false);

        // Persist changes to the database
        $entityManager->flush();

        // Return a success response
        return new Response('Registration blocked successfully', Response::HTTP_OK);
    }

    #[Route('api/teacher/unblock-registration/{id}', name: 'unblock_registration', methods: ['PUT'])]
    public function unblockRegistration(ManagerRegistry $registry, $id): Response
    {
        // Retrieve the course from the database
        $entityManager = $registry->getManager();
        $course = $entityManager->getRepository(Course::class)->find($id);

        // Check if the course exists
        if (!$course) {
            return new Response('Course not found', Response::HTTP_NOT_FOUND);
        }

        // Unblock registration by setting maxCapacity back to its original value
        $course->setRegisterAvailable(true);

        // Persist changes to the database
        $entityManager->flush();

        // Return a success response
        return new Response('Registration unblocked successfully', Response::HTTP_OK);
    }

    #[Route('api/teacher/getStudents/{id}', name: 'app_teacher_getStudents', methods: ['get'])]
    public function getStudents(ManagerRegistry $registry,$id): JsonResponse
    {
        $courses = $registry->getRepository(Course::class)->findAll();
        $data = [];
        foreach ($courses as $course) {
            $students = [];
            foreach ($course->getStudents() as $student) {
                $students[] = [
                    'id' => $student->getId(),
                    'firstName' => $student->getFirstName(),
                    'lastName' => $student->getLastName(),
                    // Add more student information here if needed
                ];
            }
            $data[] = [
                'students' => $students,
            ];
        }
        return $this->json($data);
    }

    #[Route('api/teacher/course/{courseId}/student/{studentId}/grade', name: 'app_teacher_giveGrade', methods: ['POST'])]
    public function giveGrade(Request $request, ManagerRegistry $registry, $studentId, $courseId): Response
    {
        $data = json_decode($request->getContent(), true);
        $entityManager = $registry->getManager();
        $student = $entityManager->getRepository(Student::class)->find($studentId);
        $course = $entityManager->getRepository(Course::class)->find($courseId);

        if (!$student || !$course) {
            return new JsonResponse(['message' => 'Student or course not found'], Response::HTTP_NOT_FOUND);
        }

        if (!isset($data['grade']) || !is_numeric($data['grade'])) {
            return new JsonResponse(['message' => 'Invalid grade'], Response::HTTP_BAD_REQUEST);
        }

        if (!$student->getCourse()->contains($course)) {
            return new JsonResponse(['message' => 'Student is not enrolled in this course'], Response::HTTP_BAD_REQUEST);
        }

        $grade = $entityManager->getRepository(Grade::class)->findOneBy(['student' => $student, 'course' => $course]);
        if (!$grade) {
            $grade = new Grade();
            $grade->setStudent($student);
            $grade->setCourse($course);
        }
        $grade->setValue($data['grade']);
        $entityManager->persist($grade);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Grade assigned successfully'], Response::HTTP_OK);
    }
}
