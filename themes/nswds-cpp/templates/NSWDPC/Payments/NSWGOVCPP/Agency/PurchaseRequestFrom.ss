<h2><%t payments.HELLO 'Hello' %></h2>

<% if $Content %>
{$Content}
<% end_if %>

<h3><%t payments.ACCESS_REQUEST_FROM 'Requestor' %></h3>

<% if $FromMember %>
<% with $FromMember %>
    <p><strong><%t payments.REQUEST_FROM_NAME 'Name' %>:</strong> {$Title.XML}</p>
    <p><strong><%t payments.REQUEST_FROM_EMAIL 'Email' %>:</strong> {$Email.XML}</p>
<% end_with %>
<% end_if %>

<p>
<strong><%t payments.REQUEST_ACCESS_REASON 'Reason' %>:</strong>
<% if $RequestAccessReason %>
{$RequestAccessReason.XML}
<% else %>
<%t payments.REQUEST_ACCESS_REASON_NONE 'none given' %>
<% end_if %>
</p>

<% if $ApprovalPage %>
    <h3><%t payments.APPROVAL_HEADING 'Approval' %></h3>
    <p><a href="{$ApprovalPage.AbsoluteLink.XML}"><%t payments.APPROVE_THIS_REQUEST 'Approve this request' %></a></p>
    <p><%t payments.LINK_NOT_WORKING 'If the link is not working, please copy and paste this link:' %><br><code>{$ApprovalPage.AbsoluteLink.XML}</code></p>
<% end_if %>
