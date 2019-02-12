<?php

namespace SmartEventPlugin\Entity;

use SmartEventPlugin\Entity\Event;

class Product extends Event
{
    const API_ACTION = 'products';

    private $archetype = 'product';

}