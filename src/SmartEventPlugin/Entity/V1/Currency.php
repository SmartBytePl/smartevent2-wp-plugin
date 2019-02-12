<?php

namespace SmartEventPlugin\Entity\V1;


class Currency
{
    const API_ACTION = 'currencies';

    private $source_currency;
    private $target_currency;
    private $ratio;

    public function __construct(array $array)
    {
        $this->source_currency = $array['source_currency'][0]['code'];
        $this->target_currency = $array['target_currency'][0]['code'];
        $this->ratio = $array['ratio'];
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->source_currency.'-'.$this->target_currency;
    }

    /**
     * @return mixed
     */
    public function getSourceCurrency()
    {
        return $this->source_currency;
    }

    /**
     * @return mixed
     */
    public function getTargetCurrency()
    {
        return $this->target_currency;
    }

    /**
     * @return mixed
     */
    public function getRatio()
    {
        return $this->ratio;
    }
}