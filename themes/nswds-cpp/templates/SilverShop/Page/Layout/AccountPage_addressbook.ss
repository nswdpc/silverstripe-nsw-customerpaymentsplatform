<%-- Base: content page with article --%>

<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <% include NSWDPC/Waratah/PageContentTitle %>
            <% include NSWDPC/Waratah/PageContentAbstract %>
            <% include NSWDPC/Waratah/PageElemental %>



            <% if $CurrentMember %>

                <% with CurrentMember %>

                    <% if $AddressBook.Count == 0 %>

                        <h2>
                            <%t SilverShop\Page\AccountPage_AddressBook.No_Addresses_Title 'No Addresses' %>
                        </h2>

                        <% include nswds/InPageNotification InPageNotification_Icon='info', InPageNotification_Level='warning', InPageNotification_Title='Address book', InPageNotification_Content='No addresses found.' %>

                    <% else %>


                        <% if $Top.CurrentAddress %>

                            <h2>
                                <%t SilverShop\Page\AccountPage_AddressBook.EditAddress 'Edit address' %>
                            </h2>

                            <% include nswds/Callout Callout_Icon='warning', Callout_Title='Information', Callout_Level='warning', Callout_Content='Editing this address will change it for all previous orders' %>

                            {$Top.EditAddressForm}

                        <% else %>

                            <h2>
                                <%t SilverShop\Page\AccountPage_AddressBook.Addresses_Title 'Address book' %>
                            </h2>

                            <div class="nsw-table-responsive" role="region" aria-labelledby="saved-addresses">

                                <table class="nsw-table nsw-table--striped nsw-table--caption-top">

                                    <caption id="saved-addresses">
                                        Your saved addresses
                                    </caption>

                                    <thead>
                                        <tr>
                                            <th colspan="2">Address</th>
                                            <td>Default shipping</th>
                                            <td>Default billing</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                    <% loop $AddressBook %>

                                        <tr>

                                            <td>
                                                <% if $Name %>$Name<br><% end_if %>
                                                <% if $Company %>$Company<br><% end_if %>
                                                <% if $Address %>$Address<br><% end_if %>
                                                <% if $AddressLine2 %>$AddressLine2<br><% end_if %>
                                                <% if $City %>$City<br/><% end_if %>
                                                <% if $PostalCode %>$PostalCode<br/><% end_if %>
                                                <% if $State %>$State<br/><% end_if %>
                                                <% if $Country %>$Country<br/><% end_if %>

                                                <a href="{$EditLink}" class="nsw-button nsw-button--secondary">
                                                    Edit
                                                </a>

                                            </td>
                                            <td>
                                                <% if $Phone %>$Phone<% end_if %>
                                            </td>
                                            <td>

                                                <% if $ID == $Up.DefaultShippingAddressID %>
                                                    Yes
                                                <% else %>
                                                    No
                                                <% end_if %>
                                            </td>
                                            <td>

                                                <% if $ID == $Up.DefaultBillingAddressID %>
                                                    Yes
                                                <% else %>
                                                    No
                                                <% end_if %>

                                            </td>

                                        </tr>

                                    <% end_loop %>

                                    </tbody>

                                </table>

                            </div>

                            <%-- allow default address selection --%>
                            <% if $Top.DefaultAddressForm %>

                            <h2>
                                <%t SilverShop\Page\AccountPage_AddressBook.SelectDefaultAddresses 'Select default addresses' %>
                            </h2>

                            {$Top.DefaultAddressForm}

                            <% end_if %>


                        <%-- end not editing  --%>
                        <% end_if %>

                    <% end_if %>
                    <%-- end address book count = 0 --%>


                    <%-- create an address --%>
                    <% if $Top.CreateAddressForm && not $Top.CurrentAddress %>

                    <h2>
                        <%t SilverShop\Page\AccountPage_AddressBook.CreateNewTitle 'Create New Address' %>
                    </h2>

                    {$Top.CreateAddressForm}

                    <% end_if %>

                    <%-- current member --%>
                <% end_with %>

            <% end_if %>
            <%-- end no member --%>


        </main>

        <div class="nsw-page-layout__sidebar">

            <% include SilverShop/Includes/SideBar %>

            <% include SilverShop/Includes/AccountNavigation %>

        </div>

    </div>

</div>
