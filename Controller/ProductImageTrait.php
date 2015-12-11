<?php

namespace Sitemap\Controller;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Thelia\Model\Map\ProductI18nTableMap;
use Thelia\Model\Map\ProductImageTableMap;
use Thelia\Model\Map\ProductTableMap;
use Thelia\Model\Map\RewritingUrlTableMap;
use Thelia\Model\RewritingUrl;
use Thelia\Model\RewritingUrlQuery;
use Thelia\Tools\URL;

/**
 * Class ProductImageTrait
 * @package Sitemap\Controller
 * @author Etienne Perriere <eperriere@openstudio.fr>
 */
trait ProductImageTrait
{
    protected function setSitemapProductImages(&$sitemap, $locale)
    {
        // Prepare query - get products URL
        $query = RewritingUrlQuery::create()
            ->filterByView('product')
            ->filterByRedirected(null)
            ->filterByViewLocale($locale);

        // Join with visible products
        self::addJoinProductI18n($query);

        // Get products title & image file name
        $query->withColumn(ProductI18nTableMap::TITLE, 'PRODUCT_TITLE');
        $query->addDescendingOrderByColumn(ProductImageTableMap::POSITION);
        $query->addGroupByColumn(RewritingUrlTableMap::VIEW_ID);
        $query->withColumn(ProductImageTableMap::FILE, 'PRODUCT_FILE');

        // Execute query
        $results = $query->find();

        // For each result, hydrate XML file
        /** @var RewritingUrl $result */
        foreach ($results as $result) {

            // Open new sitemap line & set product URL
            $sitemap[] = '
            <url>
                <loc>'.URL::getInstance()->absoluteUrl($result->getUrl()).'</loc>';

            // Generate image data
            $this->generateSitemapImage('product', $result->getVirtualColumn('PRODUCT_FILE'), $result->getVirtualColumn('PRODUCT_TITLE'), $sitemap);

            // Close product line
            $sitemap[] = '            </url>';
        }
    }

    /**
     * Join products and their URLs
     *
     * @param Criteria $query
     */
    protected function addJoinProductI18n(Criteria &$query)
    {
        // Join RewritingURL with Product to have only visible products
        $join = new Join();

        $join->addExplicitCondition(
            RewritingUrlTableMap::TABLE_NAME,
            'VIEW_ID',
            null,
            ProductTableMap::TABLE_NAME,
            'ID',
            null
        );

        $join->setJoinType(Criteria::INNER_JOIN);
        $query->addJoinObject($join, 'productJoin');

        $query->addJoinCondition('productJoin', ProductTableMap::VISIBLE, 1, Criteria::EQUAL, \PDO::PARAM_INT);

        // Join RewritingURL with ProductI18n to have product title for it's image
        $joinI18n = new Join();

        $joinI18n->addExplicitCondition(
            RewritingUrlTableMap::TABLE_NAME,
            'VIEW_ID',
            null,
            ProductI18nTableMap::TABLE_NAME,
            'ID',
            null
        );
        $joinI18n->addExplicitCondition(
            RewritingUrlTableMap::TABLE_NAME,
            'VIEW_LOCALE',
            null,
            ProductI18nTableMap::TABLE_NAME,
            'LOCALE',
            null
        );

        $joinI18n->setJoinType(Criteria::INNER_JOIN);
        $query->addJoinObject($joinI18n);


        // Join RewritingURL with ProductImage to have image file
        $joinImage = new Join();

        $joinImage->addExplicitCondition(
            RewritingUrlTableMap::TABLE_NAME,
            'VIEW_ID',
            null,
            ProductImageTableMap::TABLE_NAME,
            'PRODUCT_ID',
            null
        );

        $joinImage->setJoinType(Criteria::INNER_JOIN);
        $query->addJoinObject($joinImage, 'productImageJoin');

        $query->addJoinCondition('productImageJoin', ProductImageTableMap::VISIBLE, 1, Criteria::EQUAL, \PDO::PARAM_INT);
    }
}