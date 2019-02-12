<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SmartEventPlugin\Entity\V1\Event;
use SmartEventPlugin\Entity\V1\Product;
use SmartEventPlugin\Entity\V1\Channel;
use SmartEventPlugin\Entity\V1\Promotion;
use SmartEventPlugin\Enum\Entity;
use SmartEventPlugin\EventParser;
use Wruczek\PhpFileCache\PhpFileCache;
use SmartEventPlugin\Exception\ChannelNotFoundException;
use SmartEventPlugin\Exception\CurrencyNotFoundException;
use SmartEventPlugin\Exception\LanguageNotFoundException;

final class ParserTest extends TestCase
{
    const DEFAULT_CHANNEL = 'WEB-PL';
    const DEFAULT_HOST = 'http://localhost:8000';
    const DEFAULT_FIXTURES_SUITE = 'default';

    /** @var $se EventParser */
    private $se;

    public function setUp(): void
    {
        parent::setUp();
        $this->createCacheFixturesSuite(self::DEFAULT_FIXTURES_SUITE, self::DEFAULT_CHANNEL);

        $this->se = new EventParser(self::DEFAULT_HOST, 1);
        $this->se->loadData(self::DEFAULT_CHANNEL);
    }

    private function createCacheFixturesSuite($suite, $channel)
    {
        foreach (Entity::TYPES as $entity){
            $this->createEntityCacheFixture($suite, $channel, $entity);
        }
    }

    private function createEntityCacheFixture($suite, $channel, $entity)
    {
        $cache = new PhpFileCache(sys_get_temp_dir());
        $cacheKey = EventParser::getCacheKey($entity, $channel);
        $response = file_get_contents(__DIR__.'/fixtures/'.$suite.'/'.$cacheKey.'.json');
        $cache->store($cacheKey, $response, EventParser::DEFAULT_CACHE_TIME_SEC);
    }

    public function testItShouldReturnEventsArray(): void
    {
        $events = $this->se->getEvents();

        $this->assertInstanceOf(Event::class, reset($events));
        $this->assertIsArray($events);
        $this->assertEquals(403, count($events));
    }

    public function testItShouldReturnChannelsArray(): void
    {
        $channels = $this->se->getChannels();

        $this->assertInstanceOf(Channel::class, reset($channels));
        $this->assertIsArray($channels);
        $this->assertEquals(2, count($channels));
    }

    public function testItShouldReturnPromotionsArray(): void
    {
        $promotions = $this->se->getPromotions();

        //$this->assertInstanceOf(Promotion::class, reset($promotions));
        $this->assertIsArray($promotions);
        $this->assertEquals(0, count($promotions));

        $this->markTestIncomplete();
    }

    public function testItShouldReturnProductsArray(): void
    {
        $products = $this->se->getProducts();

        $this->assertInstanceOf(Product::class, reset($products));
        $this->assertIsArray($products);
        $this->assertEquals(403, count($products));
    }

    public function testItShouldSetChannel()
    {
        $this->se->setChannel('WEB-PL');

        $this->assertEquals('WEB-PL', $this->se->getChannel()->getCode());
        $this->assertEquals('Polska', $this->se->getChannel()->getName());
        $this->assertEquals('opis', $this->se->getChannel()->getDescription());
        $this->assertEquals('info@smartbyte.pl', $this->se->getChannel()->getContactEmail());
        $this->assertTrue($this->se->getChannel()->isEnabled());

        $this->assertIsArray($this->se->getChannel()->getCurrencies());
        $this->assertEquals(2,count($this->se->getChannel()->getCurrencies()));
        $this->assertTrue($this->se->getChannel()->hasCurrency('EUR'));
        $this->assertEquals('PLN', $this->se->getChannel()->getBaseCurrency());

        $this->assertIsArray($this->se->getChannel()->getLanguages());
        $this->assertEquals(1,count($this->se->getChannel()->getLanguages()));
        $this->assertTrue($this->se->getChannel()->hasLanguage('pl_PL'));
        $this->assertEquals('pl_PL', $this->se->getChannel()->getDefaultLanguage());
    }

