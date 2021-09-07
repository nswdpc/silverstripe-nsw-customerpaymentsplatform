<%-- Base: content page with article --%>

<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <% include NSWDPC/Waratah/PageContentTitle %>
            <% include NSWDPC/Waratah/PageContentAbstract %>
            <% include NSWDPC/Waratah/PageElemental %>


            <h2><%t SilverShop\Page\AccountPage.PastOrders 'Past Orders' %></h2>

            <% with $Member %>
                <% if $PastOrders %>
                    <% include SilverShop/Includes/OrderHistory %>
                <% else %>
                    <% include nswds/InPageNotification InPageNotification_Icon='list_alt', InPageNotification_Level='warning', InPageNotification_Title='Orders', InPageNotification_Content='No past orders found.' %>
                <% end_if %>
            <% end_with %>

        </main>

        <div class="nsw-page-layout__sidebar">

            <% include SilverShop/Includes/SideBar %>

            <% include SilverShop/Includes/AccountNavigation %>

        </div>

    </div>

</main>
