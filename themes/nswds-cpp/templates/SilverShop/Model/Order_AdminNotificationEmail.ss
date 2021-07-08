
<% if $Order %>
    <% loop $Order %>
        <tr>
            <td>
                <% include SilverShop\Model\Order %>
            </td>
        </tr>
    <% end_loop %>
<% else %>
    <p>No order(s) provided</p>
<% end_if %>
