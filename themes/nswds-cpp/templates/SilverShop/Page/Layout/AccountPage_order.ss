<%-- Base: content page with article --%>

<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <% include NSWDPC/Waratah/PageContentTitle %>
            <% include NSWDPC/Waratah/PageContentAbstract %>
            <% include NSWDPC/Waratah/PageElemental %>


            <h2>Order</h2>

            <% if $Message %>
                <% include nswds/InPageNotification InPageNotification_Icon='info', InPageNotification_Level='info', InPageNotification_Title='Order', InPageNotification_Content=$Message %>
            <% end_if %>

            <% if $Order %>

                <% with $Order %>
                    <% include SilverShop/Model/Order %>
                <% end_with %>

                $ActionsForm
            <% end_if %>

        </main>

        <div class="nsw-page-layout__sidebar">

            <% include SilverShop/Includes/SideBar %>

            <% include SilverShop/Includes/AccountNavigation %>

        </div>

    </div>

</div>
