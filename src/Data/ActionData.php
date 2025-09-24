<?php

declare(strict_types = 1);

namespace Pekral\Arch\Data;

abstract class ActionData
{

    /**
     * @return array<string, mixed>
     */
    abstract public function getRules(): array;

    /**
     * @return array<string, mixed>
     */
    abstract public function getPipes(): array;

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $reflection = new \ReflectionClass($this);
        $data = [];
        
        foreach ($reflection->getProperties() as $property) {
            $data[$property->getName()] = $property->getValue($this);
        }
        
        return $data;
    }

}
