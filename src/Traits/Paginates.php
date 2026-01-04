<?php

namespace Musonza\Chat\Traits;

trait Paginates
{
    protected $perPage = 25;

    protected $page = 1;

    protected $sorting = 'asc';

    protected $columns = ['*'];

    protected $pageName = 'page';

    protected $deleted = false;

    protected $cursor = null;

    protected $cursorName = 'cursor';

    /**
     * Set the limit.
     *
     * @param  int  $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->perPage = $limit ? $limit : $this->perPage;

        return $this;
    }

    /**
     * Set current page for pagination.
     *
     *
     * @return $this
     */
    public function page(int $page)
    {
        $this->page = $page ? $page : $this->page;

        return $this;
    }

    public function perPage(int $perPage)
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function deleted()
    {
        $this->deleted = true;

        return $this;
    }

    public function setPaginationParams($params)
    {
        foreach ($params as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    public function getPaginationParams()
    {
        return [
            'page'     => $this->page,
            'perPage'  => $this->perPage,
            'sorting'  => $this->sorting,
            'columns'  => $this->columns,
            'pageName' => $this->pageName,
        ];
    }

    public function getCursorPaginationParams()
    {
        return [
            'perPage'    => $this->perPage,
            'sorting'    => $this->sorting,
            'columns'    => $this->columns,
            'cursor'     => $this->cursor,
            'cursorName' => $this->cursorName,
        ];
    }

    /**
     * Set the cursor for cursor-based pagination.
     *
     * @param  string|null  $cursor
     * @return $this
     */
    public function cursor($cursor)
    {
        $this->cursor = $cursor;

        return $this;
    }

    /**
     * Set cursor pagination parameters.
     *
     * @return $this
     */
    public function setCursorPaginationParams($params)
    {
        foreach ($params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }
}
