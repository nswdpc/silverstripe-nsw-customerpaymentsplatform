<div class="tags">
    <span class="nsw-tag">Order #{$Reference}</span>
    <span class="nsw-tag">Created {$Created.Nice}</span>
</div>

<div class="nsw-table-responsive" role="region">

    <table class="nsw-table nsw-table--striped">
        <colgroup class="image"/>
        <colgroup class="product title"/>
        <colgroup class="unitprice" />
        <colgroup class="quantity" />
        <colgroup class="total"/>
        <thead>
            <tr>
                <th scope="col" colspan="2">
                    <%t SilverShop\Page\Product.SINGULARNAME "Product" %>
                </th>
                <th class="center" scope="col"><%t SilverShop\Model\Order.UnitPrice "Unit Price" %></th>
                <th class="center" scope="col"><%t SilverShop\Model\Order.Quantity "Quantity" %></th>
                <th class="right" scope="col"><%t SilverShop\Model\Order.TotalPriceWithCurrency "Total Price ({Currency})" Currency=$Currency %></th>
            </tr>
        </thead>
        <tbody>
            <% loop $Items %>
                <% include SilverShop\Model\Order_Content_ItemLine %>
            <% end_loop %>
        </tbody>
        <% include SilverShop\Model\Order_Content_SubTotals %>
    </table>

</div>
