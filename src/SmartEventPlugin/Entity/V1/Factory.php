<?php

namespace SmartEventPlugin\Entity\V1;

class Factory
{

    public function getEntities($json, $entityType){
        $return = [];
        foreach ($json as $item){
            $class = 'SmartEventPlugin\Entity\V1\\'.$entityType;
            $object = new $class($item);

            if (method_exists($object,'getCode')){
                $id = $object->getCode();
            } elseif (method_exists($object,'getId')){
                $id = $object->getId();
            }

            if (isset($id)){
                $return[$id] = $object;
            } else {
                $return[] = $object;
            }

            unset($id);
        }
        return $return;
    }
}