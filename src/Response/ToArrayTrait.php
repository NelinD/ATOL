<?php

namespace SSitdikov\ATOL\Response;

trait ToArrayTrait
{
    public function toArray(): array
    {
        $result = [];

        foreach (get_class_vars(static::class) as $key => $value) {
            if ($this->{$key} instanceof ResponseInterface) {
                $result[$key] = $this->{$key}->toArray();
            } elseif (is_object($this->{$key})) {
                $result[$key] = (array)$this->{$key};
            } else {
                $result[$key] = $this->{$key};
            }
        }

        return $result;
    }
}
