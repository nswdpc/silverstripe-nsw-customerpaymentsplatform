
<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <article>

                <div class="nsw-block">
                    <% include PageContentTitle %>
                    <% include PageContentAbstract %>
                </div>

                <% include PageElemental %>

                <% if $Order %>
                    <% with $Order %>
                        <h2><%t SilverShop\Model\Order.OrderHeadline "Order #{OrderNo} {OrderDate}" OrderNo=$Reference OrderDate=$Created.Nice %></h2>
                    <% end_with %>
                <% end_if %>

                <% if $Message %>
                    <% include InPageNotification Icon='info', Level='info', MessageTitle='Empty', Message=$Message %>
                <% end_if %>

                <% if $Order %>

                    <% with $Order %>
                        <% include SilverShop\Model\Order %>
                    <% end_with %>

                    <% include PageForm %>

                <% end_if %>

            </article>

        </main>

    </div>
</div>
