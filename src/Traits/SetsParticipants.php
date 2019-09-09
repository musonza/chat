<?php

namespace Musonza\Chat\Traits;

use Illuminate\Database\Eloquent\Model;

trait SetsParticipants
{
    protected $sender;
    protected $recipient;
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
     * @param Model $user
     *
     * @return $this
     */
    public function setParticipant(Model $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Sets the participant that's sending the message.
     *
     * @param Model $sender
     *
     * @return $this
     */
    public function from(Model $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Sets the participant to receive the message.
     *
     * @param Model $recipient
     *
     * @return $this
     */
    public function to(Model $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }
}
