<tr>
    <td class="image">
        <% if $Image %>
            <a href="$Link">
                <img src="<% with $Image.ScaleWidth(45) %>$Me.AbsoluteURL<% end_with %>" alt="$Buyable.Title"/>
            </a>
        <% end_if %>
    </td>
    <td class="title" scope="row">
        <% if $Link %>
            <a href="$Link">$TableTitle</a>
        <% else %>
            $TableTitle
        <% end_if %>
        <% if $SubTitle %>
            <span class="subtitle">$SubTitle</span>
        <% end_if %>
    </td>
    <td class="unitprice">$UnitPrice.Nice</td>
    <td class="quantity">$Quantity</td>
    <td class="total">$Total.Nice</td>
</tr>
