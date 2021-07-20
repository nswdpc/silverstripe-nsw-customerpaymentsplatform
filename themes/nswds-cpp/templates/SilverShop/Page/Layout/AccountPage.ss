
<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <article>

                <div class="nsw-block">
                    <% include NSWDPC/Waratah/PageContentTitle %>
                    <% include NSWDPC/Waratah/PageContentAbstract %>
                </div>
                <% include NSWDPC/Waratah/PageContentElemental %>


                <h2><%t SilverShop\Page\AccountPage.PastOrders 'Past Orders' %></h2>

                <% with $Member %>
                    <% if $PastOrders %>
                        <% include SilverShop/Includes/OrderHistory %>
                    <% else %>
                        <% include nswds/InPageNotification Icon='list_alt', Level='warning', MessageTitle='Orders', Message='No past orders found.' %>
                    <% end_if %>
                <% end_with %>

            </article>

        </main>

        <div class="nsw-page-layout__sidebar">

            <% include SilverShop/Includes/SideBar %>

            <% include SilverShop/Includes/AccountNavigation %>

        </div>

    </div>

</div>
