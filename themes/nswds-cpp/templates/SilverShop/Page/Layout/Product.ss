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

            <div id="Product" class="typography">

                <div class="productDetails">
                    <% if $Image.ContentImage %>
                        <img class="productImage" src="$Image.ContentImage.URL" alt="<%t SilverShop\Page\Product.ImageAltText "{Title} image" Title=$Title %>" />
                    <% else %>
                        <div class="noimage"><%t SilverShop\Page\Product.NoImage "no image" %></div>
                    <% end_if %>
                    <% if $InternalItemID %>
                        <p class="InternalItemID">
                            <span class="title"><%t SilverShop\Page\Product.Code "Product Code" %>:</span>
                            <span class="value">{$InternalItemID}</span>
                        </p>
                    <% end_if %>
                    <% if $Model %>
                        <p class="Model">
                            <span class="title"><%t SilverShop\Page\Product.Model "Model" %>:</span>
                            <span class="value">$Model.XML</span>
                        </p>
                    <% end_if %>
                    <% if $Size %>
                        <p class="Size">
                            <span class="title"><%t SilverShop\Page\Product.Size "Size" %>:</span>
                            <span class="value">$Size.XML</span>
                        </p>
                    <% end_if %>
                    <% include SilverShop\Includes\Price %>
                    <% if $IsInCart %>
                        <p class="NumItemsInCart">
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