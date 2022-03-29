


<% if $CurrentMember %>

    <h3><%t SilverShop\Page\AccountPage.Title 'My Account' %></h3>

    <div class="nsw-link-list">
        <ul class="nsw-link-list__list">
            <li class="nsw-link-list__item">
                <a href="{$Link}">
                    <span><%t SilverShop\Page\AccountPage.PastOrders 'Past Orders' %></span>
                    <% include nswds/Icon IconExtraClass='nsw-link-list__icon', Icon=list_alt %>
                </a>
            </li>
            <li class="nsw-link-list__item">
                <a href="{$Link('editprofile')}">
                    <span><%t SilverShop\Page\AccountPage.EditProfile 'Edit Profile' %></span>
                    <% include nswds/Icon IconExtraClass='nsw-link-list__icon', Icon=admin_panel_settings %>
                </a>
            </li>
            <li class="nsw-link-list__item">
                <a href="{$Link('addressbook')}">
                    <span><%t SilverShop\Page\AccountPage.AddressBook 'Address Book' %></span>
                    <% include nswds/Icon IconExtraClass='nsw-link-list__icon', Icon=local_shipping %>
                </a>
            </li>
            <li class="nsw-link-list__item">

                <a href="Security/logout">
                    <span><%t SilverShop\Page\AccountPage.LogOut 'Log Out' %></span>
                    <% include nswds/Icon IconExtraClass='nsw-link-list__icon', Icon=logout %>
                </a>
            </li>
        </ul>
    </div>

    <% with $CurrentMember %>

        <section class="nsw-section nsw-section--box nsw-m-top-lg">

            <h3><%t SilverShop\Page\AccountPage.ProfileTitle 'Profile' %></h3>

            <h4><%t SilverShop\Page\AccountPage.MemberName 'Name' %></h4>
            <p>{$Name}</p>

            <h4><%t SilverShop\Page\AccountPage.MemberEmail 'Email' %></h4>
            <p>{$Email}</p>

            <h4><%t SilverShop\Page\AccountPage.MemberSince 'Member Since' %></h4>
            <p>{$Created.Nice}</p>

            <h4><%t SilverShop\Page\AccountPage.NumberOfOrders 'Number of orders' %></h4>
            <p><% if $PastOrders %>{$PastOrders.Count}<% else %>0<% end_if %></p>

        </section>

    <% end_with %>

<% end_if %>
