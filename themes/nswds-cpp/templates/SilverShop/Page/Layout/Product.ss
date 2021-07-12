<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <article>
                <div class="nsw-block">
                <% include PageContentTitle %>
                    <% include PageContentAbstract %>
                </div>
                <% include PageElemental %>
            </article>

            <div class="product">

                <div class="details">

                    <% include SilverShop\Includes\Price %>

                    <% if $Image %>
                        <% include nswds/Media Image=$Image %>
                    <% else %>
                        <div class="noimage">
                        </div>
                    <% end_if %>

                    <ul>
                    <% if $InternalItemID %>
                        <li>
                            <span class="title"><%t SilverShop\Page\Product.Code "Product Code" %>:</span>
                            <span class="value">{$InternalItemID}</span>
                        </li>
                    <% end_if %>

                    <% if $Model %>
                        <li>
                            <span class="title"><%t SilverShop\Page\Product.Model "Model" %>:</span>
                            <span class="value">$Model.XML</span>
                        </li>
                    <% end_if %>

                    <% if $Size %>
                        <li>
                            <span class="title"><%t SilverShop\Page\Product.Size "Size" %>:</span>
                            <span class="value">$Size.XML</span>
                        </li>
                    <% end_if %>

                    </ul>

                    <% if $IsInCart %>
                        <p class="product-numcart">
                            <% if $Item.Quantity == 1 %>
                                <%t SilverShop\Page\Product.NumItemsInCartSingular "You have this item in your cart" %>
                            <% else %>
                                <%t SilverShop\Page\Product.NumItemsInCartPlural "You have {Quantity} items in your cart" Quantity=$Item.Quantity %>
                            <% end_if %>
                        </p>
                    <% end_if %>

                    <% include PageForm %>

                </div>

            </div>

        </main>

        <div class="nsw-page-layout__sidebar">
            <% include SilverShop\Includes\SideBar %>
        </div>

    </div>

</div>
