<?php

namespace SmartEventPlugin\Entity;

use SmartEventPlugin\Exception\LanguageNotFoundException;

class Category
{
    const API_ACTION = false;

	private $id;
	private $name = [];
	private $code;
	private $parentName = [];
	private $parentCode;
	private $parentId;
	private $language;

	public function __construct(array $category) {
		if(!is_array($category))
			throw new \Exception('Not an array');
		$this->id = $category['id'];
		$this->code = $category['code'];

        if($category['parent']){
            $this->parentId = $category['parent']['id'];
            $this->parentCode = $category['parent']['code'];

            foreach($category['parent']['translations'] as $language => $value){
                $this->parentName[$language] = $value['name'];
            }
        }

		foreach($category['translations'] as $language => $value){
            $this->name[$language] = $value['name'];

            if (!isset($this->language)){
                $this->setLanguage($language);
            }
        }

	}

    public function hasLanguage(String $language){
        return (isset($this->name[$language])) ? true : false;
    }

    public function hasLanguageParent(String $language){
        return (isset($this->parentName[$language])) ? true : false;
    }

	public function setLanguage(String $language){
        if (!$this->hasLanguage($language) OR !$this->hasLanguageParent($language)){
            throw new LanguageNotFoundException(sprintf('Language %s not found', $language));
        }

        $this->language = $language;
        return $this;
    }

    public function getLanguage(){
        return $this->language;
    }

	public function getId(){
		return $this->id;
	}

	public function getName(){
		return $this->name[$this->language];
	}

	public function getCode(){
		return $this->code;
	}

	public function getParentId(){
		return $this->parentId;
	}

	public function getParentName(){
        return $this->parentName[$this->language];
	}

	public function getParentCode(){
		return $this->parentCode;
	}
}
