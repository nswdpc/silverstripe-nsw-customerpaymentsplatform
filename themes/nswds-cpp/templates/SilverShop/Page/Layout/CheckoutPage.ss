<%-- Base: content page with article --%>

<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <% include NSWDPC/Waratah/PageContentTitle %>
            <% include NSWDPC/Waratah/PageContentAbstract %>
            <% include NSWDPC/Waratah/PageElemental %>


            <div class="checkout">

                <% if $PaymentErrorMessage %>
                    <% include nswds/InPageNotification InPageNotification_Icon='info', InPageNotification_Level='warning', InPageNotification_Title='Payment Error', InPageNotification_Content=$PaymentErrorMessage %>
                <% end_if %>

                <% if $Content %>
                    $Content
                <% end_if %>

                <% if $Cart %>

                    <% with $Cart %>
                        <% include SilverShop/Cart/Cart ShowSubtotals=true %>
                    <% end_with %>

                    <% if $CheckoutProgressIndicator %>
                        <div class="nsw-block">
                        {$CheckoutProgressIndicator}
                        </div>
                    <% end_if %>

                    <div class="nsw-form">
                        $OrderForm
                    </div>

                <% else %>

                    <% include nswds/InPageNotification InPageNotification_Icon='info', InPageNotification_Level='info', InPageNotification_Title='There are no items in your cart.', InPageNotification_Content=$PaymentErrorMessage %>

                <% end_if %>
            </div>

            <% include NSWDPC/Waratah/PageForm %>

        </main>

        <div class="nsw-page-layout__sidebar"></div>

    </div>

</div>
