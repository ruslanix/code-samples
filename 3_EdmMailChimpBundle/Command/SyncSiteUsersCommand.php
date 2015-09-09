<?php

namespace Mesh\EdmMailChimpBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Mesh\EncompassBundle\Entity\Site;

class SyncSiteUsersCommand extends ContainerAwareCommand
{
    protected $output;

    /**
     * Console command configuration
     */
    public function configure()
    {
        $this
            ->setName('mesh:edm:sync-site-users')
            ->setDescription('Synchronize site users with mailchimp db. Process sync user queue.')
            ->addArgument('site', InputArgument::REQUIRED, 'Site id')
            ->addArgument('limit', InputArgument::OPTIONAL, 'limit', 50)
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('fetcher_service')->enableSoftDeleteFilter();
        
        $this->output = $output;
        $limit = $input->getArgument('limit');
        $siteId = $input->getArgument('site');
        $site = $this->getRepository('MeshEncompassBundle:Site')->findOneById($siteId);

        $errors = $this->validateSite($site);

        if (count($errors)) {
            $this->output->writeln("Site is not ready for user synchronization:");
            foreach($errors as $error) {
                $this->output->writeln($error);
            }

            return;
        }

        $this->getContainer()->get('mesh.edm_mailchimp.api_facade')->startWorkflow("Sync users");

        $this->getContainer()
            ->get('mesh.edm_mailchimp.workflow.sync_user_queue')
            ->processSiteQueue($site, $limit);

        $output->writeln('done');
    }

    protected function validateSite(Site $site)
    {
        $errorList = array();

        if (!$site->getEdmMcId()) {
            $errorList[] = "- site is not synchronized with mailchimp, synchronize it befor user synchronization.";
        } else if (!$site->isEdmMcStatusOk()) {
            $errorList[] = "- site has synchronization error, resolve it befor user synchronization.";
        }

        return $errorList;
    }

    protected function getRepository($class)
    {
        return $this->getEntityManager()->getRepository($class);
    }

    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }
}