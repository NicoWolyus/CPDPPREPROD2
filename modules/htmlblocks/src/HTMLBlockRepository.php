<?php

class HTMLBlockRepository
{
    private $db;
    private $shop;
    private $db_prefix;
    
    public function __construct(Db $db, Shop $shop)
    {
        $this->db = $db;
        $this->shop = $shop;
        $this->db_prefix = $db->getPrefix();
    }
    
    public function createTables()
    {
        $engine = _MYSQL_ENGINE_;
        $success = true;
        $this->dropTables();
        
        $queries = [
           "CREATE TABLE IF NOT EXISTS `{$this->db_prefix}html_block`(
          `id_html_block` int(10) unsigned NOT NULL auto_increment,
          `id_hook` int(1) unsigned DEFAULT NULL,
          `position` int(10) unsigned NOT NULL default '0',
          `name` varchar(40) NOT NULL default '',
          PRIMARY KEY (`id_html_block`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
           "CREATE TABLE IF NOT EXISTS `{$this->db_prefix}html_block_lang`(
          `id_html_block` int(10) unsigned NOT NULL,
          `id_lang` int(10) unsigned NOT NULL,
          `content` text NOT NULL default '',
          PRIMARY KEY (`id_html_block`, `id_lang`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
           "CREATE TABLE IF NOT EXISTS `{$this->db_prefix}html_block_shop` (
          `id_html_block` int(10) unsigned NOT NULL auto_increment,
          `id_shop` int(10) unsigned NOT NULL,
          PRIMARY KEY (`id_html_block`, `id_shop`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
        ];
        
        foreach ($queries as $query) {
            $success &= $this->db->execute($query);
        }
        
        return $success;
    }
    
    public function dropTables()
    {
        $sql = "DROP TABLE IF EXISTS
      `{$this->db_prefix}html_block`,
      `{$this->db_prefix}html_block_lang`,
      `{$this->db_prefix}html_block_shop`";
        
        return $this->db->execute($sql);
    }
    
    public function getCMSBlocksSortedByHook($id_shop = null, $id_lang = null)
    {
        $id_lang = (int)(($id_lang) ?: Context::getContext()->language->id);
        $id_shop = (int)(($id_shop) ?: Context::getContext()->shop->id);
        
        $sql = 'SELECT
                bc.`id_html_block`,
                bc.`name` as block_name,
                bc.`id_hook`,
                h.`name` as hook_name,
                h.`title` as hook_title,
                h.`description` as hook_description,
                bc.`position`
            FROM `'.$this->db_prefix.'html_block` bc
                INNER JOIN `'.$this->db_prefix.'html_block_lang` bcl
                    ON (bc.`id_html_block` = bcl.`id_html_block`)
                LEFT JOIN `'.$this->db_prefix.'hook` h
                    ON (bc.`id_hook` = h.`id_hook`)
            WHERE bcl.`id_lang` = '.$id_lang.'
            ORDER BY bc.`position`';
        
        $blocks = $this->db->executeS($sql);
        
        // Prepare hook array
        $orderedBlocks = array();
        foreach ($blocks as $block) {
            if (!isset($orderedBlocks[$block['id_hook']])) {
                $id_hook = ($block['id_hook']) ?: 'not_hooked';
                $orderedBlocks[$id_hook] = array(
                   'id_hook' => $block['id_hook'],
                   'hook_name' => $block['hook_name'],
                   'hook_title' => $block['hook_title'],
                   'hook_description' => $block['hook_description'],
                   'blocks' => array(),
                );
            }
        }
        
        // Insert blocks into hook array
        foreach ($blocks as $block) {
            $id_hook = ($block['id_hook']) ?: 'not_hooked';
            unset($block['id_hook']);
            unset($block['hook_name']);
            unset($block['hook_title']);
            unset($block['hook_description']);
            $orderedBlocks[$id_hook]['blocks'][] = $block;
        }
        
        return $orderedBlocks;
    }
    
    public function getDisplayHooksForHelper()
    {
        $sql = "SELECT h.id_hook as id, h.name as name
                FROM {$this->db_prefix}hook h
                WHERE (lower(h.`name`) LIKE 'display%')
                ORDER BY h.name ASC
            ";
        $hooks = $this->db->executeS($sql);
        
        foreach ($hooks as $key => $hook) {
            if (preg_match('/admin/i', $hook['name'])
               || preg_match('/backoffice/i', $hook['name'])) {
                unset($hooks[$key]);
            }
        }
        
        return $hooks;
    }
    
    public function getByIdHook($id_hook)
    {
        $id_hook = (int)$id_hook;
        
        $sql = "SELECT cb.`id_html_block`
                    FROM {$this->db_prefix}html_block cb
                    WHERE `id_hook` = $id_hook
                ";
        $ids = $this->db->executeS($sql);
        
        $cmsBlock = array();
        foreach ($ids as $id) {
            $cmsBlock[] = new HTMLBlock((int)$id['id_html_block']);
        }
        
        return $cmsBlock;
    }
    
    public function getCountByIdHook($id_hook)
    {
        $id_hook = (int)$id_hook;
        
        $sql = "SELECT COUNT(*) FROM {$this->db_prefix}html_block
                    WHERE `id_hook` = $id_hook";
        
        return $this->db->getValue($sql);
    }
    
    //ignore
    public function installFixtures()
    {
        $success = true;
        $id_hook = (int)Hook::getIdByName('displayFooter');
        
        $queries = [
           'INSERT INTO `'.$this->db_prefix.'html_block` (`id_html_block`, `id_hook`, `position`, `name`) VALUES
                (1, '.$id_hook.', 0, \'Block I\'),
                (2, '.$id_hook.', 1, \'Block II\')',
        ];
        
        foreach (Language::getLanguages(
           true,
           Context::getContext()->shop->id
        ) as $lang) {
            $queries[] = 'INSERT INTO `'.$this->db_prefix.'html_block_lang` (`id_html_block`, `id_lang`, `content`) VALUES
                (1, '.$lang['id_lang'].', \'<p>This is default HTML content of this block.</p>\'),
                (2, '.$lang['id_lang'].', \'<p>This is another HTML content of this block.</p>\')';
        }
        
        foreach ($queries as $query) {
            $success &= $this->db->execute($query);
        }
        
        return $success;
    }
    
    //ignore
    public function createOrUpdateHTMLBlock(&$id_html_block, $id_hook, $name)
    {
        $success = true;
        
        // Validate position
        $position = (int)Tools::getValue('position', -1);
        $maxPosition = is_int(
           $this->getCountByIdHook($id_hook)
        ) ? $this->getCountByIdHook($id_hook) : 0;
        if ($position < 0 || $position > $maxPosition) {
            $position = $maxPosition;
        }
        
        // Create new block
        if (empty($id_html_block)) {
            $query = 'INSERT INTO `'.$this->db_prefix.'html_block` (`id_hook`, `position`, `name`) VALUES
                ('.$id_hook.', '.$position.', \''.$name.'\')';
            
            $success &= $this->db->execute($query);
            $id_html_block = (int)$this->db->Insert_ID();
            
            if (!empty($success) && !empty($id_html_block)) {
                $languages = Language::getLanguages(
                   true,
                   Context::getContext()->shop->id
                );
                if (!empty($languages)) {
                    $query = 'INSERT INTO `'.$this->db_prefix.'html_block_lang` (`id_html_block`, `id_lang`, `content` ) VALUES ';
                    
                    foreach ($languages as $lang) {
                        
                        Db::getInstance()->insert(
                           'html_block_lang',
                           array(
                              'id_html_block' => $id_html_block,
                              'id_lang' => pSQL((int)$lang['id_lang']),
                              'content' => pSQL(
                                 Tools::getValue(
                                    'content_'.(int)$lang['id_lang']
                                 ),
                                 true
                              ),
                           )
                        );
                        
                        //$query .= '(' . $id_html_block . ',' . (int)$lang['id_lang'] . ',' . Tools::getValue('content_' . (int)$lang['id_lang']) . '),';
                    }
                    
                    //$success &= $this->db->execute(rtrim($query, ','));
                }
            }
        } // Update existing block
        else {
            if (!empty($name)) {
                $query = 'UPDATE `'.$this->db_prefix.'html_block`
                        SET `name` = \''.$name.'\',
                            `id_hook` = '.$id_hook.',
                            `position` = '.$position.'
                        WHERE `id_html_block` = '.$id_html_block;
                $success &= $this->db->execute($query);
            }
            
            $languages = Language::getLanguages(
               true,
               Context::getContext()->shop->id
            );
            
            if (!empty($languages)) {
                foreach ($languages as $lang) {
                    
                    Db::getInstance()->update(
                       'html_block_lang',
                       array(
                          'content' => pSQL(
                             Tools::getValue(
                                'content_'.(int)$lang['id_lang']
                             ),
                             true
                          ),
                       ),
                       'id_html_block ='.$id_html_block.' AND id_lang = '.(int)$lang['id_lang']
                    
                    );

//                    $query = 'UPDATE `'.$this->db_prefix.'html_block_lang`
//                            SET `content` = '.Tools::getValue(
//                          'content_'.(int)$lang['id_lang']
//                       ).'
//                            WHERE `id_html_block` = '.$id_html_block.' AND `id_lang` = '.(int)$lang['id_lang'];
//                    $success &= $this->db->execute($query);
                }
            }
        }
        
        return $success;
    }
}
