<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">
    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <article>
                <div class="nsw-block">
                <% include PageContentTitle %>
                    <% include PageContentAbstract %>
                </div>
                <% include PageElemental %>

                <div id="Checkout">

                    <% if $PaymentErrorMessage %>
                        <% include InPageNotification Icon='info', Level='warning', MessageTitle='Payment Error', Message=$PaymentErrorMessage %>
                    <% end_if %>

                    <% if $Content %>
                        $Content
                    <% end_if %>

                    <% if $Cart %>
                        <% with $Cart %>
                            <% include SilverShop\Cart\Cart ShowSubtotals=true %>
                        <% end_with %>

                        <h2>Order</h2>
                        <div class="nsw-form">
                            $OrderForm
                        </div>

                    <% else %>

                        <% include InPageNotification Icon='info', Level='info', MessageTitle='There are no items in your cart.', Message=$PaymentErrorMessage %>

                    <% end_if %>
                </div>

                <% include PageForm %>

            </article>

        </main>

    </div>

</div>
