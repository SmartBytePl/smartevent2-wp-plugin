<?php

namespace SmartEventPlugin\Entity;

use SmartEventPlugin\Exception\ChannelNotFoundException;
use SmartEventPlugin\Exception\LanguageNotFoundException;

class Event
{
    const API_ACTION = 'events';

	private $id;
	private $isEnabled;
	private $date;
	private $name = [];
	private $description = [];
	private $attr = [];
	private $categories = [];
	private $masterVariantId;
	private $archetype = 'event';
	private $language;
	private $channels = [];
    private $variants = [];
    private $channel;
    private $visible = true;

	public function __construct(array $event) {

	    $this->id = $event['id'];
		$this->isEnabled = $event['enabled'];
		//$this->date = substr($event['available_until'],0,10);

        $channels = [];
        foreach($event['variants'] as $array){

            $variant = new Variant($array);

            if (empty($this->variants)){
                $this->masterVariantId = $variant->getId();
            }

            $channels = array_merge($channels, $variant->getChannels());

            $this->variants[$variant->getId()] = $variant;
        }

        $this->channels = $channels;

        $this->setChannel(reset($channels));

        foreach($event['attributes'] as $attribute){
            $code = $attribute['code'];
            foreach ($attribute['translations'] as $trans){
                $lang = $trans['locale'];
                $this->attr[$code][$lang]['name'] = $trans['name'];
                $this->attr[$code][$lang]['value'] = $trans['value'];
            }
        }

        foreach($event['translations'] as $language => $value){
            $this->name[$language] = $value['name'];
            $this->description[$language] = $value['description'];
        }

		if(array_key_exists("categories", $event)){
			/* @var Category $category */
			foreach($event['categories'] as $category) {
			    try{
                    $this->categories[] = new Category($category);
                } catch (\Exception $e){
                    error_log(sprintf('Event %d has no category connected with. %s', $this->id, $e->getMessage()));
                }
			}
		}
	}

	public function getChannel()
    {
        return $this->channel;
    }

	public function setChannel(String $channel){
        if (!$this->hasChannel($channel)){
            throw new ChannelNotFoundException(sprintf('Channel %s not found ', $channel));
        }

        /** @var Variant $variant */
        foreach($this->variants as $key => &$variant){
            if ($variant->hasChannel($channel)){
                $variant->setChannel($channel);
            }
        }

        $this->channel = $channel;
        return $this;
    }

    public function hasChannel(String $channel){
	    return isset($this->channels[$channel]) ? true : false;
    }

    public function getLanguage(){
        return $this->language;
    }


    public function setLanguage(String $language){
	    if (!$this->hasLanguage($language)){
	        throw new LanguageNotFoundException(sprintf('Language %s not found', $language));
        }

        $this->language = $language;

        /** @var Category $category */
        if (!empty($this->categories)){
            foreach ($this->categories as &$category){
                $category->setLanguage($language);
            }
        }

        return $this;
    }

    public function hasLanguage(String $language){
        return (array_key_exists($language, $this->name) AND array_key_exists($language, $this->description)) ? true : false;
    }

	public function getId(){
		return $this->id;
	}

	public function getArchetype(){
		return $this->archetype;
	}

	public function isEnabled(){
		return $this->isEnabled;
	}

	public function getDate(){
		return $this->date;
	}

	public function getDateText (){
		$dzien = substr($this->date,8,2);
		$dzien_tyg = substr($this->date,0,10);
		$miesiac = substr($this->date,5,2);

		$miesiac_pl = array(
			'01' => 'stycznia',
			'02' => 'luty',
			'03' => 'marca',
			'04' => 'kwietnia',
			'05' => 'maja',
			'06' => 'czerwca',
			'07' => 'lipca',
			'08' => 'sierpnia',
			'09' => 'września',
			'10' => 'października',
			'11' => 'listopada',
			'12' => 'grudnia'
		);

		$dzien_tyg_pl = array(
			'Monday' => 'Poniedziałek',
			'Tuesday' => 'Wtorek',
			'Wednesday' => 'Środa',
			'Thursday' => 'Czwartek',
			'Friday' => 'Piątek',
			'Saturday' => 'Sobota',
			'Sunday' => 'Niedziela',
		);

		return '<span class="day">' . $dzien . '</span>' . '<span class="month">' . $miesiac_pl[$miesiac] . '</span>' . '<span class="day-num">'. $dzien_tyg_pl[strftime("%A", strtotime($dzien_tyg))] .'<span>';
	}

	public function getName(){
		return $this->name[$this->getLanguage()];
	}

	public function getDescription(){
		return $this->description[$this->getLanguage()];
	}

	public function getPrice(){
        $variants = $this->getVariants();
        $variant =  reset($variants);
		return $variant->getPrice();
	}

	public function getOnHand(){
	    $variants = $this->getVariants();
        $variant =  reset($variants);
        return $variant->getOnHand();
	}

	public function getAttributeValue(String $attr)
    {
        return isset($this->attr[$attr][$this->getLanguage()]['value']) ? $this->attr[$attr][$this->getLanguage()]['value'] : null;
    }

    public function getAttributeName(String $attr)
    {
        return isset($this->attr[$attr][$this->getLanguage()]['name']) ? $this->attr[$attr][$this->getLanguage()]['name'] : null;;
    }

	public function getUrl(){
        return $this->getAttributeValue('url');
	}

	public function getAddress(){
		return $this->getAttributeValue('address');
	}

	public function getVariants(){
	    $return = [];
	    /** @var Variant $variant */
        foreach ($this->variants as $variant){
	        if ($variant->hasChannel($this->getChannel())){
                $return [$variant->getId()] = $variant;
            }
        }
	    return $return;
    }

	public function getCity(){
		/* @var Category $category */
		foreach($this->categories as $category){
			if($category->getParentCode() == 'event_city'){
				return $category->getName();
			}
		}
		return null;
	}

	/**
	 * @param $name
	 *
	 * @return Category|null
	 */
	public function getCategoryByName($name){
		/* @var Category $category */
		foreach ($this->categories as $category){
			if($category->getName() == $name)
				return $category;
		}
		return null;
	}

	/**
	 * @param $id
	 *
	 * @return Category|null
	 */
	public function getCategoryById($id){
		/* @var Category $category */
		foreach ($this->categories as $category){
			if($category->getId() == $id)
				return $category;
		}
		return null;
	}

	public function getCategories(){
		return $this->categories;
	}

	/**
	 * @param $parentName
	 *
	 * @return array
	 */
	public function getCategoriesByParentName($parentName){
		$categories = [];
		if($this->categories){
			/* @var Category $category */
			foreach ($this->categories as $category){
				if($category->getParentName() == $parentName){
					$categories[] = $category;
				}
			}
		}
		return $categories;
	}

	public function getMasterVariantId()
	{
	    $variants = $this->getVariants();
        $variant =  reset($variants);
        return $variant->getId();
	}

	public function setVisible(bool $visible)
    {
        $this->visible = $visible;
        return $this;
    }

	public function isVisible()
    {
        return $this->visible;
    }
}
