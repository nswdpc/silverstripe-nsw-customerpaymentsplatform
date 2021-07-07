
    <% if $GroupsMenu %>
        <% include SilverShop\Includes\ProductMenu %>
    <% else_if $Parent %>
        <% with $Parent %>
            <% include SilverShop\Includes\ProductMenu %>
        <% end_with %>
    <% end_if %>

    <div class="cart">
        <% include SilverShop\Cart\SideCart %>
    </div>
