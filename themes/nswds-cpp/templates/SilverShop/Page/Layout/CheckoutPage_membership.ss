<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <article>

                <div class="nsw-block">
                    <% include PageContentTitle %>
                    <% include PageContentAbstract %>
                </div>

                <% include PageElemental %>

                <% include PageForm %>

                <h2><%t SilverStripe\Security\Security.LOGIN 'Log In' %></h2>
                $LoginForm

            </article>

        </main>

    </div>

</div>
