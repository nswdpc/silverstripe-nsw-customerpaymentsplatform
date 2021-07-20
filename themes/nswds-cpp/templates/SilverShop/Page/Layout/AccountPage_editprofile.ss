
<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <article>

                <div class="nsw-block">
                    <% include NSWDPC/Waratah/PageContentTitle %>
                    <% include NSWDPC/Waratah/PageContentAbstract %>
                </div>
                <% include NSWDPC/Waratah/PageContentElemental %>


                <h2>
                    <%t SilverShop/Page/AccountPage_EditProfile.Title 'Edit Profile' %>
                </h2>

                $EditAccountForm


                <h2>
                    Change password
                </h2>

                $ChangePasswordForm

            </article>

        </main>

        <div class="nsw-page-layout__sidebar">

            <% include SilverShop/Includes/SideBar %>

            <% include SilverShop/Includes/AccountNavigation %>

        </div>

    </div>

</div>
