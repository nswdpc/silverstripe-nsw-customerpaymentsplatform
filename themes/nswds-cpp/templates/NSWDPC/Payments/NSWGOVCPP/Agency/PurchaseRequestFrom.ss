<% if $FromMember %>
<% with $FromMember %>
    <p><strong><%t payments.REQUEST_FROM 'Request from' %>:<strong> {$Title.XML} - {$Email.XML}</p>
<% end_with %>
<% end_if %>\

<% if $RequestAccessReason %>
    <p><strong><%t payments.REQUEST_ACCESS_REASON 'Reason' %>:</strong> {$Reason.XML}</p>
<% end_if %>

<% if $ApprovalPage %>
    <p><a href="{$ApprovalPage.AbsoluteLink}"><%t payments.APPROVE_THIS_REQUEST 'Approve this request' %></a></p>
<% end_if %>
