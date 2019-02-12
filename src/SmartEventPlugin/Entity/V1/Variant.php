<?php

namespace SmartEventPlugin\Entity\V1;

use SmartEventPlugin\Exception\ChannelNotFoundException;

class Variant
{
    const API_ACTION = 'false';

    private $id;
    private $code;
    private $on_hold;
    private $on_hand;
    private $tracked;
    private $position;
    private $tax_code;
    private $tax_name;
    private $price = [];
    private $original_price = [];
    private $channel;
    private $channels = [];
    
    public function __construct(array $array)
    {
        $this->id = $array['id'];
        $this->code = $array['code'];
        $this->on_hold = $array['on_hold'];
        $this->on_hand = $array['on_hand'];
        $this->tracked = $array['tracked'] == 'true' ? true : false;
        $this->position = $array['position'];
        $this->tax_code = $array['tax_category'][0]['code'];
        $this->tax_name = $array['tax_category'][0]['name'];
        foreach($array['channel_pricings'] as $channel => $pricing){
            $this->price[$channel] = $pricing['price']/100.0;
            $this->original_price[$channel] = ((int)$pricing['original_price'] > 0 ) ? $pricing['original_price']/100.0 : null;
            $this->channels[$channel] = $channel;

            if (!$this->channel){
                $this->setChannel($channel);
            }
        }
    }

    /**
     * @return int|string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     * @throws ChannelNotFoundException
     * @return self
     */
    public function setChannel(String $channel): self
    {
        if (!$this->hasChannel($channel)){
            throw new ChannelNotFoundException(sprintf('Channel %s not exists', $channel));
        }

        $this->channel = $channel;
        return $this;
    }

    /**
     * @return array
     */
    public function getChannels()
    {
        return $this->channels;
    }

    public function hasChannel(String $channel): bool
    {
        return (isset($this->channels[$channel])) ? true : false;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return mixed
     */
    public function getOnHold()
    {
        return $this->on_hold;
    }

    /**
     * @return mixed
     */
    public function getOnHand()
    {
        return $this->on_hand;
    }

    /**
     * @return mixed
     */
    public function getTracked()
    {
        return $this->tracked;
    }

    /**
     * @return mixed
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return mixed
     */
    public function getTaxCode()
    {
        return $this->tax_code;
    }

    /**
     * @return mixed
     */
    public function getTaxName()
    {
        return $this->tax_name;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price[$this->channel];
    }

    /**
     * @return mixed
     */
    public function getOriginalPrice()
    {
        return $this->original_price[$this->channel];
    }

}