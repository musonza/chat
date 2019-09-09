<?php

namespace Musonza\Chat\Traits;

use Illuminate\Database\Eloquent\Model;

trait SetsParticipants
{
    protected $from;
    protected $to;
    protected $user;

    /**
     * Sets user.
     *
     * @param Model $user
     *
     * @return $this
     */
    public function for(Model $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Sets user.
     *
     * @param object $user
     *
     * @return $this
     */
    public function setUser(Model $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set Sender.
     *
     * @param Model $from
     *
     * @return $this
     */
    public function from(Model $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function to(Model $recipient): self
    {
        $this->to = $recipient;

        return $this;
    }
}
