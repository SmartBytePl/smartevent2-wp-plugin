<?php

namespace SmartEventPlugin;

use SmartEventPlugin\Entity\V1\Currency;
use SmartEventPlugin\Enum\Entity;
use SmartEventPlugin\Entity\V1\Factory;
use SmartEventPlugin\Entity\V1\Event;
use SmartEventPlugin\Entity\V1\Category;
use SmartEventPlugin\Entity\V1\Channel;
use SmartEventPlugin\Exception\CurrencyNotFoundException;
use SmartEventPlugin\Exception\EntityNotFoundException;
use SmartEventPlugin\Exception\ChannelNotFoundException;
use SmartEventPlugin\Exception\LanguageNotFoundException;
use Wruczek\PhpFileCache\PhpFileCache;

/**
 * Class EventParser
 * @author Jakub Lech
 * @author Krzysztof Wędrowicz
 */
class EventParser
{
    const API_PATH_PATTERN = '/openapi/v%d';
    const CLASS_PATTERN = '\SmartEventPlugin\Entity\V%d\%s';
    const DEFAULT_CACHE_TIME_SEC = 300;
    const CACHE_KEY_PATTERN = '%s-%s';

    private $host;
    private $apiPath;
    private $version;

    /** @var Channel $channel */
    private $channel;
    private $language;
    private $factory;

    private $items = [];

    /**
     * EventParser constructor.
     * Takes two parameters. First is the host address of SmartEvent backend server.
     * The second one is one of supported language.
     * @param $host
     * @param int $version
     */
    public function __construct($host,int $version = 1)
    {
        $this->host = $host;
        $this->version = $version;
        $this->apiPath = sprintf(self::API_PATH_PATTERN, (int)$version);
        $factoryClass = sprintf(self::CLASS_PATTERN, (int)$version, 'Factory');
        $this->factory = new $factoryClass();
    }

    /**
     * @param string $channel
     * @param null $language
     * @throws ChannelNotFoundException
     * @throws EntityNotFoundException
     * @throws LanguageNotFoundException
     */
    public function loadData($channel = 'WEB-PL', $language = null)
    {
        foreach (Entity::TYPES as $entityType){
            $array = $this->getEntity($entityType, $channel);
            $entity = $this->factory->getEntities($array, $entityType);
            $this->items[$entityType] = $entity;
        }
        $this->setChannel($channel);
        $this->setLanguage($language);
    }

    public static function  getCacheKey(String $entityType, String $channel)
    {
        return sprintf(self::CACHE_KEY_PATTERN, $entityType, $channel);
    }

    /**
     * Download events data from backendServer in json format
     * @return array
     * @throws EntityNotFoundException
     * @throws ChannelNotFoundException
     */
    private function getEntity(String $entityType, String $channel)
    {
        if (!in_array($entityType, Entity::TYPES)) {
            throw new EntityNotFoundException("Entity Type '$entityType' is not supported");
        }

        if (isset($this->items[Entity::TYPE_CHANNEL]) and !isset($this->items[Entity::TYPE_CHANNEL][$channel])){
            throw new ChannelNotFoundException(sprintf('Channel %s not found', $channel));
        }

        $class = sprintf(self::CLASS_PATTERN, $this->version, $entityType);
        if (empty($class::API_ACTION)){
            return [];
        }

        $cacheKey = $this->getCacheKey($entityType, $channel);

        $cache = new PhpFileCache(sys_get_temp_dir());
        $response = null;
        if ($cache->isExpired($cacheKey)){
            if ($response = $this->loadApiEntity($class::API_ACTION, $channel)){
                $cache->store($cacheKey, $response, self::DEFAULT_CACHE_TIME_SEC);
            }
        }

        if (!$response){
            $response = $cache->retrieve($cacheKey);
        }

        return json_decode($response, true);
    }

    private function loadApiEntity(String $entityType, String $channel)
    {
        $ch = curl_init();
        $url = $this->host.$this->apiPath."/".$entityType.'?channel='.$channel;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($ch);
    }

    public function setChannel(String $channel)
    {
        if (!isset($this->items[Entity::TYPE_CHANNEL][$channel])){
            throw new ChannelNotFoundException(sprintf('Channel %s not found', $channel));
        }
        $this->channel = $this->items[Entity::TYPE_CHANNEL][$channel];
        $this->setLanguage();
    }

    /**
     * @return Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return String
     */
    public function getChannelCurrency()
    {
        return $this->getChannel()->getBaseCurrency();
    }

    /**
     * Set default language
     * @param $language
     *
     * @throws LanguageNotFoundException
     */
    public function setLanguage($language = null)
    {
        if (!$language){
            $language = $this->getChannel()->getDefaultLanguage();
        }

        if (!$this->getChannel()->hasLanguage($language)){
            throw new LanguageNotFoundException(sprintf('Language %s is not availible for channel %s', $language, $this->getChannel()->getCode()));
        }

        /** @var Event $event */
        foreach($this->items[Entity::TYPE_EVENT] as &$event){
            $event->setLanguage($language);
        }
        $this->language = $language;
    }

    /**
     * Get currency
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Get array of all events
     * @return array
     */
    public function getEvents()
    {
        $return = [];
        /** @var Event $event */
        foreach($this->items[Entity::TYPE_EVENT] as $event){
            if ($event->isVisible()){
                $return [] = $event;
            }
        }
    	return $return;
    }

