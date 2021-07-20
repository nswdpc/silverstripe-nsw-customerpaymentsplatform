
<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <article>

                 <div class="nsw-block">
                    <% include NSWDPC/Waratah/PageContentTitle %>
                    <% include NSWDPC/Waratah/PageContentAbstract %>
                </div>
                <% include NSWDPC/Waratah/PageContentElemental %>


                <h2>Order</h2>

                <% if $Message %>
                    <% include nswds/InPageNotification Icon='info', Level='info', MessageTitle='Order', Message=$Message %>
                <% end_if %>

                <% if $Order %>

                    <% with $Order %>
                        <% include SilverShop/Model/Order %>
                    <% end_with %>

                    $ActionsForm
                <% end_if %>

            </article>

        </main>

        <div class="nsw-page-layout__sidebar">

            <% include SilverShop/Includes/SideBar %>

            <% include SilverShop/Includes/AccountNavigation %>
        </div>

    </div>

</div>
