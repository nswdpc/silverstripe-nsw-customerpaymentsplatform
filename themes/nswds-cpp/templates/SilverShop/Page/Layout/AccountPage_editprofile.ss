<%-- Base: content page with article --%>

<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <% include NSWDPC/Waratah/PageContentTitle %>
            <% include NSWDPC/Waratah/PageContentAbstract %>
            <% include NSWDPC/Waratah/PageElemental %>


            <h2>
                <%t SilverShop\Page\AccountPage_EditProfile.Title 'Edit Profile' %>
            </h2>

            $EditAccountForm


            <h2>
                Change password
            </h2>

            $ChangePasswordForm

        </main>

        <div class="nsw-page-layout__sidebar">

            <% include SilverShop/Includes/SideBar %>

            <% include SilverShop/Includes/AccountNavigation %>

        </div>

    </div>

</div>
