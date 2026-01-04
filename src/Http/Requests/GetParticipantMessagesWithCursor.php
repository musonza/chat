<?php

namespace Musonza\Chat\Http\Requests;

use Musonza\Chat\ValueObjects\Pagination;

class GetParticipantMessagesWithCursor extends BaseRequest
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
            'perPage'          => 'integer',
            'sorting'          => 'string|in:asc,desc',
            'columns'          => 'array',
            'cursor'           => 'string|nullable',
            'cursorName'       => 'string',
        ];
    }

    public function getCursorPaginationParams()
    {
        return [
            'perPage'    => $this->perPage    ?? $this->pagination->getPerPage(),
            'sorting'    => $this->sorting    ?? $this->pagination->getSorting(),
            'columns'    => $this->columns    ?? $this->pagination->getColumns(),
            'cursor'     => $this->cursor     ?? null,
            'cursorName' => $this->cursorName ?? 'cursor',
        ];
    }
}
