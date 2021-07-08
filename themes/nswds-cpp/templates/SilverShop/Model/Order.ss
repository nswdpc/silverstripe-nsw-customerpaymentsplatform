
<% include SilverShop\Model\Order_Content %>

<% include SilverShop\Model\Order_Address %>

<% if $Total %>

    <% if $Payments %>
        <% include SilverShop\Model\Order_Payments %>
    <% end_if %>

    <h2><%t SilverShop\Model\Order.TotalOutstanding "Total outstanding" %></h2>
    <p>$TotalOutstanding.Nice</p>

<% end_if %>

<% if $Notes %>
    <h2><%t SilverShop\Model\Order.db_Notes "Notes" %></h2>
    <p>
        $Notes.XML
    </p>
<% end_if %>
