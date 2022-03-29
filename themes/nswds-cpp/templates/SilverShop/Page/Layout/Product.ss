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

                            <section class="nsw-section nsw-section--off-white nsw-section--half-padding">

                                <div class="nsw-container">

                                    <h3><%t SilverShop\Page\Product.About "About" %></h3>

                                    <ul>

                                        <li><strong><%t SilverShop\Page\Product.Price "Price" %>:</strong>
                                        <span>
                                        <% if $PriceRange %>
                                            <span class="value">$PriceRange.Min.Nice</span>
                                            <% if $PriceRange.HasRange %>
                                                - <span class="value">$PriceRange.Max.Nice</span>
                                            <% end_if %>
                                            <span class="currency">$Price.Currency</span>
                                        <% else_if $Price %>
                                            <span class="value">$Price.Nice</span>
                                            <span class="currency">$Price.Currency</span>
                                        <% end_if %>
                                        </span>
                                        </li>

                                    <% if $InternalItemID %>
                                        <li>
                                            <strong class="title"><%t SilverShop\Page\Product.Code "Product code" %>:</strong>
                                            <span class="value">{$InternalItemID}</span>
                                        </li>
                                    <% end_if %>

                                    <% if $Model %>
                                        <li>
                                            <strong class="title"><%t SilverShop\Page\Product.Model "Model" %>:</strong>
                                            <span class="value">$Model.XML</span>
                                        </li>
                                    <% end_if %>

                                    <% if $Size %>
                                        <li>
                                            <strong class="title"><%t SilverShop\Page\Product.Size "Size" %>:</strong>
                                            <span class="value">$Size.XML</span>
                                        </li>
                                    <% end_if %>

                                    </ul>

                                </div>

                            </section>

                        </div>

                        <div class="nsw-col nsw-col-xs-12 nsw-col-sm-6">

                            <section class="nsw-section nsw-section--half-padding nsw-section--grey-03">

                                <div class="nsw-container">

                                    <% if $IsInCart %>
                                        <h3><%t SilverShop\Page\Product.UpdateCart "Update cart" %></h3>
                                        <p class="product-numcart">
                                            <% if $Item.Quantity == 1 %>
                                                <%t SilverShop\Page\Product.NumItemsInCartSingular "You have this item in your cart" %>
                                            <% else %>
                                                <%t SilverShop\Page\Product.NumItemsInCartPlural "You have {Quantity} items in your cart" Quantity=$Item.Quantity %>
                                            <% end_if %>
                                        </p>
                                    <% else %>
                                        <h3><%t SilverShop\Page\Product.Buy "Buy" %></h3>
                                    <% end_if %>

                                    <% include NSWDPC/Waratah/PageForm %>

                                </div>

                            </section>

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

            </section>

        </main>

        <div class="nsw-page-layout__sidebar">
            <% include SilverShop/Includes/SideBar %>
        </div>

    </div>

</div>
