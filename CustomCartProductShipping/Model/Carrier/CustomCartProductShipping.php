<?php
namespace Perspective\CustomCartProductShipping\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;
/* Enable delivery method availability, only for orders with the Social attribute. */
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Cart;

class CustomCartProductShipping extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'customcartproductshipping';

    protected $_isFixed = true;

    protected $checkoutSession; 

    private ResultFactory $rateResultFactory;

    private MethodFactory $rateMethodFactory;

    /* Enable delivery method availability, only for orders with the Social attribute. */
    protected $productRepository;
    protected $cart;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        /* Enable delivery method availability, only for orders with the Social attribute. */
        ProductRepository $productRepository,
        Cart $cart,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->_scopeConfig = $scopeConfig;
        /* Enable delivery method availability, only for orders with the Social attribute. */
        $this->productRepository = $productRepository;
        $this->cart = $cart;

    }

    /**
     * Custom Shipping Rates Collector
     *
     * @param RateRequest $request
     * @return \Magento\Shipping\Model\Rate\Result|bool
     */
    public function getSocialAttributeValuesInCart()
    {   
        $socialAttributes = [];
        // Получаем все элементы корзины
        $quote = $this->cart->getQuote();
        $quoteItems = $quote->getAllVisibleItems();
        foreach ($quoteItems as $item) {
            $productId = $item->getProduct()->getId();
            // Получаем объект продукта по его ID
            $product = $this->productRepository->getById($productId);
            // Получаем объект атрибута "social"
            $socialAttribute = $product->getCustomAttribute('Social');
            if (!$socialAttribute)
            {
                return 0;
            }
            else {
                $socialAttributeValue = $socialAttribute->getValue();
                $socialAttributes[$productId] = $socialAttributeValue;
            }
        }
        return $socialAttributes;
    }

    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        /** @var Method $method */
        $method = $this->rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('name'));

        $shippingCost = (float) $this->getConfigData('shipping_cost');

// Cтоимость доставки на процент, зависящий от количества продуктов в заказе.
        $qty = $request->getPackageQty();

        if ($qty>=3 && $qty<=5)
        {
            $shippingCost = $shippingCost - ($shippingCost * 0.5);
        }
        elseif ($qty>=6 && $qty<=10)
        {
            $shippingCost = $shippingCost - ($shippingCost * 0.8);
        }
        elseif ($qty>10)
        {
            $shippingCost = $shippingCost * 0;
        }


        $method->setPrice($shippingCost);
        $method->setCost($shippingCost);

        $attribute = $this->getSocialAttributeValuesInCart();

        if (!$attribute) {
            return false;
        }


        /** @var Result $result */
        $result = $this->rateResultFactory->create();
        $result->append($method);

        return $result;
    }
    

    public function getAllowedMethods(): array
    {
        return [$this->_code => $this->getConfigData('name')];
    }
}
