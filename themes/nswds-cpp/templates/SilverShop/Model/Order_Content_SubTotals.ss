<tfoot>
    <tr>
        <td colspan="4" scope="row" class="subtotal">
            <%t SilverShop\Model\Order.SubTotal "Sub-total" %>
        </td>
        <td class="right">
            $SubTotal.Nice
        </td>
    </tr>
    <% loop $Modifiers %>
        <% if $ShowInTable %>
            <tr>
                <td colspan="4" scope="row">
                    $TableTitle
                </td>
                <td class="right">
                    $TableValue.Nice
                </td>
            </tr>
        <% end_if %>
    <% end_loop %>
    <tr>
        <td colspan="4" scope="row" class="total">
            <%t SilverShop\Model\Order.Total "Total" %>
        </td>
        <td class="right">
            $Total.Nice
        </td>
    </tr>
</tfoot>
