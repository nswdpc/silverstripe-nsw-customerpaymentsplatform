<div class="pricetag">
    <% if $DiscountedPrice %>
        <s class="original">
            <span class="symbol">$Price.Symbol</span>
            <strong class="main">$Price.Main</strong>
            <small class="fractional">$Price.Fractional</small>
            <span class="code">$Price.CurrencyCode</span>
        </s>
        <span class="discounted">$DiscountedPrice.Nice</span> <%t SilverShop/Includes/PriceTag.SAVE "Save" %>: <span class="savings">$DiscountedPrice.Savings</span>
    <% else %>
        <span class="original"><strong class="price">$Price.Nice</strong></span>
    <% end_if %>
    <% if $RecommendedPrice %><span>$RecommendedPrice.Nice</span><% end_if %>
</div>
