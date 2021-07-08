<h3>Product categories</h3>

<div class="nsw-link-list">

    <ul class="nsw-link-list__list">

        <li class="nsw-link-list__item">
            <% with $Level(1) %>
                <a href="$Link">
                <span>
                {$MenuTitle.XML}
                </span>
                <% include Icon IconExtraClass='nsw-link-list__icon', Icon=list_alt %>
                </a>
            <% end_with %>
        </li>

        <% if $GroupsMenu %>

            <% loop $GroupsMenu %>

                <% if $Children %>
                    <li class="nsw-link-list__item">
                        <a href="$Link">
                        <span>
                        {$MenuTitle.XML}
                        </span>
                        <% include Icon IconExtraClass='nsw-link-list__icon', Icon=list_alt %>
                        </a>
                <% else %>
                    <li class="nsw-link-list__item">
                        <a href="$Link">
                        <span>
                        {$MenuTitle.XML}
                        </span>
                        <% include Icon IconExtraClass='nsw-link-list__icon', Icon=list_alt %>
                        </a>
                <% end_if %>

                <% if $Children %>
                    <ul class="nsw-link-list__list">
                        <% loop $Children %>
                            <li class="nsw-link-list__item">
                                <a href="$Link">
                                <span>
                                    {$MenuTitle.XML}
                                </span>
                                <% include Icon IconExtraClass='nsw-link-list__icon', Icon=list_alt %>
                                </a>
                            </li>
                        <% end_loop %>
                    </ul>
                 <% end_if %>

                </li>
            <% end_loop %>
        <% end_if %>
    </ul>

</div>
