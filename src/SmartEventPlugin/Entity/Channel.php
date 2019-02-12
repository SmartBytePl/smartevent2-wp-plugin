<?php

namespace SmartEventPlugin\Entity;


class Channel
{
    const API_ACTION = 'channels';

    private $code;
    private $name;
    private $description;
    private $enabled;
    private $contact_email;
    private $base_currency;
    private $default_language;
    private $currencies = [];
    private $languages = [];

    public function __construct(array $array)
    {
        $this->code = $array['code'];
        $this->name = $array['name'];
        $this->description = $array['description'];
        $this->enabled = $array['enabled'] == 'true' ? true : false;
        $this->contact_email = $array['contact_email'];
        $this->base_currency = $array['base_currency'][0]['code'];
        $this->default_language = $array['default_locale'][0]['code'];
        foreach($array['currencies'] as $currency)
            $this->currencies[$currency['code']] = $currency['code'];
        foreach($array['locales'] as $language)
            $this->languages[$language['code']] = $language['code'];
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return mixed
     */
    public function getContactEmail()
    {
        return $this->contact_email;
    }

    /**
     * @return mixed
     */
    public function getBaseCurrency()
    {
        return $this->base_currency;
    }

    /**
     * @return mixed
     */
    public function getDefaultLanguage()
    {
        return $this->default_language;
    }

    /**
     * @return mixed
     */
    public function getCurrencies()
    {
        return $this->currencies;
    }

    /**
     * @param $currency
     * @return bool
     */
    public function hasCurrency($currency)
    {
        return (isset($this->currencies[$currency])) ? true : false;
    }

    /**
     * @return array
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * @param $language
     * @return bool
     */
    public function hasLanguage($language)
    {
        return (isset($this->languages[$language])) ? true : false;
    }

}