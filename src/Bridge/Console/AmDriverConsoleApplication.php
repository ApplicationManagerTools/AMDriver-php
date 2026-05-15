<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Console;

use ApplicationManagerTools\AmDriver\Bridge\Console\Command\CallbackSendCommand;
use ApplicationManagerTools\AmDriver\Bridge\Console\Command\ConsumptionPushCommand;
use ApplicationManagerTools\AmDriver\Bridge\Console\Command\OrchestrationSimulateCommand;
use ApplicationManagerTools\AmDriver\Bridge\Console\Command\OrchestrationSimulateCreateCommand;
use ApplicationManagerTools\AmDriver\Bridge\Console\Command\OrchestrationSimulateStartCommand;
use ApplicationManagerTools\AmDriver\Bridge\Console\Command\OrchestrationSimulateStopCommand;
use ApplicationManagerTools\AmDriver\Bridge\Console\Command\ServeCommand;
use ApplicationManagerTools\AmDriver\Bridge\Console\Command\StatePushSampleCommand;
use Symfony\Component\Console\Application;

final class AmDriverConsoleApplication extends Application
{
    public function __construct()
    {
        parent::__construct('am-driver', '1.0.0');
        $this->addCommands([
            new ServeCommand(),
            new ConsumptionPushCommand(),
            new OrchestrationSimulateCommand(),
            new OrchestrationSimulateCreateCommand(),
            new OrchestrationSimulateStopCommand(),
            new OrchestrationSimulateStartCommand(),
            new CallbackSendCommand(),
            new StatePushSampleCommand(),
        ]);
    }
}
