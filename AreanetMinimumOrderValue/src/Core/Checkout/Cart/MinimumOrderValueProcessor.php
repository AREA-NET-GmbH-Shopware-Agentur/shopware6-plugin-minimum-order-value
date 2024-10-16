<?php declare(strict_types=1);

namespace AreanetMinimumOrderValue\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\Translation\TranslatorInterface;

class MinimumOrderValueProcessor implements CartProcessorInterface
{
    private SystemConfigService $systemConfigService;
    private TranslatorInterface $translator;

    public function __construct(SystemConfigService $systemConfigService, TranslatorInterface $translator) {
        $this->systemConfigService = $systemConfigService;
        $this->translator = $translator;
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void {
        $taxStatus = $this->systemConfigService->get('AreanetMinimumOrderValue.config.tax', null);
        $minimumOrderValue = $this->systemConfigService->get('AreanetMinimumOrderValue.config.minimumOrderValue', null);

        if($taxStatus == "netto") {
            $minimumOrder = ($minimumOrderValue - $toCalculate->getPrice()->getNetPrice()) > 0 ? ($minimumOrderValue - $toCalculate->getPrice()->getNetPrice()) : 0;
        } else {
            $minimumOrder = ($minimumOrderValue - $toCalculate->getPrice()->getTotalPrice()) > 0 ? ($minimumOrderValue - $toCalculate->getPrice()->getTotalPrice()) : 0;
        }

        if($minimumOrder && count($toCalculate->getLineItems())) {
            $minimumOrderValueItem = new LineItem('minimumOrderValue', 'minimumOrderValue', null, 1);
            $minimumOrderValueItem->setLabel($this->translator->trans('areanet-minimum-order-value.minimum-order-fee'));
            $minimumOrderValueItem->setGood(false);
            $minimumOrderValueItem->setRemovable(false);
            $minimumOrderValueItem->setPrice(new CalculatedPrice(
                $minimumOrder,
                $minimumOrder,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
            ));

            $toCalculate->addLineItems(new LineItemCollection([$minimumOrderValueItem]));
        }
    }
}