<?php

namespace Musonza\Chat\ValueObjects;

use Musonza\Chat\ConfigurationManager;

class Pagination
{
    /**
     * @var array
     */
    private $paginationConfiguration;

    public function __construct()
    {
        $this->paginationConfiguration = ConfigurationManager::paginationDefaultParameters();
    }

    public function getPage(): int
    {
        return $this->paginationConfiguration['page'];
    }

    public function getPerPage(): int
    {
        return $this->paginationConfiguration['perPage'];
    }

    public function getSorting(): string
    {
        return $this->paginationConfiguration['sorting'];
    }

    public function getColumns(): array
    {
        return $this->paginationConfiguration['columns'];
    }

    public function getPageName(): string
    {
        return $this->paginationConfiguration['pageName'];
    }
}

return [
    'page'     => $pagination['page']     ?? 1,
    'perPage'  => $pagination['perPage']  ?? 25,
    'sorting'  => $pagination['sorting']  ?? 'asc',
    'columns'  => $pagination['columns']  ?? ['*'],
    'pageName' => $pagination['pageName'] ?? 'page',
];
