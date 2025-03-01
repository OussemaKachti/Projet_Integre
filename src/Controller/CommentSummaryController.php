<?php

namespace App\Controller;

use App\Service\OpenAIClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\SondageRepository;

class CommentSummaryController extends AbstractController
{
    private OpenAIClient $openAIClient;

    public function __construct(OpenAIClient $openAIClient)
    {
        $this->openAIClient = $openAIClient;
    }

    #[Route('/api/summarize-comments/{id}', name: 'summarize_comments', methods: ['GET'])]
    public function summarize(int $id, SondageRepository $sondageRepository, OpenAIClient $openAIClient): JsonResponse
    {
        $sondage = $sondageRepository->find($id);
    
        if (!$sondage) {
            return $this->json(['error' => 'Sondage non trouvé.'], 404);
        }
    
        $comments = $sondage->getCommentaires()->map(fn($comment) => $comment->getContenuComment())->toArray();
    
        if (empty($comments)) {
            return $this->json(['summary' => 'Pas encore de commentaires pour ce sondage.']);
        }
    
        $summary = $openAIClient->summarizeComments($comments);
    
        return $this->json(['summary' => $summary]);
    }
    
}