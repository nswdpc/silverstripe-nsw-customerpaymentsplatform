<%-- Base: content page with article --%>

<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <% include NSWDPC/Waratah/PageContentTitle %>
            <% include NSWDPC/Waratah/PageContentAbstract %>
            <% include NSWDPC/Waratah/PageElemental %>


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
                        <% include nswds/Button Button_LinkURL=$ContinueLink, Button_ExtraClass='nsw-button--full-width', Button_Title='Continue Shopping' %>
                    <% end_if %>
                    <% if $Cart %>
                        <% if $CheckoutLink %>
                        <% include nswds/Button Button_LinkURL=$CheckoutLink, Button_ExtraClass='nsw-button--full-width', Button_Title='Proceed to Checkout' %>
                        <% end_if %>
                    <% end_if %>
                </nav>

            <% else %>
                <% include nswds/InPageNotification InPageNotification_Icon='shopping_cart', InPageNotification_Level='info', InPageNotification_Title='Empty', InPageNotification_Content='There are no items in your cart' %>
            <% end_if %>

            <% include NSWDPC/Waratah/PageForm %>

        </main>

    </div>

</div>
