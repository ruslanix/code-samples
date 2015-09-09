<?php

namespace Application\CatalogParser\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Application\CatalogParser\DB\DBFunc;

class DBCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('parser:DB')
            ->setDescription('Database manipulation')
            ->addArgument(
                'method',
                InputArgument::REQUIRED,
                'DB method: create, truncate'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $method = $input->getArgument('method');
        $method = 'execute_' .$method;

        if(!method_exists($this, $method)){
            throw new \Exception("[DBCommand] wrong method, see --help");
        }

        call_user_func_array(array($this, $method), array($input, $output));
    }

    protected function execute_create(InputInterface $input, OutputInterface $output)
    {
        $dbFunc = new DBFunc();
        $dbFunc->createSchema();
        $output->writeln("Done.");
    }

    protected function execute_truncate(InputInterface $input, OutputInterface $output)
    {
        $dbFunc = new DBFunc();
        $dbFunc->truncateDatabase();
        $output->writeln("Done.");
    }
}