    /**
     * Get array of all products
     * @return array
     */
    public function getProducts()
    {
        return $this->items[Entity::TYPE_PRODUCT];
    }

    /**
     * Get array of all promotions
     * @return array
     */
    public function getPromotions()
    {
        return $this->items[Entity::TYPE_PROMOTION];
    }

    /**
     * Get array of all promotions
     * @return array
     */
    public function getChannels()
    {
        return $this->items[Entity::TYPE_CHANNEL];
    }

    /**
     * Get array of all bonuses
     * @return array
     * @deprecated please use getProducts
     */
    public function getBonuses()
    {
        return $this->getProducts();
    }


    public function getVariants()
    {
    	$variants = [];
    	/* @var Event $event */
    	foreach($this->items[Entity::TYPE_EVENT] as $event){
    		$variants[] = $event->getMasterVariantId();
	    }
	    return $variants;
    }



    public function getCities()
    {
    	$cities = [];
	    /* @var Event $event */
	    foreach($this->items[Entity::TYPE_EVENT] as $event){
	        if (null != $event->getCity())
	    	$cities[] = $event->getCity();
	    }
	    return array_unique($cities);
    }

	/**
	 * Get array of dates when events take place
	 *
	 * @param null $variants
	 *
	 * @return array
	 */
    public function getEventDates($variants = null){
        $dates = [];
	    if($variants){
		    foreach ($variants as $variant)
		    {
			    $dates[] = $this->getEventByVariant($variant)->getDate();
		    }
	    }
	    else{
		    /* @var Event $event */
		    foreach ($this->items[Entity::TYPE_EVENT] as $event)
		    {
			    $dates[] = $event->getDate();
		    }
	    }
	    $dates = array_values(array_unique($dates));
	    sort($dates);
        return $dates;
    }

    public function getFirstAndLastDate($which = 'first')
    {
	    $dates = $this->getEventDates();
	    $passed = [];
	    $first_date = null;
	    $last_date = null;
	    foreach($dates as $d)
	    {
	    	$date = new \DateTime($d);
	    	if(!$passed){
	    		$first_date = $date;
	    		$last_date = clone $first_date;
	    		$last_date->modify('+2000 days');
		    }
		    if($date <= $last_date)
		        $passed[] = $date;
	    }
	    $last = count($passed) > 0 ? $passed[count($passed) - 1] : null;

	    return ($which == 'first') ? $first_date : $last;
    }

    /**
     * Get all events from particular date
     * @param $date
     *
     * @return array
     */
    public function findByDate($date)
    {
	    /* @var Event $event */
	    foreach ($this->items[Entity::TYPE_EVENT] as &$event)
        {
            if($event->getDate() == $date){
                $event->setVisible(true);
            } else {
                $event->setVisible(false);
            }
        }
        return $this->getEvents();
    }

    public function findByCategoryName(array $categoryNames, $method)
    {
	    /* @var Event $event */
        foreach ($this->items[Entity::TYPE_EVENT] as &$event)
        {
        	/* @var Category $category */
            $count = 0;
            $event->setVisible(false);
            foreach ($event->getCategories() as $category){
                if(in_array($category->getName(), $categoryNames)){
                    $count ++;
                    if ($method == 'OR'){
                        $event->setVisible(true);
                    } elseif ($count == count($categoryNames)) {
                        $event->setVisible(true);
                    }
                }
            }
        }
        return $this->getEvents();
    }

    public function exclude(array $excludeArray)
    {
        /** @var Event $event */
        foreach ($this->items[Entity::TYPE_EVENT] as &$event){
            if(in_array($event->getId(), $excludeArray))
                $event->setVisible(false);
        }
	    return $this->getEvents();
    }

    public function resetFilters(){
        $this->loadData($this->getChannel()->getCode(), $this->getLanguage());
    }

    public function getEventById($id)
    {
	    /* @var Event $event */
	    foreach ($this->items[Entity::TYPE_EVENT] as $event){
		    if($event->getId() == $id){
			    return $event;
		    }
	    }
	    return null;
    }

	public function getEventByVariant($variant)
    {
		/* @var Event $event */
		foreach ($this->items[Entity::TYPE_EVENT] as $event){
			if($event->getMasterVariantId() == $variant){
				return $event;
			}
		}
		return null;
	}

	public function getMinOnHand($variants)
    {
		$onHand = 10000;
		foreach ($variants as $variant){
			$onHand = min($this->getEventByVariant($variant)->getOnHand(), $onHand);
		}
		return $onHand;
	}

	public function getIdsFromVariants($variants)
    {
		$ids = [];
		foreach($variants as $variant){
			$ids[] = $this->getEventByVariant($variant)->getId();
		}
		return $ids;
	}

	public function convertPriceTo($price, $targetCurrency)
    {
        if ($this->getChannel()->hasCurrency($targetCurrency)){
            throw new CurrencyNotFoundException(sprintf('Currency %s is not availible for channel %s', $targetCurrency, $this->getChannel()->getCode()));
        }
        $sourceCurrency = $this->getChannel()->getBaseCurrency();

        /** @var Currency $exchange */
        $exchange = $this->items[Entity::TYPE_CURRENCY][$sourceCurrency.'-'.$targetCurrency];
        if (!isset($exchange)){
            throw new \InvalidArgumentException(sprintf("Exchange rate for currency %s not exists", $targetCurrency));
        }
        $ratio = $exchange->getRatio();

        return $price/$ratio;
    }
}
