<p><%t SilverShop\ShopEmail.StatusChangeTitle 'Shop Status Change' %></p>

<table width="100%" cellpadding="0" cellspacing="0">
    <% with Order %>
        <tr>
            <td valign="top">
                <%t SilverStripe\Control\ChangePasswordEmail_ss.Hello 'Hello' %> <% if $FirstName %>$FirstName<% else %>$Member.FirstName<% end_if %>
            </td>
        </tr>
        <tr>
            <td valign="top">
                <%t SilverShop\ShopEmail.StatusChanged 'Status for order #{OrderNo} changed to "{OrderStatus}"' OrderNo=$Reference OrderStatus=$StatusI18N %>
            </td>
        </tr>
    <% end_with %>
    <tr>
        <td valign="top">
            $Note
        </td>
    </tr>
    <tr>
        <td valign="top">
            <%t SilverShop\ShopEmail.Regards "Kind regards" %>
        </td>
    </tr>

    <tr>
        <td valign="top">
            $SiteConfig.Title<br/>
            $FromEmail<br/>
            <%t SilverShop\ShopEmail.PhoneNumber "PhoneNumber" %>
        </td>
    </tr>
</table>
