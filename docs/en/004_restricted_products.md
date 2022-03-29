# Restricted products

The module provides a basic method for giving access to and removing access from all restricted or private products, using standard group restrictions.

This can be useful if you have a mix of public and restricted products and need more granular controls over product selection based on user permissions.

Other options for doing this in a more general way:

+ Require sign-in for all products
+ Separate public and restricted products into separate websites

## Prerequisites

+ Members who can sign in, either as customers or approvers
+ A shop module, such as SilverShop, where products are pages
+ The elemental userforms module, if you want to allow people to request access

## Build

On build, 3 groups are auto-created:

1. 'Purchase access requestors' - users who can request access to restricted products
2. 'Have purchase access' - users who have been given access to restricted products
3. 'Purchase access approvers' - approvers who can move people between the two groups above

These groups are maintained in the system with no special permissions.

The following page is created:

1. 'Purchase access approval' - providing a form for approvers to move people between groups. This is automatically restricted to this group on write.

## Setup

+ (Optional) Create a user form page (ensure the user defined form module is installed) and restrict it to the 'Purchase access requestors' group. Manually, or automatically (via another module), add specific users to the 'Purchase access requestors' group. Publish this page.
+ Restrict certain product pages to the 'Have purchase access' group. Publish the product.

## Operation

When an approver receives a request to access, they review this access and make a decision whether to allow access or not.

If no access is to be provded, no action is needed within the system.

To give access the approver signs in and loads the 'Purchase access approval' page. Select the user or users to give or remove access and hit 'Save'.

Upon gaining access the relevant member will be sent an email. The email HTML content can be edited in the 'Purchase access approval' page in the CMS.


> :warning: Note that this method does not currently provide access to specific products. Once a person is given access, they will have access to all products viewable by the "Have purchase access" group
