<?php

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\Executable;
use FiiSoft\Jackdaw\Internal\ResultApi;
use FiiSoft\Jackdaw\Internal\SignalHandler;

interface LastOperation extends ResultApi, Executable, SignalHandler
{
}