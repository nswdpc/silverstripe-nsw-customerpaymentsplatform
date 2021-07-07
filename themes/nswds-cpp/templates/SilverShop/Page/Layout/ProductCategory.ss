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
                    <div class="nsw-grid">
                        <div class="nsw-col nsw-col-md-6 nsw-col-lg-4">
                            <% loop $Products %>
                                <% include SilverShop\Includes\ProductGroupItem %>
                            <% end_loop %>
                        </div>
                        <% include SilverShop\Includes\ProductGroupPagination %>
                    </div>
                <% end_if %>

                <% include PageForm %>

            </article>

        </main>

        <div class="nsw-page-layout__sidebar">
            <% include SilverShop\Includes\SideBar %>
        </div>

    </div>

</div>
