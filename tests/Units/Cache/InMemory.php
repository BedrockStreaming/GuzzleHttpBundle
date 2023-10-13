<?php

declare(strict_types=1);

namespace M6Web\Bundle\GuzzleHttpBundle\tests\Units\Cache;

class InMemory extends \atoum
{
    public function testEmptyCache()
    {
        $this
            ->if($this->newTestedInstance())
            ->then
                ->boolean($this->testedInstance->has('foo'))
                    ->isFalse()
                ->variable($this->testedInstance->get('bar'))
                    ->isNull()
        ;
    }

    public function testCache()
    {
        $this
            ->if($this->newTestedInstance())
            ->and($value = uniqid())
            ->and($key = uniqid())
            ->and($this->testedInstance->set($key, $value))
            ->then
                ->boolean($this->testedInstance->has($key))
                    ->isTrue()
                ->string($this->testedInstance->get($key))
                    ->isEqualto($value)
                ->variable($this->testedInstance->ttl($key))
                    ->isNull()
                ->variable($this->testedInstance->remove($key))
                    ->isNull()
                ->boolean($this->testedInstance->has($key))
                    ->isFalse()
                ->variable($this->testedInstance->get($key))
                    ->isNull()
                ->boolean($this->testedInstance->ttl($key))
                    ->isFalse()
        ;
    }

    public function testTtl()
    {
        $this
            ->if($this->newTestedInstance())
            ->and($value = uniqid())
            ->and($key = uniqid())
            ->and($this->testedInstance->set($key, $value, 1))
            ->then
                ->boolean($this->testedInstance->has($key))
                    ->isTrue()
                ->string($this->testedInstance->get($key))
                    ->isEqualto($value)
                ->integer($this->testedInstance->ttl($key))
                    ->isEqualto(1)
            ->then(sleep(1))
                ->boolean($this->testedInstance->has($key))
                    ->isFalse()
                ->variable($this->testedInstance->get($key))
                    ->isNull()
                ->boolean($this->testedInstance->ttl($key))
                    ->isFalse()
        ;
    }
}
