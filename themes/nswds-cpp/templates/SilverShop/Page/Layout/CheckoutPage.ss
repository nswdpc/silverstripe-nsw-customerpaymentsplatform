<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">
    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <article>
                <div class="nsw-block">
                    <% include NSWDPC/Waratah/PageContentTitle %>
                    <% include NSWDPC/Waratah/PageContentAbstract %>
                </div>
                <% include NSWDPC/Waratah/PageContentElemental %>


                <div class="checkout">

                    <% if $PaymentErrorMessage %>
                        <% include nswds/InPageNotification Icon='info', Level='warning', MessageTitle='Payment Error', Message=$PaymentErrorMessage %>
                    <% end_if %>

                    <% if $Content %>
                        $Content
                    <% end_if %>

                    <% if $Cart %>
                        <% with $Cart %>
                            <% include SilverShop/Cart/Cart ShowSubtotals=true %>
                        <% end_with %>

                        <h2>Order</h2>
                        <div class="nsw-form">
                            $OrderForm
                        </div>

                    <% else %>

                        <% include nswds/InPageNotification Icon='info', Level='info', MessageTitle='There are no items in your cart.', Message=$PaymentErrorMessage %>

                    <% end_if %>
                </div>

                <% include NSWDPC/Waratah/PageForm %>

            </article>

        </main>

    </div>

</div>
