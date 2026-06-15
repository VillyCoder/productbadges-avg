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

        return parent::renderForm();
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
