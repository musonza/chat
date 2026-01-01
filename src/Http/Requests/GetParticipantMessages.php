<?php

namespace Musonza\Chat\Http\Requests;

use Musonza\Chat\ValueObjects\Pagination;

class GetParticipantMessages extends BaseRequest
{
    /**
     * @var Pagination
     */
    private $pagination;

    public function __construct(Pagination $pagination)
    {
        parent::__construct();
        $this->pagination = $pagination;
    }

    public function authorized()
    {
        return true;
    }

    public function rules()
    {
        return [
            'participant_id'   => 'required',
            'participant_type' => 'required',
            'page'             => 'integer',
            'perPage'          => 'integer',
            'sorting'          => 'string|in:asc,desc',
            'columns'          => 'array',
            'pageName'         => 'string',
        ];
    }

    public function getPaginationParams()
    {
        return [
            'page'     => $this->page     ?? $this->pagination->getPage(),
            'perPage'  => $this->perPage  ?? $this->pagination->getPerPage(),
            'sorting'  => $this->sorting  ?? $this->pagination->getSorting(),
            'columns'  => $this->columns  ?? $this->pagination->getColumns(),
            'pageName' => $this->pageName ?? $this->pagination->getPageName(),
        ];
    }
}
