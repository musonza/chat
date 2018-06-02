<?php

namespace Musonza\Chat\Traits;

trait IdentifiesUsers
{
    protected $from;
    protected $to;

    /**
     * Sets user.
     *
     * @param object $user
     *
     * @return $this
     */
    public function for($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set Sender.
     *
     * @param int $from
     *
     * @return $this
     */
    public function from($from)
    {
        $this->from = is_object($from) ? $from->id : $from;

        return $this;
    }

    public function to($recipient)
    {
        $this->to = $recipient;

        return $this;
    }
}