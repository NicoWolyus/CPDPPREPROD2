<?php
/**
 * 2008 - 2019 (c) Prestablog
 *
 * MODULE PrestaBlog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2
 */

include_once(_PS_MODULE_DIR_.'prestablog/prestablog.php');
include_once(_PS_MODULE_DIR_.'prestablog/class/news.class.php');
include_once(_PS_MODULE_DIR_.'prestablog/class/categories.class.php');

class PrestaBlogRssModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    // public $display_column_left = true;
    // public $display_column_right = true;
    public $display_header = false;
    public $display_footer = false;

    public function init()
    {
        parent::init();
        if (Tools::getValue('rss') && !CategoriesClass::isCategorieValide((int)Tools::getValue('rss'))) {
            Tools::redirect('index.php?controller=404');
        } else {
            header('Content-type: application/xml; charset=utf-8');
            echo '<?xml version="1.0" encoding="UTF-8"?>';
        }
    }

    public function display()
    {

        $news = NewsClass::getListe(
            (int)$this->context->cookie->id_lang,
            1,
            0,
            null,
            null,
            'n.`date`',
            'desc',
            null,
            Date('Y-m-d H:i:s'),
            (Tools::getValue('rss') ? (int)Tools::getValue('rss') : null),
            1,
            (int)Configuration::get('prestablog_rss_title_length'),
            (int)Configuration::get('prestablog_rss_intro_length')
        );

        $prestablog = new PrestaBlog();
        $prestablog->initLangueModule((int)$this->context->cookie->id_lang);

        $rsscatitem = '';
        if (Tools::getValue('rss')) {
            $rsscatitem = '<category><![CDATA[';
            $rsscatitem .= CategoriesClass::getCategoriesName(
                (int)$this->context->cookie->id_lang,
                (int)Tools::getValue('rss')
            );
            $rsscatitem .= ']]></category>';
        }

        $out_flux = '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
            <channel>
                <title><![CDATA['.$prestablog->rss_langue['channel_title'].']]></title>
                <link>'.Tools::getShopDomainSsl(true).__PS_BASE_URI__.'</link>
                <atom:link
                    href="'.PrestaBlog::prestablogUrl(array('rss' => Tools::getValue('rss'))).'"
                    rel="self"
                    type="application/rss+xml"
                />
                <description>RSS</description>
                <pubDate>'.date('r').'</pubDate>
                '.$rsscatitem.'
                <image>
                    <url>'.Tools::getShopDomainSsl(true).__PS_BASE_URI__.'img/logo.png</url>
                    <title><![CDATA['.$prestablog->rss_langue['channel_title'].']]></title>
                    <link>'.Tools::getShopDomainSsl(true).__PS_BASE_URI__.'</link>
                </image>';

        if (count($news) > 0) {
            foreach ($news as $news_item) {
                $out_flux .= '<item>
                    <guid>'.PrestaBlog::prestablogUrl(array(
                        'id'      => $news_item['id_prestablog_news'],
                        'seo'     => $news_item['link_rewrite'],
                        'titre'  => $news_item['title']
                    )).'</guid>
                    <title><![CDATA['.$news_item['title'].']]></title>
                    <pubDate>'.date('r', strtotime($news_item['date'])).'</pubDate>';

                if (count($news_item['categories']) > 0) {
                    foreach ($news_item['categories'] as $val_cat) {
                        $out_flux .= '<category><![CDATA['.$val_cat['title'].']]></category>';
                    }
                }

                $out_flux .= '
                    <link>'.PrestaBlog::prestablogUrl(array(
                        'id'      => $news_item['id_prestablog_news'],
                        'seo'     => $news_item['link_rewrite'],
                        'titre'      => $news_item['title']
                    )).'</link>
                    <description xml:space="preserve"><![CDATA['.($news_item['paragraph_crop']).']]></description>
                    </item>';
            }
        }
        $out_flux .= '</channel>
            </rss>';
        echo $out_flux;
    }
}
