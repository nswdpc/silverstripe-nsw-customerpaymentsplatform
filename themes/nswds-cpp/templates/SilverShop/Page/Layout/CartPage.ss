<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">
    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <article>

                <div class="nsw-block">
                    <% include PageContentTitle %>
                    <% include PageContentAbstract %>
                </div>

                <% include PageElemental %>

                <% if $Cart %>

                    <% if $CartForm %>
                        $CartForm
                    <% else %>
                        <% with $Cart %>
                            <% include SilverShop\Cart\Cart Editable=true %>
                        <% end_with %>
                    <% end_if %>

                <% else %>
                    <% include InPageNotification Icon='shopping_cart', Level='info', MessageTitle='Empty', Message='There are no items in your cart' %>
                <% end_if %>

                <div class="cartfooter">
                    <% if $ContinueLink %>
                        <a class="nsw-button nsw-button--primary nsw-button--full-width" href="$ContinueLink">
                            <%t SilverShop\Cart\ShoppingCart.ContinueShopping 'Continue Shopping' %>
                        </a>
                    <% end_if %>
                    <% if $Cart %>
                        <% if $CheckoutLink %>
                            <a class="nsw-button nsw-button--primary nsw-button--full-width" href="$CheckoutLink">
                                <%t SilverShop\Cart\ShoppingCart.ProceedToCheckout 'Proceed to Checkout' %>
                            </a>
                        <% end_if %>
                    <% end_if %>
                </div>

                <% include PageForm %>

            </article>

        </main>

    </div>

</div>
