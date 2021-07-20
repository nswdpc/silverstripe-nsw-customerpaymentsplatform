
    <% include SilverShop/Cart/SideCart %>

    <% if $GroupsMenu %>
        <% include SilverShop/Includes/ProductMenu %>
    <% else_if $Parent %>
        <% with $Parent %>
            <% include SilverShop/Includes/ProductMenu %>
        <% end_with %>
    <% end_if %>
