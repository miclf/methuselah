<?php namespace spec\Pandemonium\Methuselah;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Pandemonium\Methuselah\UrlRepositories\JsonUrlRepository;

class DocumentProviderSpec extends ObjectBehavior
{
    function let()
    {
        // Feed the repository with test paths and URLs.
        $this->getUrlRepository()->setSource('spec/testdata/urls.json');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Pandemonium\Methuselah\DocumentProvider');
    }

    function it_loads_local_files()
    {
        $this->get('foo.bar')->shouldReturn("baz\n");
    }
}
