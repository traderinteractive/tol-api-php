<?php

namespace TraderInteractive\Api;

use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;

final class SerializerException extends RuntimeException implements InvalidArgumentException
{
}
