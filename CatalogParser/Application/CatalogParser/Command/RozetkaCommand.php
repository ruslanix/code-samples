<?php

namespace Application\CatalogParser\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Application\CatalogParser\Parser\Rozetka\RozetkaParserChainFactory;
use Application\CatalogParser\Parser\Rozetka\RozetkaPageIterator;
use Application\CatalogParser\Parser\Rozetka\RozetkaParser;
use Application\CatalogParser\Utils\ArrayUtils;
use Application\CatalogParser\DB\DBFunc;

class RozetkaCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('parser:parse:rozetka')
            ->setDescription('Parse Rozetka.com.ua catalog')
            ->addArgument(
                'query',
                InputArgument::REQUIRED,
                "Search query (for example: 'роутер')"
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Sets max parsed items count, default 200',
                200
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $query = $input->getArgument('query');
        $limit = $input->getOption('limit');

        $parser = new RozetkaParser();
        $parser->setCatalogPageIterator(new RozetkaPageIterator($query));
        $parser->setParserChainFactory(new RozetkaParserChainFactory());
        $parser->setMaxItemsCount($limit);
        $parser->parse();

        $items = $parser->getItems();

        ArrayUtils::sortByKey($items, 'price', SORT_ASC);
        $csvData = ArrayUtils::array2scv($items);

        $dbFunc = new DBFunc();
        $dbFunc->saveCatalogData('rozetka.com.ua', $query, $csvData);

        $output->writeln("Done.");
    }
}