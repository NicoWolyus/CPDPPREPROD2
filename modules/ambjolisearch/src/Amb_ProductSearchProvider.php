<?php
/**
 *   AmbJoliSearch Module : Search for prestashop
 *
 *   @author    Ambris Informatique
 *   @copyright Copyright (c) 2013-2015 Ambris Informatique SARL
 *   @license   Commercial license
 *   @module     Advanced search (AmbJoliSearch)
 *   @file       ambjolisearch.php
 *   @subject    script principal pour gestion du module (install/config/hook)
 *   Support by mail: support@ambris.com
 */

use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchProviderInterface;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchResult;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrderFactory;
use Symfony\Component\Translation\TranslatorInterface;

class AmbProductSearchProvider implements ProductSearchProviderInterface
{
    private $module;

    public function __construct($module)
    {
        $this->module = $module;
        $this->context = Context::getContext();
        $this->translator = $module->getTranslator();
        $this->sortOrderFactory = new SortOrderFactory($this->translator);
    }

    public function runQuery(
        ProductSearchContext $context,
        ProductSearchQuery $query
    ) {

        $products = array();
        $count    = 0;

        if (($string = $query->getSearchString())) {
            if (!class_exists('AmbSearch')) {
                require_once _PS_ROOT_DIR_ . '/modules/ambjolisearch/classes/AmbSearch.php';
            }

            $searcher = new AmbSearch(false, $this->context, $this->module);
            $searcher->search($context->getIdLang(), $string, $query->getPage(), $query->getResultsPerPage(), $query->getSortOrder()->toLegacyOrderBy(), $query->getSortOrder()->toLegacyOrderWay());
            $products = $searcher->getResults();
            $count = $searcher->getTotal();
        }
        $result = new ProductSearchResult;
        $result->setProducts($products);
        $result->setTotalProductsCount($count);

        $result->setAvailableSortOrders(
            $this->sortOrderFactory->getDefaultSortOrders()
        );

        foreach ($result->getAvailableSortOrders() as $sortOrder) {
            if ($sortOrder->getField() == 'position') {
                $sortOrder->SetDirection('desc');
            }
        }

        if ($string) {
            $this->context->smarty->assign('categories', $result->categories = $searcher->getCategories());
        }

        return $result;
    }
}