    public function testItShouldThrowChannelNotFoundExceptionOnSetChannel()
    {
        $this->expectException(ChannelNotFoundException::class);
        $this->se->setChannel('NON-EXISTING');

    }

    public function testItShouldSetDefaultLanguage()
    {
        $this->se->setLanguage();
        $this->assertEquals('pl_PL', $this->se->getLanguage());
        $this->assertEquals($this->se->getChannel()->getDefaultLanguage(), $this->se->getLanguage());

        /** @var Event $event */
        $events = $this->se->getEvents();
        $event = reset($events);
        $this->assertEquals('pl_PL', $event->getLanguage());
        $this->assertEquals($this->se->getLanguage(), $event->getLanguage());

    }

    public function testItShouldThrowLanguageNotFoundExceptionOnSetLanguage()
    {
        $this->expectException(LanguageNotFoundException::class);
        $this->se->setLanguage('NON-EXISTING');
    }

    public function testItShouldReturnVariantsIds()
    {
        $variants = $this->se->getVariants();
        $variant = reset($variants);

        $this->assertEquals(1, $variant);
        $this->assertIsArray($variants);
        $this->assertEquals(403, count($variants));
    }

    public function testItShouldReturnCities()
    {
        $cities = $this->se->getCities();
        $city = reset($cities);
        $this->assertIsArray($cities);
        $this->assertEquals('Warszawa', $city);
        $this->assertEquals(5, count($cities));
    }

    public function testItShouldReturnEventDates()
    {
        $dates = $this->se->getEventDates();
        $date = reset($dates);

        $this->assertNotEquals('0', strtotime($date));
        $this->assertIsArray($dates);
        $this->assertEquals(403, count($dates));
    }

    public function testItShouldReturnFirstAndLastDate()
    {
        $date = $this->se->getFirstAndLastDate('first');

        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertEquals('2019-02-13', $date->format('Y-m-d'));

        $date = $this->se->getFirstAndLastDate('last');
        $this->assertEquals('2020-11-03', $date->format('Y-m-d'));
    }

    public function testItShouldFindEventsByDate()
    {
        $events = $this->se->findByDate('2019-02-15');
        $event = reset ($events);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertIsArray($events);
        $this->assertEquals('1',count($events));
    }

    public function testItShouldFindByCategoryNames()
    {
        $events = $this->se->findByCategoryName(['Warszawa', 'Jakub Cyran'], 'AND');
        $event = reset ($events);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertIsArray($events);
        $this->assertEquals('1',count($events));

        $events = $this->se->findByCategoryName(['Warszawa', 'Jakub Cyran'], 'OR');
        $event = reset ($events);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertIsArray($events);
        $this->assertEquals('26',count($events));

        $events = $this->se->exclude(['134', '-1']);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertIsArray($events);
        $this->assertEquals('25',count($events));

        $this->se->resetFilters();

        $events = $this->se->getEvents();
        $this->assertInstanceOf(Event::class, $event);
        $this->assertIsArray($events);
        $this->assertEquals('403',count($events));

    }

    public function testItShouldReturnSingleEvent()
    {
        $event = $this->se->getEventById('134');

        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals('134', $event->getId());

        $event = $this->se->getEventById('-1');
        $this->assertEquals(null, $event);

        $event = $this->se->getEventByVariant('139');
        $this->assertEquals('139', $event->getId());

        $event = $this->se->getEventByVariant('-1');
        $this->assertEquals(null, $event);
    }

    public function testItShouldReturnMinOnHand()
    {
        $onHand = $this->se->getMinOnHand(['139','134']);
        $this->assertEquals(28, $onHand);
    }

    public function testItShouldReturnEventsIdsByVariants()
    {
        $eventIds = $this->se->getIdsFromVariants(['139','134']);
        $this->assertIsArray($eventIds);
        $this->assertEquals(['139','134'], $eventIds);
    }

    public function testItShouldConvertPrice()
    {
        $price = $this->se->convertPriceTo(970, 'USD');
        $this->assertEquals(262.16,round($price,2));
    }

    public function testItShouldThrowCurrencyNotFoundException()
    {
        $this->expectException(CurrencyNotFoundException::class);
        $price = $this->se->convertPriceTo(970, 'EUR');
    }

}