<div class="panel">
    <div class="panel-heading">
        <i class="icon-tags"></i> {l s='Asignar productos a esta badge' mod='productbadges'}
    </div>
    <form action="{$current_index|escape:'html':'UTF-8'}&amp;token={$assign_token|escape:'html':'UTF-8'}" method="post">
        <input type="hidden" name="submitAssignProducts" value="1">
        <input type="hidden" name="id_productbadge" value="{$badge_id|intval}">
        <div class="panel-body">
            <table class="table tableDnD">
                <thead>
                    <tr>
                        <th style="width:30px;">
                            <input type="checkbox" id="check-all-products">
                        </th>
                        <th>{l s='Producto' mod='productbadges'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $products as $product}
                    <tr>
                        <td>
                            <input type="checkbox"
                                   name="product_ids[]"
                                   value="{$product.id_product|intval}"
                                   {if in_array($product.id_product, $assigned_ids)}checked="checked"{/if}>
                        </td>
                        <td>{$product.name|escape:'html':'UTF-8'}</td>
                    </tr>
                    {foreachelse}
                    <tr>
                        <td colspan="2">{l s='No hay productos disponibles.' mod='productbadges'}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        <div class="panel-footer">
            <button type="submit" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Guardar asignación' mod='productbadges'}
            </button>
        </div>
    </form>
</div>
