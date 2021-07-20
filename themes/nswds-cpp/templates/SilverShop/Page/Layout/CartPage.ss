<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">
    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <article>

                <div class="nsw-block">
                    <% include NSWDPC/Waratah/PageContentTitle %>
                    <% include NSWDPC/Waratah/PageContentAbstract %>
                </div>
                <% include NSWDPC/Waratah/PageContentElemental %>


                <% if $Cart %>

                    <% if $CartForm %>
                        $CartForm
                    <% else %>
                        <% with $Cart %>
                            <% include SilverShop\Cart\Cart Editable=true %>
                        <% end_with %>
                    <% end_if %>

                    <nav class="cartfooter">

                        <p>Choose an option below to continue</p>

                        <% if $ContinueLink %>
                            <% include nswds/Button Link=$ContinueLink, ButtonClass='nsw-button--full-width', Title='Continue Shopping' %>
                        <% end_if %>
                        <% if $Cart %>
                            <% if $CheckoutLink %>
                            <% include nswds/Button Link=$CheckoutLink, ButtonClass='nsw-button--full-width', Title='Proceed to Checkout' %>
                            <% end_if %>
                        <% end_if %>
                    </nav>

                <% else %>
                    <% include nswds/InPageNotification Icon='shopping_cart', Level='info', MessageTitle='Empty', Message='There are no items in your cart' %>
                <% end_if %>

                <% include NSWDPC/Waratah/PageForm %>

            </article>

        </main>

    </div>

</div>
