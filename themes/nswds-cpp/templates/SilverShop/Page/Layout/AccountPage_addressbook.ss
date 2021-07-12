<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <article>

                <div class="nsw-block">
                    <% include PageContentTitle %>
                    <% include PageContentAbstract %>
                </div>

                <% include PageElemental %>

                <% if $CurrentMember %>

                    <% with CurrentMember %>

                        <% if $AddressBook.Count == 0 %>

                            <h2>
                                <%t SilverShop\Page\AccountPage_AddressBook.No_Addresses_Title 'No Addresses' %>
                            </h2>

                            <% include nswds/InPageNotification nswds/Icon='info', Level='warning', MessageTitle='Address book', Message='No addresses found.' %>

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
                                            <td>Actions</th>
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
                                            </td>
                                            <td>
                                                <% if $Phone %>$Phone<% end_if %>
                                            </td>
                                            <td>

                                                <% if $ID == $Top.CurrentMember.DefaultShippingAddressID %>
                                                    <% include nswds/Icon Icon='thumb_up' %>
                                                <% end_if %>
                                            </td>
                                            <td>

                                                <% if $ID == $Top.CurrentMember.DefaultBillingAddressID %>
                                                    <% include nswds/Icon Icon='thumb_up' %>
                                                <% end_if %>

                                            </td>
                                            <td>


                                                <% if $ID != $Top.CurrentMember.DefaultShippingAddressID %>
                                                    <a href="$Top.Link('setdefaultshipping')/{$ID}" class="nsw-button nsw-button--primary">
                                                        <%t SilverShop\Page\AccountPage_AddressBook.MakeDefaultShipping 'Make Default Shipping' %>
                                                    </a>
                                                <% end_if %>

                                                <% if $ID != $Top.CurrentMember.DefaultBillingAddressID %>
                                                    <a href="$Top.Link('setdefaultbilling')/{$ID}" class="nsw-button nsw-button--primary">
                                                        <%t SilverShop\Page\AccountPage_AddressBook.MakeDefaultBilling 'Make Default Billing' %>
                                                    </a>
                                                <% end_if %>

                                                <a href="$Top.Link('deleteaddress')/{$ID}" class="nsw-button nsw-button--danger">
                                                    Remove
                                                </a>

                                            </td>

                                        </tr>

                                    <% end_loop %>

                                    </tbody>

                                </table>

                            </div>

                        <% end_if %>

                    <% end_with %>

                <% end_if %>

                <h2>
                    <%t SilverShop\Page\AccountPage_AddressBook.CreateNewTitle 'Create New Address' %>
                </h2>

                {$CreateAddressForm}

            </article>

        </main>

        <div class="nsw-page-layout__sidebar">

            <% include SilverShop\Includes\SideBar %>

            <% include SilverShop\Includes\AccountNavigation %>

        </div>

    </div>

</div>
