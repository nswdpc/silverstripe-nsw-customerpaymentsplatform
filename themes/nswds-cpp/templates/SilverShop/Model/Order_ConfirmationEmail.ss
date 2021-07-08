
<div class="content">
    $PurchaseCompleteMessage
</div>

<% if $Order %>
    <% loop $Order %>
        <tr>
            <td>
                <% include SilverShop\Model\Order %>
            </td>
        </tr>
    <% end_loop %>
<% end_if %>
