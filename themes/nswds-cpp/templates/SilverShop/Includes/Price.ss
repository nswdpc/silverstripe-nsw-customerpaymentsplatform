

<div class="price">

    <% include Icon Icon=sell %>

    <% if $PriceRange %>
        <strong class="value">$PriceRange.Min.Nice</strong>
        <% if $PriceRange.HasRange %>
            - <strong class="value">$PriceRange.Max.Nice</strong>
        <% end_if %>
        <span class="currency">$Price.Currency</span>
    <% else_if $Price %>
        <strong class="value">$Price.Nice</strong>
        <span class="currency">$Price.Currency</span>
    <% end_if %>

</div>
