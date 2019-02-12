<?php

namespace SmartEventPlugin\Entity\V1;

use SmartEventPlugin\Entity\V1\Event;

class Product extends Event
{
    const API_ACTION = 'products';

    private $archetype = 'product';

}