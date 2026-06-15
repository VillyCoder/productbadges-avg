{foreach $badges as $badge}
<span class="productbadge productbadge--{$badge.position|escape:'html':'UTF-8'}"
      style="background-color:{$badge.bg_color|escape:'html':'UTF-8'};color:{$badge.text_color|escape:'html':'UTF-8'};">
    {$badge.text|escape:'html':'UTF-8'}
</span>
{/foreach}
