<?php

namespace Musonza\Chat\Traits;

use Illuminate\Database\Eloquent\Model;

trait SetsParticipants
{
    protected $sender;

    protected $recipient;

    protected $participant;

    /**
     * Sets participant.
     *
     *
     * @return $this
     */
    public function setParticipant(Model $participant): self
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * Sets the participant that's sending the message.
     *
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
     *
     * @return $this
     */
    public function to(Model $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }
}
