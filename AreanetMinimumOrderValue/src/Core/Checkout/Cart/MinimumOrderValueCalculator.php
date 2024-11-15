<?php declare(strict_types=1);

namespace AreanetMinimumOrderValue\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class MinimumOrderValueCalculator
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService) {
        $this->systemConfigService = $systemConfigService;
    }

    public function calculate(LineItem $lineItem, Cart $toCalculate, SalesChannelContext $context): void {
        $taxStatus = $this->systemConfigService->get('AreanetMinimumOrderValue.config.tax', $context->getSalesChannel()->getId());
        $minimumOrderValue = $this->systemConfigService->get('AreanetMinimumOrderValue.config.minimumOrderValue', $context->getSalesChannel()->getId());
        $fixedPriceForMinimumOrderItem = $this->systemConfigService->get('AreanetMinimumOrderValue.config.fixedPriceForMinimumOrderItem', $context->getSalesChannel()->getId());

        if($fixedPriceForMinimumOrderItem) {
            if ($taxStatus == "netto") {
                $minimumOrder = ($minimumOrderValue - $toCalculate->getPrice()->getNetPrice()) > 0 ? $fixedPriceForMinimumOrderItem : 0;
            } else {
                $minimumOrder = ($minimumOrderValue - $toCalculate->getPrice()->getTotalPrice()) > 0 ? $fixedPriceForMinimumOrderItem : 0;
            }
        } else {
            if ($taxStatus == "netto") {
                $minimumOrder = ($minimumOrderValue - $toCalculate->getPrice()->getNetPrice()) > 0 ? ($minimumOrderValue - $toCalculate->getPrice()->getNetPrice()) : 0;
            } else {
                $minimumOrder = ($minimumOrderValue - $toCalculate->getPrice()->getTotalPrice()) > 0 ? ($minimumOrderValue - $toCalculate->getPrice()->getTotalPrice()) : 0;
            }
        }

        $lineItem->setPrice(new CalculatedPrice(
            $minimumOrder,
            $minimumOrder,
            new CalculatedTaxCollection(),
            new TaxRuleCollection()
        ));
    }
}
