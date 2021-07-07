


<% if $CurrentMember %>

    <h3><%t SilverShop\Page\AccountPage.Title 'My Account' %></h3>

    <div class="nsw-link-list">
        <ul class="nsw-link-list__list">
            <li class="nsw-link-list__item">
                <a href="{$Link}">
                    <span><%t SilverShop\Page\AccountPage.PastOrders 'Past Orders' %></span>
                    <% include Icon IconExtraClass='nsw-link-list__icon', Icon=list_alt %>
                </a>
            </li>
            <li class="nsw-link-list__item">
                <a href="{$Link('editprofile')}">
                    <span><%t SilverShop\Page\AccountPage.EditProfile 'Edit Profile' %></span>
                    <% include Icon IconExtraClass='nsw-link-list__icon', Icon=admin_panel_settings %>
                </a>
            </li>
            <li class="nsw-link-list__item">
                <a href="{$Link('addressbook')}">
                    <span><%t SilverShop\Page\AccountPage.AddressBook 'Address Book' %></span>
                    <% include Icon IconExtraClass='nsw-link-list__icon', Icon=local_shipping %>
                </a>
            </li>
            <li class="nsw-link-list__item">

                <a href="Security/logout">
                    <span><%t SilverShop\Page\AccountPage.LogOut 'Log Out' %></span>
                    <% include Icon IconExtraClass='nsw-link-list__icon', Icon=logout %>
                </a>
            </li>
        </ul>
    </div>

    <% with $CurrentMember %>

        <h3><%t SilverShop\Page\AccountPage.ProfileTitle 'Profile' %></h3>

        <div role="region" aria-labelledby="account-profile-caption" tabindex="0">

            <table class="nsw-table nsw-table--bordered nsw-table--striped nsw-table--caption-top">

                <caption id="account-profile-caption">
                    Your profile information
                </caption>

                <tbody>

                    <tr>
                        <th><%t SilverShop\Page\AccountPage.MemberName 'Name' %></th>
                        <td>{$Name}</td>
                    </tr>

                    <tr>
                        <th><%t SilverShop\Page\AccountPage.MemberEmail 'Email' %></th>
                        <td>{$Email}</td>
                    </tr>

                    <tr>
                        <th><%t SilverShop\Page\AccountPage.MemberSince 'Member Since' %></th>
                        <td>{$Created.Nice}</td>
                    </tr>

                    <tr>
                        <th><%t SilverShop\Page\AccountPage.NumberOfOrders 'Number of orders' %></th>
                        <td><% if $PastOrders %>{$PastOrders.Count}<% else %>0<% end_if %></td>
                    </tr>

                </tbody>

            </table>

        </div>

    <% end_with %>

<% end_if %>
