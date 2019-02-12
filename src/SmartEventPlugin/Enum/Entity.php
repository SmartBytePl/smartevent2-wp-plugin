<?php

namespace SmartEventPlugin\Enum;

class Entity
{
    const TYPE_CHANNEL = 'Channel';
    const TYPE_CURRENCY = 'Currency';
    const TYPE_EVENT = 'Event';
    const TYPE_PRODUCT = 'Product';
    const TYPE_PROMOTION = 'Promotion';
    const TYPE_CATEGORY = 'Category';
    const TYPE_VARIANT = 'Variant';

    const TYPES = [
        self::TYPE_CHANNEL,
        self::TYPE_CURRENCY,
        self::TYPE_EVENT,
        self::TYPE_PRODUCT,
        self::TYPE_PROMOTION,
    ];
}