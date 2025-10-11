<?php

namespace App\Controller;

use App\Entity\Expense;
use App\Repository\ExpenseRepository;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/expenses')]
class ExpenseController extends AbstractController
{
    #[Route('/', name: 'app_expense_index', methods: ['GET'])]
    public function index(ExpenseRepository $expenseRepository, Request $request): Response
    {
        $category = $request->query->get('category', '');
        $propertyId = $request->query->get('property', '');
        $month = $request->query->get('month', date('Y-m'));

        if ($category) {
            $expenses = $expenseRepository->findByCategory($category);
        } elseif ($propertyId) {
            $expenses = $expenseRepository->findByProperty($propertyId);
        } elseif ($month) {
            $startDate = new \DateTime($month . '-01');
            $endDate = new \DateTime($month . '-01');
            $endDate->modify('last day of this month');
            $expenses = $expenseRepository->findByDateRange($startDate, $endDate);
        } else {
            $expenses = $expenseRepository->findAllWithProperties();
        }

        return $this->render('expense/index.html.twig', [
            'expenses' => $expenses,
            'category' => $category,
            'propertyId' => $propertyId,
            'month' => $month,
        ]);
    }

    #[Route('/new', name: 'app_expense_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        PropertyRepository $propertyRepository
    ): Response {
        $expense = new Expense();
        
        if ($request->isMethod('POST')) {
            $propertyId = $request->get('property_id');
            
            if ($propertyId) {
                $property = $propertyRepository->find($propertyId);
                $expense->setProperty($property);
            }

            $expense->setDescription($request->get('description'));
            $expense->setAmount($request->get('amount'));
            $expense->setCategory($request->get('category'));
            $expense->setExpenseDate(new \DateTime($request->get('expense_date')));
            $expense->setSupplier($request->get('supplier'));
            $expense->setInvoiceNumber($request->get('invoice_number'));
            $expense->setNotes($request->get('notes'));

            $entityManager->persist($expense);
            $entityManager->flush();

            $this->addFlash('success', 'Dépense créée avec succès !');
            return $this->redirectToRoute('app_expense_index');
        }

        return $this->render('expense/new.html.twig', [
            'expense' => $expense,
            'properties' => $propertyRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_expense_show', methods: ['GET'])]
    public function show(Expense $expense): Response
    {
        return $this->render('expense/show.html.twig', [
            'expense' => $expense,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_expense_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Expense $expense, 
        EntityManagerInterface $entityManager,
        PropertyRepository $propertyRepository
    ): Response {
        if ($request->isMethod('POST')) {
            $propertyId = $request->get('property_id');
            
            if ($propertyId) {
                $property = $propertyRepository->find($propertyId);
                $expense->setProperty($property);
            } else {
                $expense->setProperty(null);
            }

            $expense->setDescription($request->get('description'));
            $expense->setAmount($request->get('amount'));
            $expense->setCategory($request->get('category'));
            $expense->setExpenseDate(new \DateTime($request->get('expense_date')));
            $expense->setSupplier($request->get('supplier'));
            $expense->setInvoiceNumber($request->get('invoice_number'));
            $expense->setNotes($request->get('notes'));
            $expense->setUpdatedAt(new \DateTime());

            $entityManager->flush();

            $this->addFlash('success', 'Dépense modifiée avec succès !');
            return $this->redirectToRoute('app_expense_show', ['id' => $expense->getId()]);
        }

        return $this->render('expense/edit.html.twig', [
            'expense' => $expense,
            'properties' => $propertyRepository->findAll(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_expense_delete', methods: ['POST'])]
    public function delete(Request $request, Expense $expense, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$expense->getId(), $request->request->get('_token'))) {
            $entityManager->remove($expense);
            $entityManager->flush();
            $this->addFlash('success', 'Dépense supprimée avec succès !');
        }

        return $this->redirectToRoute('app_expense_index');
    }

    #[Route('/statistics', name: 'app_expense_statistics', methods: ['GET'])]
    public function statistics(ExpenseRepository $expenseRepository): Response
    {
        $statistics = $expenseRepository->getStatistics();
        $byCategory = $expenseRepository->getStatisticsByCategory();
        $byProperty = $expenseRepository->getStatisticsByProperty();

        return $this->render('expense/statistics.html.twig', [
            'statistics' => $statistics,
            'byCategory' => $byCategory,
            'byProperty' => $byProperty,
        ]);
    }
}