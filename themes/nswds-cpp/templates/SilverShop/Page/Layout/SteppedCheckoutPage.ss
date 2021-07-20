<div class="nsw-container nsw-p-top-sm nsw-p-bottom-lg">

    <div class="nsw-page-layout">

        <main id="main-content" class="nsw-page-layout__main">

            <article>

                <div class="nsw-block">
                    <% include NSWDPC/Waratah/PageContentTitle %>
                    <% include NSWDPC/Waratah/PageContentAbstract %>
                </div>
                <% include NSWDPC/Waratah/PageContentElemental %>


                <% if $PaymentErrorMessage %>
                    <% include nswds/InPageNotification nswds/Icon='error', Level='info', MessageTitle='Payment error', Message=$PaymentErrorMessage %>
                <% end_if %>

                <% if $Cart %>

                    <div id="Checkout">

                        <div class="nsw-accordion js-accordion">

                            <h2 class="nsw-accordion__title">Cart</h2>

                            <div class="nsw-accordion__content">
                                <div class="nsw-wysiwyg-content">
                                <% with $Cart %>
                                <% include SilverShop\Cart\Cart %>
                                <% end_with %>
                                </div>
                            </div>


                            <% if $IsPastStep('contactdetails') %>
                                <h2 class="nsw-accordion__title"><a href="$Link('contactdetails')" class="accordion-toggle" title="edit contact details">Contact</a></h2>
                            <% else %>
                                <h2 class="accordion-toggle">Contact</h2>
                            <% end_if %>
                            <% if not $IsFutureStep('contactdetails') %>
                                <div class="nsw-accordion__content">
                                    <div class="nsw-wysiwyg-content">
                                        <% if $IsCurrentStep('contactdetails') %>
                                            <p><%t SilverShop\Checkout\Step\Address.SupplyContactInformation "Supply your contact information" %></p>
                                            $OrderForm
                                        <% end_if %>
                                        <% if $IsPastStep('contactdetails') %>
                                            <% with $Cart %>
                                                $Name ($Email)
                                            <% end_with %>
                                        <% end_if %>
                                    </div>
                                </div>
                            <% end_if %>

                            <% if $IsPastStep('shippingaddress') %>
                                <h2 class="nsw-accordion__title"><a class="accordion-toggle" title="edit address(es)" href="$Link('shippingaddress')">
                                    <%t SilverShop\Model\Address.SINGULARNAME "Address" %>
                                </a></h2>
                            <% else %>
                                <h2 class="nsw-accordion__title accordion-toggle"><%t SilverShop\Model\Address.SINGULARNAME "Address" %></h2>
                            <% end_if %>

                            <% if not $IsFutureStep('shippingaddress') %>
                                <div class="nsw-accordion__content">
                                    <div class="nsw-wysiwyg-content">
                                        <% if $IsCurrentStep('shippingaddress') %>
                                            <p><%t SilverShop\Checkout\Step\Address.EnterShippingAddress "Please enter your shipping address details." %></p>
                                            $OrderForm
                                        <% end_if %>
                                        <% if $IsPastStep('shippingaddress') %>
                                            <div class="row">
                                                <div class="span4">
                                                    <% with $Cart %>
                                                        <h4><%t SilverShop\Checkout\Step\Address.ShipTo "Ship To:" %></h4>
                                                        $ShippingAddress
                                                    <% end_with %>
                                                </div>
                                                <div class="span4">
                                                <h4><%t SilverShop\Checkout\Step\Address.BillTo "Bill To:" %></h4>
                                                    <% if $IsCurrentStep('billingaddress') %>
                                                        $OrderForm
                                                    <% else %>
                                                        <% with $Cart %>
                                                            $BillingAddress
                                                        <% end_with %>
                                                    <% end_if %>
                                                </div>
                                            </div>
                                        <% end_if %>
                                    </div>
                                </div>
                            <% end_if %>

                            <% if $IsPastStep('shippingmethod') %>
                                <h2 class="nsw-accordion__title"><a class="accordion-toggle" title="choose shipping method" href="$Link('shippingmethod')">
                                    <%t SilverShop\Checkout\Step\CheckoutStep.Shipping "Shipping" %>
                                </a></h2>
                            <% else %>
                                <h2 class="nsw-accordion__title accordion-toggle"><%t SilverShop\Checkout/Step\CheckoutStep.Shipping "Shipping" %></h2>
                            <% end_if %>

                            <% if not $IsFutureStep('shippingmethod') %>
                                <div class="nsw-accordion__content">
                                    <div class="nsw-wysiwyg-content">
                                        <% if $IsCurrentStep('shippingmethod') %>
                                            $OrderForm
                                        <% end_if %>
                                        <% if $IsPastStep('shippingmethod') %>
                                            <% with $Cart %>
                                                <p>$ShippingMethod.Title</p>
                                            <% end_with %>
                                        <% end_if %>
                                    </div>
                                </div>
                            <% end_if %>

                            <% if $IsPastStep('paymentmethod') %>
                                <h2 class="nsw-accordion__title"><a class="accordion-toggle" title="choose payment method" href="$Link('paymentmethod')">
                                    <%t SilverShop\Forms\OrderActionsForm.PaymentMethod "Payment Method" %>
                                </a></h2>
                            <% else %>
                                <h2 class="nsw-accordion__title accordion-toggle"><%t SilverShop\Forms\OrderActionsForm.PaymentMethod "Payment Method" %></h2>
                            <% end_if %>

                            <% if not $IsFutureStep('paymentmethod') %>
                                <div class="nsw-accordion__content">
                                    <div class="nsw-wysiwyg-content">
                                        <% if $IsCurrentStep('paymentmethod') %>
                                            $OrderForm
                                        <% end_if %>
                                        <% if $IsPastStep('paymentmethod') %>
                                            $SelectedPaymentMethod
                                        <% end_if %>
                                    </div>
                                </div>
                            <% end_if %>

                            <h2 class="nsw-accordion__title"><%t SilverShop\Checkout\Step\CheckoutStep.Summary "Summary" %></h2>

                            <% if not $IsFutureStep('summary') %>

                                <div class="nsw-accordion__content">
                                    <div class="nsw-wysiwyg-content">

                                    <% if $IsCurrentStep('summary') %>

                                        <% with $Cart %>
                                        <div class="nsw-table-responsive">
                                            <table class="nsw-table nsw-table--striped">
                                                <tfoot>
                                                    <% loop $Modifiers %>
                                                        <% if $ShowInTable %>
                                                    <tr class="modifierRow $EvenOdd $FirstLast $ClassName">
                                                        <td colspan="3">$TableTitle</td>
                                                        <td>$TableValue.Nice</td>
                                                    </tr>
                                                        <% end_if %>
                                                    <% end_loop %>
                                                    <tr>
                                                        <th colspan="3"><%t SilverShop\Model\Order.GrandTotal "Grand Total" %></th>
                                                        <td>$Total.Nice $Currency</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        <% end_with %>

                                        $OrderForm

                                    <% end_if %>

                                    </div>
                                </div>

                            <% end_if %>

                        </div>

                    </div>

                <% else %>

                    <% include nswds/InPageNotification nswds/Icon='shopping_cart', Level='info', MessageTitle='Empty', Message='There are no items in your cart' %>

                    <% if $ContinueLink %>
                        <a class="nsw-button nsw-button--primary nsw-button--full-width" href="$ContinueLink">
                            <%t SilverShop/Cart/ShoppingCart.ContinueShopping 'Continue Shopping' %>
                        </a>
                    <% end_if %>

                <% end_if %>

            </article>

        </main>

    </div>

</div>
