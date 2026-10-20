<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Affiche le contrat OpenAPI de référence (source de vérité) dans Swagger UI,
 * pour le comparer à la documentation générée sur /api/docs.
 */
final class ContractController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/../../specs/001-task-api/contracts/openapi.yaml')]
        private readonly string $contractPath,
    ) {
    }

    #[Route('/api/contract', name: 'api_contract', methods: ['GET'])]
    public function contractPage(): Response
    {
        return $this->render('contract.html.twig');
    }

    #[Route('/api/contract.yaml', name: 'api_contract_file', methods: ['GET'])]
    public function contractFile(): Response
    {
        // Le dossier specs/ n'existe que dans le dépôt, pas forcément dans une image déployée
        if (!is_file($this->contractPath)) {
            throw new NotFoundHttpException('Contrat OpenAPI introuvable.');
        }

        return new Response((string) file_get_contents($this->contractPath), headers: ['Content-Type' => 'application/yaml']);
    }
}
