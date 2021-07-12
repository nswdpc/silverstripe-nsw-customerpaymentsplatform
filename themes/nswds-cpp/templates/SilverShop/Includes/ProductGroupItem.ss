<div class="nsw-card">

    <div class="nsw-card__content">

        <h3 class="nsw-card__title">
            <a href="{$Link}" class="nsw-card__link">
            <% if $MenuTitle %>{$MenuTitle}<% else %>{$Title.XML}<% end_if %>
            </a>
        </h3>

        <p class="nsw-card__copy">

            <% if $Model %>
                <span><strong><%t SilverShop\Page\Product.Model "Model" %>:</strong> $Model.XML</span>
                <br>
            <% end_if %>

            <span>
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
            </span>

        </p>

        <p class="nsw-card__copy">

            <% if $Abstract %>
                $Abstract.XML
            <% end_if %>
        </p>

        <% include nswds/Icon Icon='east', IconExtraClass='nsw-card__icon' %>

    </div>

    <% if $Image %>
    <div class="nsw-card__image-area">
        <img src="{$Image.ScaleWidth(720).URL}" class="nsw-card__image" alt="{$Image.Title.XML}">
    </div>
    <% end_if %>


</div>
