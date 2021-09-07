<%-- Base: content page with article --%>

<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <% include NSWDPC/Waratah/PageContentTitle %>
            <% include NSWDPC/Waratah/PageContentAbstract %>
            <% include NSWDPC/Waratah/PageElemental %>

            <div class="product">

                <div class="details">

                    <div class="nsw-grid">

                        <div class="nsw-col nsw-col-xs-12 nsw-col-sm-6">

                            <h3>About</h3>

                            <dl>

                                <dt><%t SilverShop\Page\Product.Price "Price" %></dt>
                                <dd>

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

                                </dd>

                            <% if $InternalItemID %>
                                <dt>
                                    <span class="title"><%t SilverShop\Page\Product.Code "Product Code" %>:</span>
                                </dt>
                                <dd>
                                    <span class="value">{$InternalItemID}</span>
                                </dd>
                            <% end_if %>

                            <% if $Model %>
                                <dt>
                                    <span class="title"><%t SilverShop\Page\Product.Model "Model" %>:</span>
                                </dt>
                                <dd>
                                    <span class="value">$Model.XML</span>
                                </dd>
                            <% end_if %>

                            <% if $Size %>
                                <dt>
                                    <span class="title"><%t SilverShop\Page\Product.Size "Size" %>:</span>
                                </dt>
                                <dd>
                                    <span class="value">$Size.XML</span>
                                </dd>
                            <% end_if %>

                            </dl>

                        </div>

                        <div class="nsw-col nsw-col-xs-12 nsw-col-sm-6">

                            <% if $IsInCart %>
                                <h3>Update cart</h3>
                                <p class="product-numcart">
                                    <% if $Item.Quantity == 1 %>
                                        <%t SilverShop\Page\Product.NumItemsInCartSingular "You have this item in your cart" %>
                                    <% else %>
                                        <%t SilverShop\Page\Product.NumItemsInCartPlural "You have {Quantity} items in your cart" Quantity=$Item.Quantity %>
                                    <% end_if %>
                                </p>
                            <% else %>
                                <h3>Add to cart</h3>
                            <% end_if %>

                            <% include NSWDPC/Waratah/PageForm %>

                        </div>

                    </div>
                    <%-- grid --%>


                    <% if $Image %>
                        <% include nswds/Media Media_Image=$Image, Media_Caption=$Image.Title %>
                    <% else %>
                        <div class="noimage">
                        </div>
                    <% end_if %>

                </div>

                <%-- details --%>

            </div>
            <%-- product --%>

        </main>

        <div class="nsw-page-layout__sidebar">
            <% include SilverShop/Includes/SideBar %>
        </div>

    </div>

</div>
