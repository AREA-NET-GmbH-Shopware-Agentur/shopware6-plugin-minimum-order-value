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
        $calculator = new MinimumOrderValueCalculator($this->systemConfigService);

        $dummyLineItem = new LineItem('minimumOrderValue', 'minimumOrderValue');
        $calculator->calculate($dummyLineItem, $toCalculate, $context);

        if ($dummyLineItem->getPrice() && $dummyLineItem->getPrice()->getUnitPrice() > 0 && count($toCalculate->getLineItems())) {
            $existingMinimumOrderItemOriginal = $original->getLineItems()->filter(function (LineItem $item) {
                return $item->getType() === 'minimumOrderValue';
            });

            $existingMinimumOrderItemToCalculate = $toCalculate->getLineItems()->filter(function (LineItem $item) {
                return $item->getType() === 'minimumOrderValue';
            });

            if (($behavior->isRecalculation() && $existingMinimumOrderItemOriginal->count() === 0 && $existingMinimumOrderItemToCalculate->count() === 0) || (!$behavior->isRecalculation() && $existingMinimumOrderItemToCalculate->count() === 0)) {
                $minimumOrderValueItem = new LineItem('minimumOrderValue', 'minimumOrderValue', null, 1);
                $minimumOrderValueItem->setLabel($this->translator->trans('areanet-minimum-order-value.minimum-order-fee'));
                $minimumOrderValueItem->setGood(false);
                $minimumOrderValueItem->setRemovable(false);
                $minimumOrderValueItem->setStackable(true);

                $calculator->calculate($minimumOrderValueItem, $toCalculate, $context);

                $toCalculate->addLineItems(new LineItemCollection([$minimumOrderValueItem]));
            }
        }
    }
}