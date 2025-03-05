<?php
namespace App\Service;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ClubRepository;
use App\Entity\Club;

class PaginatorService
{
    private PaginatorInterface $paginator;

    public function __construct(PaginatorInterface $paginator)
    {
        $this->paginator = $paginator;
    }

    public function paginate($query, Request $request, int $limit = 10)
    {
        return $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $limit
        );
    }
}