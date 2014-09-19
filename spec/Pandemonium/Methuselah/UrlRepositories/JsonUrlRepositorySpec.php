<?php namespace spec\Pandemonium\Methuselah\UrlRepositories;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class JsonUrlRepositorySpec extends ObjectBehavior
{
    function let()
    {
        // Feed the repository with test URLs.
        $this->setSource('spec/testdata/urls.json');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Pandemonium\Methuselah\UrlRepositories\JsonUrlRepository');
    }

    function it_retrieves_urls()
    {
        $this->find('foo.bar')
        ->shouldReturn('spec/testdata/foo/baz.txt');
    }

    function it_retrieves_urls_with_placeholders()
    {
        $this->find('foo.baz', ['qux' => 'boom'])
        ->shouldReturn('spec/testdata/foo/boom/quux');
    }
}
