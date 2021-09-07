<%-- Base: content page with article --%>

<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <% include NSWDPC/Waratah/PageContentTitle %>
            <% include NSWDPC/Waratah/PageContentAbstract %>
            <% include NSWDPC/Waratah/PageElemental %>

            <% if $Order %>
                <% with $Order %>
                    <h2><%t SilverShop\Model\Order.OrderHeadline "Order #{OrderNo} {OrderDate}" OrderNo=$Reference OrderDate=$Created.Nice %></h2>
                <% end_with %>
            <% end_if %>

            <% if $Message %>
                <% include nswds/InPageNotification InPageNotification_Icon='info', InPageNotification_Level='info', InPageNotification_Title='Empty', InPageNotification_Content=$Message %>
            <% end_if %>

            <% if $Order %>

                <% with $Order %>
                    <% include SilverShop/Model/Order %>
                <% end_with %>

                <% include NSWDPC/Waratah/PageForm %>

            <% end_if %>

        </main>

        <div class="nsw-page-layout__sidebar"></div>

    </div>

</div>
