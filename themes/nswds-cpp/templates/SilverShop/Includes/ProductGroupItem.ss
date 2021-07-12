<div class="nsw-content-block">

    <div class="nsw-content-block__content">

        <h3 class="nsw-content-block__title">
            $Title
        </h3>


            <p class="nsw-content-block__copy">
                <% if $Abstract %>
                    $Abstract.XML
                <% end_if %>
                <a href="$Link">View</a>
            </p>

            <ul class="nsw-content-block__list">

            <% if $Model %>
                <li>
                <span><strong><%t SilverShop\Page\Product.Model "Model" %>:</strong> $Model.XML</span>
                </li>
            <% end_if %>

                <li>

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

                </li>
            </ul>

            <div class="nsw-content-block__link">
               <a href="$Link" class="nsw-content-block__link">
                   <%t SilverShop\Page\Product.View "View Product" %>
               </a>
           </div>

            <% if $canPurchase %>
                <div class="nsw-content-block__link">
                    <% include nswds/Button Link=$addLink, ButtonClass='nsw-button--full-width', Title='Add to Cart' %>
                </div>
            <% end_if %>

    </div>

    <% if $Image %>
    <div class="nsw-content-block__image-area">
        <img src="{$Image.ScaleWidth(720).URL}" class="nsw-content-block__image" alt="{$Image.Title.XML}">
    </div>
    <% end_if %>


</div>
