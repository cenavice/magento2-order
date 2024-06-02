<?php
namespace Cenavice\Order\Setup\Patch\Data;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;

class AddPaymentReceivedOrderStatus implements DataPatchInterface, PatchVersionInterface
{
    /**
     * Status Factory
     *
     * @var StatusFactory
     */
    private $statusFactory;

    /**
     * Status Resource Factory
     *
     * @var StatusResourceFactory
     */
    private $statusResourceFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param StatusFactory $statusFactory
     * @param StatusResourceFactory $statusResourceFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        StatusFactory $statusFactory,
        StatusResourceFactory $statusResourceFactory,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->statusFactory = $statusFactory;
        $this->statusResourceFactory = $statusResourceFactory;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Install order status data
     *
     * @throws \Exception
     */
    public function apply()
    {
        $this->addPaymentReceivedStatus();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getVersion()
    {
        return '1.0.1';
    }

    private function addPaymentReceivedStatus()
    {
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();
        /** @var Status $status */
        $paymentReceivedStatus = $this->statusFactory->create();
        $paymentReceivedStatus->setData([
            'status' => 'payment_received',
            'label' => 'Payment Received',
        ]);
        try {
            $statusResource->save($paymentReceivedStatus);
            $paymentReceivedStatus->assignState(Order::STATE_PROCESSING, false, true);
        } catch (AlreadyExistsException $exception) {
            return;
        }
    }
}
