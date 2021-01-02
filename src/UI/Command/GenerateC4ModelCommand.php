<?php

declare(strict_types=1);

namespace App\UI\Command;

use Psr\Log\LoggerInterface;
use StructurizrPHP\Client\Client;
use StructurizrPHP\Client\Credentials;
use StructurizrPHP\Client\Infrastructure\Http\SymfonyRequestFactory;
use StructurizrPHP\Client\UrlMap;
use StructurizrPHP\Core\Model\Enterprise;
use StructurizrPHP\Core\Model\Location;
use StructurizrPHP\Core\Model\Tags;
use StructurizrPHP\Core\View\Configuration\Shape;
use StructurizrPHP\Core\View\PaperSize;
use StructurizrPHP\Core\Workspace;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateC4ModelCommand extends Command
{
    private const TAG_DB = 'TAG_DB';
    private const TAG_WEB_BROWSER = 'TAG_WEB_BROWSER';
    private const TAG_HEXAGON = 'TAG_HEXAGON';
    private const TAG_INTERNAL = 'TAG_INTERNAL';

    private string $workspace;
    private string $apiKey;
    private string $apiSecret;
    private LoggerInterface $logger;

    public function __construct(string $workspace, string $apiKey, string $apiSecret, LoggerInterface $logger)
    {
        parent::__construct();

        $this->workspace = $workspace;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setName('c4model:generate')
            ->setDescription('Generates C4 model using Structurizr');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workspace = new Workspace(
            $id = $this->workspace,
            $name = 'Loyalty Points Collector',
            $description = 'Allows to collect loyalty points in wallets'
        );
        $workspace->getModel()->setEnterprise(new Enterprise('Structurizr PHP'));
        $person = $workspace->getModel()->addPerson(
            $name = 'User',
            $description = 'A person who manages wallets',
            Location::internal()
        );
        $softwareSystem = $workspace->getModel()->addSoftwareSystem(
            $name = 'Loyalty Points Collector',
            $description = 'Allows to collect loyalty points in wallets',
            Location::internal()
        );
        $person->usesSoftwareSystem($softwareSystem, 'Uses', 'HTTP');

        $contextView = $workspace->getViews()->createSystemContextView($softwareSystem, 'System Context', 'Loyalty Points Collector');
        $contextView->addAllElements();
        $contextView->setAutomaticLayout(true);

        $styles = $workspace->getViews()->getConfiguration()->getStyles();

        $styles->addElementStyle(Tags::SOFTWARE_SYSTEM)->background('#1168bd')->color('#ffffff');
        $styles->addElementStyle(Tags::PERSON)->background('#08427b')->color('#ffffff')->shape(Shape::person());

        //Containers
        $apiContainer = $softwareSystem->addContainer('Wallet API', 'The API to manage wallets', 'php');
        $webAppContainer = $softwareSystem->addContainer('Web App', 'User interface for managing wallets', 'TypeScript/ReactJS');
        $nodeAppContainer = $softwareSystem->addContainer('Node App', 'Handles sockets connections to inform about wallet changes', 'Node');
        $pubSubContainer = $softwareSystem->addContainer('Simple message broker', '', 'Redis');
        $messageBrokerContainer = $softwareSystem->addContainer('Message broker', '', 'RabbitMQ');
        $database = $softwareSystem->addContainer('Database', '', 'MySQL');

        //Containers - people relationship
        $person->usesContainer($webAppContainer, 'Manages wallets', 'Web Browser');

        //Containers - relationships
        $webAppContainer->usesContainer($apiContainer, 'Sends requests', 'Http');
        $webAppContainer->usesContainer($nodeAppContainer, 'Subscribes to sockets', 'Sockets');

        $apiContainer->usesContainer($database, 'Reads and writes');
        $apiContainer->usesContainer($pubSubContainer, 'Notifies that changes were performed on wallet');

        $apiContainer->uses($messageBrokerContainer, 'Publishes commands');
        $apiContainer->uses($messageBrokerContainer, 'Publishes persisted events');
        $apiContainer->uses($messageBrokerContainer, 'Subscribes to messages');

        $nodeAppContainer->usesContainer($pubSubContainer, 'Subscribes to changes applied to wallet');
        $nodeAppContainer->usesContainer($webAppContainer, 'Notifies about changes applied to wallet', 'Sockets');

        $systemContainerView = $workspace->getViews()->createContainerView($softwareSystem, 'system-container', 'Loyalty Points Collector - detailed view');
//        $systemContainerView->setAutomaticLayout(true);
        $systemContainerView->setPaperSize(PaperSize::A5_Landscape());

        $systemContainerView->addAllPeople(true);
        $systemContainerView->addContainer($webAppContainer);

        $systemContainerView->addContainer($apiContainer);
        $systemContainerView->addContainer($pubSubContainer);
        $systemContainerView->addContainer($messageBrokerContainer);
        $systemContainerView->addContainer($nodeAppContainer);
        $systemContainerView->addContainer($database);

        // Tags
        $database->addTags(self::TAG_DB, self::TAG_INTERNAL);
        $webAppContainer->addTags(self::TAG_WEB_BROWSER, self::TAG_INTERNAL);
        $apiContainer->addTags(self::TAG_HEXAGON, self::TAG_INTERNAL);
        $apiContainer->addTags(self::TAG_INTERNAL);
        $nodeAppContainer->addTags(self::TAG_INTERNAL);
        $messageBrokerContainer->addTags(self::TAG_INTERNAL);
        $pubSubContainer->addTags(self::TAG_INTERNAL);

        // Styles
        $styles = $workspace->getViews()->getConfiguration()->getStyles();
        $styles->addElementStyle(self::TAG_DB)->shape(Shape::cylinder());
        $styles->addElementStyle(self::TAG_WEB_BROWSER)->shape(Shape::webBrowser());
        $styles->addElementStyle(self::TAG_HEXAGON)->shape(Shape::hexagon());
        $styles->addElementStyle(self::TAG_INTERNAL)->background('#438DD5')->color('#ffffff');

        $client = new Client(
            new Credentials($this->apiKey, $this->apiSecret),
            new UrlMap('https://api.structurizr.com'),
            new \GuzzleHttp\Client(),
            new SymfonyRequestFactory(),
            $this->logger
        );
        $client->put($workspace);

        $output->writeln('Done!');

        return 0;
    }
}
