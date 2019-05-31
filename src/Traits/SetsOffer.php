<?php

namespace Musonza\Chat\Traits;

trait SetsOffer
{
    protected $from;
    protected $to;
    protected $user;

    /**
     * Sets offer.
     *
     * @param object $offer
     *
     * @return $this
     */
    public function offer($offer)
    {
        $this->offer = $offer;

        return $this;
    }

    /**
     * Sets offer.
     *
     * @param object $offer
     *
     * @return $this
     */
    public function setOffer($offer)
    {
     $this->offer = $offer;

        return $this;
    }

}
