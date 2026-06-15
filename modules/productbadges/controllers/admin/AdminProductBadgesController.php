<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminProductBadgesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table      = 'productbadges';
        $this->className  = 'ProductBadge';
        $this->identifier = 'id_productbadge';
        $this->lang       = true;

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        parent::__construct();

        $this->fields_list = [
            'id_productbadge' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'text' => [
                'title' => $this->l('Texto'),
            ],
            'position' => [
                'title' => $this->l('Posición'),
            ],
            'active' => [
                'title'  => $this->l('Activo'),
                'active' => 'status',
                'type'   => 'bool',
                'align'  => 'center',
                'class'  => 'fixed-width-sm',
            ],
        ];
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAssignProducts')) {
            $id_badge    = (int) Tools::getValue('id_productbadge');
            $product_ids = Tools::getValue('product_ids');

            if (!$id_badge) {
                $this->errors[] = $this->l('Badge no válido.');
                return false;
            }

            // Eliminar asignaciones actuales de este badge
            Db::getInstance()->execute(
                'DELETE FROM `' . _DB_PREFIX_ . 'productbadges_product`
                WHERE id_productbadge = ' . $id_badge
            );

            // Insertar las seleccionadas con INSERT IGNORE para evitar duplicados
            if (is_array($product_ids) && !empty($product_ids)) {
                foreach ($product_ids as $id_product) {
                    $id_product = (int) $id_product;
                    if ($id_product > 0) {
                        Db::getInstance()->execute(
                            'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'productbadges_product`
                            (id_productbadge, id_product)
                            VALUES (' . $id_badge . ', ' . $id_product . ')'
                        );
                    }
                }
            }

            $this->confirmations[] = $this->l('Asignación de productos guardada correctamente.');
            Tools::redirectAdmin(
                self::$currentIndex
                . '&id_productbadge=' . $id_badge
                . '&updateproductbadges&token=' . $this->token
            );
        }

        return parent::postProcess();
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Badge'),
                'icon'  => 'icon-tag',
            ],
            'input' => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Texto'),
                    'name'     => 'text',
                    'lang'     => true,
                    'required' => true,
                    'col'      => 4,
                    'hint'     => $this->l('Texto visible en la badge. Ej: NUEVO, OFERTA.'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Color de fondo'),
                    'name'     => 'bg_color',
                    'class'    => 'colorpicker-input',
                    'required' => true,
                    'col'      => 2,
                    'hint'     => $this->l('Formato hexadecimal. Ej: #ff0000'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Color de texto'),
                    'name'     => 'text_color',
                    'class'    => 'colorpicker-input',
                    'required' => true,
                    'col'      => 2,
                    'hint'     => $this->l('Formato hexadecimal. Ej: #ffffff'),
                ],
                [
                    'type'     => 'select',
                    'label'   => $this->l('Posición'),
                    'name'    => 'position',
                    'required' => true,
                    'options' => [
                        'query' => [
                            ['id' => 'top-left',  'name' => $this->l('Superior izquierda')],
                            ['id' => 'top-right', 'name' => $this->l('Superior derecha')],
                        ],
                        'id'   => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type'    => 'switch',
                    'label'   => $this->l('Activo'),
                    'name'    => 'active',
                    'is_bool' => true,
                    'values'  => [
                        ['id' => 'active_on',  'value' => 1, 'label' => $this->l('Sí')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Guardar'),
            ],
        ];

        $output = parent::renderForm();

        // La sección de asignación solo aparece al editar, no al crear
        if ($this->object->id) {
            $output .= $this->renderAssignForm();
        }

        return $output;
    }

    private function renderAssignForm()
    {
        $id_lang  = (int) $this->context->language->id_lang;
        $id_badge = (int) $this->object->id;

        $products = Db::getInstance()->executeS(
            'SELECT p.id_product, pl.name
            FROM `' . _DB_PREFIX_ . 'product` p
            INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                ON p.id_product = pl.id_product AND pl.id_lang = ' . $id_lang . '
            ORDER BY pl.name ASC'
        );

        $assigned = Db::getInstance()->executeS(
            'SELECT id_product FROM `' . _DB_PREFIX_ . 'productbadges_product`
            WHERE id_productbadge = ' . $id_badge
        );

        $assigned_ids = array_column($assigned, 'id_product');

        $this->context->smarty->assign([
            'badge_id'      => $id_badge,
            'products'      => $products,
            'assigned_ids'  => $assigned_ids,
            'assign_token'  => $this->token,
            'current_index' => self::$currentIndex,
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/assign_products.tpl'
        );
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addJS($this->module->getPathUri() . 'views/js/admin.js');
    }

    public function processSave()
    {
        $bg_color   = Tools::getValue('bg_color');
        $text_color = Tools::getValue('text_color');
        $position   = Tools::getValue('position');

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $bg_color)) {
            $this->errors[] = $this->l('El color de fondo no es válido. Usa formato hexadecimal (#RRGGBB).');
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $text_color)) {
            $this->errors[] = $this->l('El color de texto no es válido. Usa formato hexadecimal (#RRGGBB).');
        }

        if (!in_array($position, ['top-left', 'top-right'], true)) {
            $this->errors[] = $this->l('La posición indicada no es válida.');
        }

        if (count($this->errors)) {
            return false;
        }

        return parent::processSave();
    }
}
