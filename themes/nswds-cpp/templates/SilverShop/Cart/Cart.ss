<% if $Items %>

<div class="nsw-table-responsive" role="region" aria-labelledby="caption-cart" tabindex="0">

    <table class="nsw-table nsw-table--caption-top">

        <caption id="caption-cart">
            <%t SilverShop/Cart/ShoppingCart.TableSummary "Current contents of your cart." %>
        </caption>

        <colgroup>
            <col class="image"/>
            <col class="product title"/>
            <col class="unitprice" />
            <col class="quantity" />
            <col class="total"/>
            <col class="remove"/>
        </colgroup>

        <thead>
            <tr>
                <th scope="col" colspan="2">
                    <%t SilverShop/Page/Product.SINGULARNAME "Product" %>
                </th>
                <th scope="col"><%t SilverShop/Model/Order.UnitPrice "Unit Price" %></th>
                <th scope="col"><%t SilverShop/Model/Order.Quantity "Quantity" %></th>
                <th scope="col"><%t SilverShop/Model/Order.TotalPriceWithCurrency "Total Price ({Currency})" Currency=$Currency %></th>
                <% if $Editable %>
                    <th scope="col"><%t SilverShop/Generic.Remove "Remove" %></th>
                <% end_if %>
            </tr>
        </thead>
        <tbody>
            <% loop $Items %>
                <% if $ShowInTable %>
                <tr>
                    <td>
                        <% if $Image %>
                            <div class="image">
                                <a href="$Link">
                                    <% include nswds/Media Image=$Image, ImageWidth=100 %>
                                </a>
                            </div>
                        <% end_if %>
                    </td>
                    <td>
                        <p>
                        <% if $Link %>
                            <a href="$Link">$TableTitle</a>
                        <% else %>
                            {$TableTitle.XML}
                        <% end_if %>
                        </p>
                        <% if $SubTitle %><p class="subtitle">$SubTitle</p><% end_if %>
                        <% if $Product.Variations && $Up.Editable %>
                            <%t SilverShop/Generic.Change "Change" %>: $VariationField
                        <% end_if %>
                    </td>
                    <td>
                        {$UnitPrice.Nice}
                    </td>
                    <td>
                        <% if $Up.Editable %>
                            {$QuantityField}
                        <% else %>
                            {$Quantity}
                        <% end_if %>
                    </td>
                    <td>
                        {$Total.Nice}
                    </td>
                    <% if $Up.Editable %>
                        <td>
                            <% if $RemoveField %>
                                {$RemoveField}
                            <% else %>
                                <a href="$removeallLink">
                                    <% include nswds/Icon Icon='remove' %>
                                </a>
                            <% end_if %>
                        </td>
                    <% end_if %>
                </tr>
                <% end_if %>
            <% end_loop %>
        </tbody>
        <tfoot>
            <tr class="subtotal">
                <th colspan="4" scope="row">
                    <%t SilverShop/Model/Order.SubTotal "Sub-total" %>
                </th>
                <td>
                    {$SubTotal.Nice}
                </td>
                <% if $Editable %>
                <td>&nbsp;</td>
                <% end_if %>
            </tr>
            <% if $ShowSubtotals %>
                <% if $Modifiers %>
                    <% loop $Modifiers %>
                        <% if $ShowInTable %>
                            <tr class="{$Classes}">
                                <th colspan="4" scope="row">
                                    <% if $Link %>
                                        <a href="$Link">$TableTitle</a>
                                    <% else %>
                                        $TableTitle
                                    <% end_if %>
                                </th>
                                <td>
                                    {$TableValue.Nice}
                                </td>
                                <% if $Up.Editable %>
                                    <td>
                                        <% if $CanRemove %>
                                            <strong>
                                                <a class="ajaxQuantityLink" href="$removeLink">
                                                    <% include nswds/Icon Icon='remove' %>
                                                </a>
                                            </strong>
                                        <% end_if %>
                                    </td>
                                <% end_if %>
                            </tr>
                            <% if $Form %>
                                <tr>
                                    <td colspan="5">
                                        {$Form}
                                    </td>
                                    <td colspan="10"></td>
                                </tr>
                            <% end_if %>
                        <% end_if %>
                    <% end_loop %>
                <% end_if %>
                <tr>
                    <th colspan="4" scope="row">
                        <%t SilverShop/Model/Order.Total "Total" %>
                    </th>
                    <td>
                        <span class="value">$Total.Nice</span>
                    </td>
                    <% if $Editable %>
                    <td>&nbsp;</td>
                    <% end_if %>
                </tr>
            <% end_if %>
        </tfoot>
    </table>
</div>
<% else %>
    <% include nswds/InPageNotification Icon='shopping_cart', Level='warning', MessageTitle='Empty', Message='There are no items in your cart' %>
<% end_if %>
