$(document).ready(function () {
    // Buscador de productos en el panel de asignación
    $('#badge-product-search').on('input', function () {
        var q = $(this).val().toLowerCase();
        $('input[name="product_ids[]"]').each(function () {
            var $row = $(this).closest('tr');
            $row.toggle($row.find('td:last').text().toLowerCase().indexOf(q) !== -1);
        });
    });

    // Marcar/desmarcar todos los productos
    $('#check-all-products').on('change', function () {
        $('input[name="product_ids[]"]').prop('checked', $(this).is(':checked'));
    });

    $('.colorpicker-input').each(function () {
        var $text   = $(this);
        var $picker = $('<input>', {
            type:  'color',
            value: /^#[0-9a-fA-F]{6}$/.test($text.val()) ? $text.val() : '#000000',
            css:   { marginLeft: '8px', verticalAlign: 'middle', cursor: 'pointer', width: '40px', height: '30px' }
        });

        // Selector → campo de texto
        $picker.on('input change', function () {
            $text.val($(this).val());
        });

        // Campo de texto → selector (solo si el valor es un hex válido)
        $text.on('input change', function () {
            if (/^#[0-9a-fA-F]{6}$/.test($(this).val())) {
                $picker.val($(this).val());
            }
        });

        $text.after($picker);
    });
});
