<?php
namespace Cenavice\Order\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateBonusOrder extends Command
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectmanager;

    /**
     * @var \Cenavice\Order\Helper\Data
     */
    protected $helperData;

    /**
     * Constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectmanager
     * @param \Cenavice\Order\Helper\Data $helperData
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Cenavice\Order\Helper\Data $helperData
    ) {
        // $this->objectmanager = $objectManager;
        $this->_objectmanager = $objectmanager;
        $this->helperData = $helperData;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('cenavice:order:create-bonus-order');
        $this->setDescription('This is my console command.');
        // $this->setDefinition(
        //     [
        //         new InputArgument(
        //             'main_order_entity_id',
        //             InputArgument::REQUIRED,
        //             'Main Order Entity ID'
        //         ),
        //     ]
        // );  


        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null|int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $state = $this->_objectmanager->get('Magento\Framework\App\State');
        try {
           $state->setAreaCode('adminhtml');            
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
           
        }

        $output->writeln('Creating bonus order ...');  
        try {
            $orderIncrementId = '000000005';
            $qty = 3;
            $this->helperData->createBonusOrder($orderIncrementId, $qty);
            $output->writeln('<info>Success Message.</info>');
        } catch (\Throwable $th) {
            $output->writeln('<error>An error encountered.</error>');
            $output->writeln('<error>'.__($th->getMessage()).'</error>');
            return 0;
        }

        return 1;
    }
}