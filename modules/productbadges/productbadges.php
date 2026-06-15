<?php
//Seguridad para no ejecutar directamente el php.
if (!defined('_PS_VERSION_')) {
    exit;
}

class Productbadges extends Module
{
    public function __construct()
    {
        $this->name = 'productbadges'; 
        $this->tab = 'front_office_features'; //Categoria donde aparece el modulo en el backoffice.
        $this->version = '1.0.0';
        $this->author = 'AVG';
        $this->need_instance = 0; //Presta no instancia el modulo en cada pagina si no esta instalado, ahorramos recursos.
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '1.7.8.99',
        ]; 
        $this->bootstrap = true; //Usamos bootstrap requerido en el doc de la prueba.

        parent::__construct();

        $this->displayName = $this->l('Product Badges');
        $this->description  = $this->l('Gestiona etiquetas visuales reutilizables para los productos del catálogo.');
        $this->confirmUninstall = $this->l('¿Deseas desinstalar el módulo? Se eliminarán todas las badges y sus asignaciones.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayProductFlags')
            && $this->registerHook('displayAfterProductThumbs')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->installTab()
            && $this->installSql();
    }

    public function uninstall()
    {
        return $this->uninstallTab()
            && $this->uninstallSql()
            && parent::uninstall();
    }

    // -------------------------------------------------------------------------
    // Tab (pestaña en el menú del Back Office)
    // -------------------------------------------------------------------------

    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminProductBadges';
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->module = $this->name;

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Product Badges';
        }

        return $tab->add();
    }

    private function uninstallTab()
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminProductBadges');

        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // SQL
    // -------------------------------------------------------------------------

    private function installSql()
    {
        $queries = require dirname(__FILE__) . '/sql/install.php';
        
        foreach ($queries as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            } //Usamos API oficial de PS.
        }

        return true;
    }

    private function uninstallSql()
    {
        $queries = require dirname(__FILE__) . '/sql/uninstall.php';
        
        foreach ($queries as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Configuración del módulo (Back Office)
    // -------------------------------------------------------------------------

    public function getContent()
    {
        // Se implementa en la siguiente fase
        return '';
    }

    // -------------------------------------------------------------------------
    // Hooks frontend
    // -------------------------------------------------------------------------

    public function hookDisplayProductFlags($params)
    {
        // Muestra badges en listados de categoría, búsqueda y home
        // Se implementa en la fase de frontend
    }

    public function hookDisplayAfterProductThumbs($params)
    {
        // Muestra badges en la ficha de producto
        // Se implementa en la fase de frontend
    }

    public function hookActionFrontControllerSetMedia()
    {
        if (!(bool) Configuration::get('PRODUCTBADGES_ACTIVE')) {
            return;
        }

        $this->context->controller->registerStylesheet(
            'productbadges-css',
            'modules/' . $this->name . '/views/css/productbadges.css',
            ['media' => 'all', 'priority' => 200]
        );
    }
}
