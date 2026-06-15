<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'productbadges` (
    `id_productbadge` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `bg_color` varchar(7) NOT NULL DEFAULT \'#000000\',
    `text_color` varchar(7) NOT NULL DEFAULT \'#ffffff\',
    `position` enum(\'top-left\',\'top-right\') NOT NULL DEFAULT \'top-left\',
    `active` tinyint(1) unsigned NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_productbadge`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'productbadges_lang` (
    `id_productbadge` int(10) unsigned NOT NULL,
    `id_lang` int(10) unsigned NOT NULL,
    `text` varchar(64) NOT NULL DEFAULT \'\',
    PRIMARY KEY (`id_productbadge`, `id_lang`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'productbadges_product` (
    `id_productbadge` int(10) unsigned NOT NULL,
    `id_product` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id_productbadge`, `id_product`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

return $sql;
