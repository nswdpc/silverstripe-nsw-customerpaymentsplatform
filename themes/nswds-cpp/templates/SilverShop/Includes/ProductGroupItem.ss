<div class="nsw-card">

    <div class="nsw-card__content">

        <h2 class="nsw-card__title">
            <a href="$Link" class="nsw-card__link">$Title</a>
        </h2>

        <% if $Abstract %>
            <p class="nsw-card__copy">
            $Abstract.XML
            </p>
        <% end_if %>

        <% if $Model %>
        <p class="nsw-card__copy">
            <strong><%t SilverShop\Page\Product.Model "Model" %>:</strong> $Model.XML
        </p>
        <% end_if %>

        <p class="nsw-card__copy">
            <strong>Price:</strong>
            <% if $PriceRange %>
                    <strong class="value">$PriceRange.Min.Nice</strong>
                    <% if $PriceRange.HasRange %>
                        - <strong class="value">$PriceRange.Max.Nice</strong>
                    <% end_if %>
                    <span class="currency">$Price.Currency</span>
                </div>
            <% else_if $Price %>
                    <strong class="value">$Price.Nice</strong> <span class="currency">$Price.Currency</span>
            <% end_if %>

        </p>

        <% if $View %>
            <p class="nsw-card__copy">
                <a href="$Link" class="nsw-card__link"">
                    <%t SilverShop\Page\Product.View "View Product" %>
                </a>
            </p>
        <% else %>

            <% if $canPurchase %>
                <p class="nsw-card__copy">
                    <a href="$addLink" class="nsw-button nsw-button--primary nsw-button--full-width">
                        <%t SilverShop\Page\Product.AddToCart "Add to Cart" %>
                        <%--
                        <% if $IsInCart %>
                            ($Item.Quantity)
                        <% end_if %>
                        --%>
                    </a>
                </p>
            <% end_if %>

        <% end_if %>

    </div>

    <% if $Image %>
    <div class="nsw-card__image-area">
        <img src="{$Image.ScaleWidth(720).URL}" class="nsw-card__image" alt="{$Image.Title.XML}">
    </div>
    <% end_if %>


</div>
