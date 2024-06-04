<?php
namespace Cenavice\Order\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    public $transaction;
    /**
     * @var \Magento\Catalog\Model\Product
     */
    public $productModel;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    public $orderFactory;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    public $orderRepository;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService $invoiceService
     */
    public $invoiceService;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    public $quoteFactory;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    public $quoteManagement;
    
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    public $customerRepository;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param \Magento\Catalog\Model\Product $productModel
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->transaction = $transaction;
        $this->productModel = $productModel;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->quoteFactory = $quoteFactory;
        $this->quoteManagement = $quoteManagement;
        $this->customerRepository = $customerRepository;
        parent::__construct($context);
    }

    public function createBonusOrder($orderIncrementId, $qty = null)
    {
        if ($qty == null) {
            $qty = $this->getBonusOrderItemQty();
        }
        
        $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);

        $quote = $this->quoteFactory->create();
        $quote->setStore($order->getStore());
        $customer = $this->customerRepository->getById($order->getCustomerId());
        $quote->setCurrency();
        $quote->assignCustomer($customer);

        foreach ($order->getAllItems() as $key => $item) {
            $product = $this->productModel->load($item->getProductId());
            $quote->addProduct(
                $product,
                $qty
            );

            $productItem = $quote->getItemByProduct($product);
            $productItem->setCustomPrice(0);
            $productItem->setOriginalCustomPrice(0);
        }
 
        //Set Address to quote
        $orderShippingAddress = $this->filterAddressData($order->getShippingAddress()->getData());
        $orderShippingAddress['weight'] = 0;
        $orderBillingAddress = $this->filterAddressData($order->getBillingAddress()->getData());
        $orderBillingAddress['weight'] = 0;

        $quote->getBillingAddress()->addData($orderBillingAddress);
        $quote->getShippingAddress()->addData($orderShippingAddress);
 
        // Collect Rates and Set Shipping & Payment Method
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
                        ->collectShippingRates();
        $shippingAddress->setShippingMethod('freeshipping_freeshipping');
        $quote->setPaymentMethod('free');
        $quote->setInventoryProcessed(false);
        $quote->setOrderType('bonus');
        $quote->save();
 
        // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => 'checkmo']);
 
        // Collect Totals & Save Quote
        $quote->collectTotals()->save();
 
        // Create Order From Quote
        $bonusOrder = $this->quoteManagement->submit($quote);
        
        $bonusOrder->setEmailSent(0);
        $bonusOrder->setOrderType('bonus');
        
        $bonusOrder->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
            ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);

        $bonusOrder->save();

        $this->invoiceBonusOrder($bonusOrder);
        
        if ($bonusOrder->getEntityId()) {
            $incrementId = $bonusOrder->getRealOrderId();
            $result['order_id'] = $bonusOrder->getRealOrderId();

            $this->orderRepository->save($order);
        } else {
            $result['order_id'] = null;
        }

        return $result;
    }

    public function filterAddressData($data)
    {
        $allowed = ['prefix', 'firstname', 'middlename', 'lastname', 'email', 'suffix', 'company', 'street', 'city', 'country_id', 'region', 'region_id', 'postcode', 'telephone', 'fax', 'vat_id'];
        $remove = [];

        foreach ($data as $key => $value)
            if (!in_array($key, $allowed))
                $remove[] = $key;

        foreach ($remove as $key)
            unset($data[$key]);

        return $data;
    }

    public function invoiceBonusOrder($order)
    {
        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->save();
            
            $transactionSave = 
                $this->transaction
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
            $transactionSave->save();
        }
    }

    public function getBonusOrderItemQty()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $bonusOrderItemQty = $this->scopeConfig->getValue('cenavice_order/bonus_order/bonus_order_item_qty', $storeScope);

        return $bonusOrderItemQty;
    }
}
