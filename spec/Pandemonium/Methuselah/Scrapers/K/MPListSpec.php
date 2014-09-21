<?php namespace spec\Pandemonium\Methuselah\Scrapers\K;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Illuminate\Container\Container;

class MPListSpec extends ObjectBehavior
{
    function let($provider)
    {
        $provider = (new Container)->make('Pandemonium\Methuselah\DocumentProvider');

        // Feed the URL repository with local test paths.
        $provider->getUrlRepository()->setSource('spec/testdata/urls.json');

        $this->beConstructedWith($provider);

        $this->setOptions(['legislature_number' => 54]);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Pandemonium\Methuselah\Scrapers\K\MPList');
    }

    function it_should_return_an_array_when_scraping()
    {
        $this->scrape()->shouldBeArray();
    }
}
