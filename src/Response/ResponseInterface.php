<?php

namespace SSitdikov\ATOL\Response;

interface ResponseInterface
{

    public function __construct(\stdClass $json);

    public function toArray(): array;
}
