<?php
//Seguridad para no ejecutar directamente el php.
if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductBadge extends ObjectModel
{
    public $bg_color;
    public $text_color;
    public $position;
    public $active;
    public $text; // campo multilenguaje, se almacena en productbadges_lang

    public static $definition = [
        'table'     => 'productbadges',
        'primary'   => 'id_productbadge',
        'multilang' => true,
        'fields'    => [
            'bg_color'   => ['type' => self::TYPE_STRING, 'validate' => 'isColor',       'size' => 7,  'required' => true],
            'text_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor',       'size' => 7,  'required' => true],
            'position'   => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 20, 'required' => true],
            'active'     => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'],
            // campo multilenguaje
            'text'       => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml',   'size' => 64, 'required' => true, 'lang' => true],
        ],
    ];
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
            && $this->registerHook('actionProductFlagsModifier')
            && $this->registerHook('displayAfterProductThumbs')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('displayHeader')
            && $this->installTab()
            && $this->installSql()
            && $this->installConfig();
    }

    public function uninstall()
    {
        return $this->uninstallTab()
            && $this->uninstallSql()
            && $this->deleteConfig()
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

    private function installConfig()
    {
        return Configuration::updateValue('PRODUCTBADGES_ACTIVE', 1)
            && Configuration::updateValue('PRODUCTBADGES_SHOW_LISTING', 1)
            && Configuration::updateValue('PRODUCTBADGES_SHOW_PRODUCT', 1)
            && Configuration::updateValue('PRODUCTBADGES_MAX_BADGES', 3);
    }

    private function deleteConfig()
    {
        return Configuration::deleteByName('PRODUCTBADGES_ACTIVE')
            && Configuration::deleteByName('PRODUCTBADGES_SHOW_LISTING')
            && Configuration::deleteByName('PRODUCTBADGES_SHOW_PRODUCT')
            && Configuration::deleteByName('PRODUCTBADGES_MAX_BADGES');
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit_productbadges')) {
            $active       = (int) Tools::getValue('PRODUCTBADGES_ACTIVE');
            $show_listing = (int) Tools::getValue('PRODUCTBADGES_SHOW_LISTING');
            $show_product = (int) Tools::getValue('PRODUCTBADGES_SHOW_PRODUCT');
            $max_badges   = (int) Tools::getValue('PRODUCTBADGES_MAX_BADGES');

            if ($max_badges < 1 || $max_badges > 10) {
                $output .= $this->displayError($this->l('El número máximo de badges debe estar entre 1 y 10.'));
            } else {
                Configuration::updateValue('PRODUCTBADGES_ACTIVE', $active);
                Configuration::updateValue('PRODUCTBADGES_SHOW_LISTING', $show_listing);
                Configuration::updateValue('PRODUCTBADGES_SHOW_PRODUCT', $show_product);
                Configuration::updateValue('PRODUCTBADGES_MAX_BADGES', $max_badges);
                $output .= $this->displayConfirmation($this->l('Configuración guardada correctamente.'));
            }
        }

        return $output . $this->renderConfigForm();
    }

    private function renderConfigForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuración'),
                    'icon'  => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Módulo activo'),
                        'name'    => 'PRODUCTBADGES_ACTIVE',
                        'is_bool' => true,
                        'values'  => [
                            ['id' => 'active_on',  'value' => 1, 'label' => $this->l('Sí')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Mostrar en listados'),
                        'name'    => 'PRODUCTBADGES_SHOW_LISTING',
                        'is_bool' => true,
                        'values'  => [
                            ['id' => 'listing_on',  'value' => 1, 'label' => $this->l('Sí')],
                            ['id' => 'listing_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Mostrar en ficha de producto'),
                        'name'    => 'PRODUCTBADGES_SHOW_PRODUCT',
                        'is_bool' => true,
                        'values'  => [
                            ['id' => 'product_on',  'value' => 1, 'label' => $this->l('Sí')],
                            ['id' => 'product_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Máximo de badges por producto'),
                        'name'  => 'PRODUCTBADGES_MAX_BADGES',
                        'class' => 'fixed-width-xs',
                        'hint'  => $this->l('Número entre 1 y 10.'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Guardar'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module          = $this;
        $helper->name_controller = $this->name;
        $helper->token           = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex    = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language    = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->submit_action   = 'submit_productbadges';

        $helper->fields_value['PRODUCTBADGES_ACTIVE']       = (int) Configuration::get('PRODUCTBADGES_ACTIVE');
        $helper->fields_value['PRODUCTBADGES_SHOW_LISTING'] = (int) Configuration::get('PRODUCTBADGES_SHOW_LISTING');
        $helper->fields_value['PRODUCTBADGES_SHOW_PRODUCT'] = (int) Configuration::get('PRODUCTBADGES_SHOW_PRODUCT');
        $helper->fields_value['PRODUCTBADGES_MAX_BADGES']   = (int) Configuration::get('PRODUCTBADGES_MAX_BADGES');

        return $helper->generateForm([$fields_form]);
    }

    // -------------------------------------------------------------------------
    // Hooks frontend
    // -------------------------------------------------------------------------

    public function hookDisplayProductFlags($params)
    {
        // Classic PS 1.7.6+ usa actionProductFlagsModifier para inyectar flags.
        // Retornamos vacío para evitar doble renderizado en temas que invocan ambos hooks.
        return;
    }

    public function hookDisplayAfterProductThumbs($params)
    {
        // La ficha de producto se gestiona via hookActionProductFlagsModifier
        // que inyecta las badges en el sistema nativo de flags del tema Classic.
        return;
    }

    private function getBadgesForProduct($id_product)
    {
        $id_lang = (int) $this->context->language->id;
        $max     = max(1, (int) Configuration::get('PRODUCTBADGES_MAX_BADGES'));

        return Db::getInstance()->executeS(
            'SELECT b.id_productbadge, b.bg_color, b.text_color, b.position, bl.text
            FROM `' . _DB_PREFIX_ . 'productbadges` b
            INNER JOIN `' . _DB_PREFIX_ . 'productbadges_lang` bl
                ON b.id_productbadge = bl.id_productbadge
                AND bl.id_lang = ' . $id_lang . '
            INNER JOIN `' . _DB_PREFIX_ . 'productbadges_product` bp
                ON b.id_productbadge = bp.id_productbadge
            WHERE bp.id_product = ' . (int) $id_product . '
                AND b.active = 1
            LIMIT ' . $max
        );
    }

    public function hookActionProductFlagsModifier($params)
    {
        if (!(bool) Configuration::get('PRODUCTBADGES_ACTIVE')) {
            return;
        }

        $is_product_page = isset($this->context->controller->php_self)
                           && $this->context->controller->php_self === 'product';

        if ($is_product_page) {
            if (!(bool) Configuration::get('PRODUCTBADGES_SHOW_PRODUCT')) {
                return;
            }
        } else {
            if (!(bool) Configuration::get('PRODUCTBADGES_SHOW_LISTING')) {
                return;
            }
        }

        $id_product = (int) ($params['product']['id_product'] ?? 0);
        if (!$id_product) {
            return;
        }

        $badges = $this->getBadgesForProduct($id_product);
        foreach ($badges as $badge) {
            $id       = (int) $badge['id_productbadge'];
            $position = in_array($badge['position'], ['top-left', 'top-right'], true)
                ? $badge['position'] : 'top-left';

            $params['flags']['productbadge-' . $id] = [
                'type'  => 'productbadge badge-' . $id . ' productbadge--' . $position,
                'label' => $badge['text'],
            ];
        }
    }

    public function hookDisplayHeader()
    {
        if (!(bool) Configuration::get('PRODUCTBADGES_ACTIVE')) {
            return;
        }

        $badges = Db::getInstance()->executeS(
            'SELECT id_productbadge, bg_color, text_color
            FROM `' . _DB_PREFIX_ . 'productbadges`
            WHERE active = 1'
        );

        if (empty($badges)) {
            return;
        }

        $css = '<style>';
        foreach ($badges as $b) {
            $id  = (int) $b['id_productbadge'];
            $bg  = htmlspecialchars($b['bg_color'],   ENT_QUOTES, 'UTF-8');
            $col = htmlspecialchars($b['text_color'], ENT_QUOTES, 'UTF-8');
            // Selector más específico que el del tema Classic (.product-flags li.product-flag = 0,2,1)
            $css .= '.product-flags li.product-flag.badge-' . $id . '{background-color:' . $bg . ';color:' . $col . ';}';
        }
        $css .= '</style>';

        return $css;
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
