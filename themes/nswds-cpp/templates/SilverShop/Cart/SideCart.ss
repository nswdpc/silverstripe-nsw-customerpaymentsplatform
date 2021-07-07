<%-- require css("silvershop/core: client/dist/css/sidecart.css") --%>

<div class="nsw-grid nsw-grid--spaced">

    <div class="nsw-col">

        <div class="sidecart">

            <h3><%t SilverShop\Cart\ShoppingCart.Headline "Cart" %></h3>

            <% if $Cart %>
                <% with $Cart %>
                    <p>
                        <% if $Items.Plural %>
                            <%t SilverShop\Cart\ShoppingCart.ItemsInCartPlural 'There are <a href="{link}">{quantity} items</a> in your cart.' link=$Top.CartLink quantity=$Items.Quantity %>
                        <% else %>
                            <%t SilverShop\Cart\ShoppingCart.ItemsInCartSingular 'There is <a href="{link}">1 item</a> in your cart.' link=$Top.CartLink %>
                        <% end_if %>
                    </p>
                    <% if $Items.count > 0 %>
                    <table class="nsw-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                    <% loop $Items %>
                        <tr>
                            <td>
                            <% if $Product.Image %>
                                <div class="image">
                                    <a href="$Product.Link">
                                        $Product.Image.ScaleWidth(45)
                                    </a>
                                </div>

                            <% end_if %>
                                <a href="$Product.Link">
                                    {$TableTitle}
                                    <% if $SubTitle %>
                                    <br>
                                    {$SubTitle}
                                    <% end_if %>
                                </a>
                            </td>
                            <td>
                                <span class="quantity">$Quantity</span>
                                <span class="times">&times;</span>
                                <span class="unitprice">$UnitPrice.Nice</span>
                            </td>
                            <td>
                                <a class="remove" href="$removeallLink">
                                     <% include Icon Icon=remove_shopping_cart %>
                                </a>
                            </td>
                        </tr>
                    <% end_loop %>
                        </tbody>
                    </table>
                    <% end_if %>

                    <div class="checkout">
                        <a class="nsw-button nsw-button--primary nsw-button--full-width" href="$Top.CheckoutLink"><%t SilverShop\Cart\ShoppingCart.Checkout "Checkout" %></a>
                    </div>

                <% end_with %>

            <% else %>
                <% include InPageNotification Icon='shopping_cart', Level='info', MessageTitle='Empty', Message='There are no items in your cart' %>
            <% end_if %>

        </div>

    </div>

</div>
