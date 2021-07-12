<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

    <main id="main-content" class="nsw-page-layout__main">

        <article>

            <div class="nsw-block">
                <% include PageContentTitle %>
                <% include PageContentAbstract %>
            </div>

            <% include PageElemental %>

            <% if $Products %>

                <div class="nsw-grid nsw-grid--spaced">
                    <% loop $Products %>
                    <div class="nsw-col nsw-col-md-6">
                        <% include SilverShop\Includes\ProductGroupItem %>
                    </div>
                    <% end_loop %>
                </div>

                <% include SilverShop\Includes\ProductGroupPagination %>

            <% end_if %>

            <% include PageForm %>

        </article>

    </main>

    <div class="nsw-page-layout__sidebar">
        <% include SilverShop\Includes\SideBar %>
    </div>

    </div>

</div>
