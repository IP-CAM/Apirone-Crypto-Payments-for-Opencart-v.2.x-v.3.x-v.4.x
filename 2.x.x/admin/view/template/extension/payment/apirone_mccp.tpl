<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-apirone" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
            </div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) : ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="container-fluid">
        <?php if (isset($success) && $success) : ?>
        <div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
              <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>

        <?php if (isset($error) && $error) : ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" id="form-apirone" class="form-horizontal">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#tab-settings" data-toggle="tab"><i class="fa fa-cog"></i> <?php echo $tab_settings; ?></a></li>
                    <li><a href="#tab-currencies" data-toggle="tab"><i class="fa fa-bitcoin"></i> <?php echo $tab_currencies; ?></a></li>
                    <li><a href="#tab-statuses" data-toggle="tab"><i class="fa fa-check"></i> <?php echo $tab_statuses; ?></a></li>
                    <li><a href="#tab-info" data-toggle="tab"><i class="fa fa-info-circle"></i> <?php echo $tab_info; ?></a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="tab-settings">
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-sort-order"><?php echo $entry_merchantname; ?></label>
                            <div class="col-sm-10">
                            <input type="text" name="apirone_mccp_merchantname" value="<?php echo $apirone_mccp_merchantname; ?>" placeholder="<?php echo $entry_merchantname; ?>" id="input-merchantname" class="form-control" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-testcustomer"><?php echo $entry_testcustomer; ?></label>
                            <div class="col-sm-10">
                                <input type="email" name="apirone_mccp_testcustomer" value="<?php echo $apirone_mccp_testcustomer; ?>" placeholder="<?php echo $entry_testcustomer_placeholder; ?>" id="input-testcustomer" class="form-control" />
                                <span class="contorl-label" style="margin-top: 4px; display: inline-block;"><?php echo $text_test_currency_customer; ?></span>
                            </div>
                        </div>

                        <div class="form-group required">
                            <label class="col-sm-2 control-label" for="input-timeout"><?php echo $entry_timeout; ?></label>
                            <div class="col-sm-10">
                                <input type="number" name="apirone_mccp_timeout" value="<?php echo $apirone_mccp_timeout; ?>" placeholder="<?php echo $entry_timeout; ?>" id="input-timeout" class="form-control" />
                                <?php if (isset($errors['apirone_mccp_timeout']) && $errors['apirone_mccp_timeout']) : ?>
                                <div class=" text-danger"><?php echo $errors['apirone_mccp_timeout']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-processing-fee"><?php echo $entry_processing_fee_policy; ?></label>
                            <div class="col-sm-10">
                                <select name="apirone_mccp_processing_fee" id="input-processing-fee" class="form-control">
                                    <option value="fixed"<?php echo $apirone_mccp_processing_fee == 'fixed' ? ' selected' : ''; ?>>
                                        <?php echo $text_processing_fee_fixed; ?>
                                    </option>
                                    <option value="percentage"<?php echo $apirone_mccp_processing_fee == 'percentage' ? ' selected' : ''; ?>>
                                        <?php echo $text_processing_fee_percentage; ?>
                                    </option>
                                </select>

                            </div>
                        </div>
                        <div class="form-group required">
                            <label class="col-sm-2 control-label" for="input-factor">
                                <span data-toggle="tooltip" data-original-title="<?php echo $text_factor_tooltip; ?>"><?php echo $entry_factor; ?></span>
                            </label>
                            <div class="col-sm-10">
                                <input name="apirone_mccp_factor" type="number" min="0.01" step='0.01' value="<?php echo $apirone_mccp_factor; ?>" id="input-factor" class="form-control" />
                                <?php if (isset($errors['apirone_mccp_factor'])) : ?>
                                <div class=" text-danger"><?php echo $errors['apirone_mccp_factor']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-geo-zone"><?php echo $entry_geo_zone; ?></label>
                            <div class="col-sm-10">
                                <select name="apirone_mccp_geo_zone_id" id="input-geo-zone" class="form-control">
                                    <option value="0"><?php echo $text_all_zones; ?></option>
                                    <?php foreach ($geo_zones as $geo_zone) :?>
                                    <?php if ($geo_zone['geo_zone_id'] == $apirone_mccp_geo_zone_id) : ?>
                                    <option value="<?php echo $geo_zone['geo_zone_id']; ?>" selected="selected"><?php echo $geo_zone['name']; ?></option>
                                    <?php else : ?>
                                    <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
                            <div class="col-sm-10">
                                <select name="apirone_mccp_status" id="input-status" class="form-control">
                                    <?php if ($apirone_mccp_status) : ?>
                                    <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                    <option value="0"><?php echo $text_disabled; ?></option>
                                    <?php else : ?>
                                    <option value="1"><?php echo $text_enabled; ?></option>
                                    <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-sort-order"><?php echo $entry_sort_order; ?></label>
                            <div class="col-sm-10">
                                <input type="text" name="apirone_mccp_sort_order" value="<?php echo $apirone_mccp_sort_order; ?>" placeholder="<?php echo $entry_sort_order; ?>" id="input-sort-order" class="form-control" />
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane" id="tab-currencies">
                        <?php foreach ($currencies as $currency) : ?>
                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="input-merchant">
                                    <span data-toggle="tooltip" data-original-title="<?php echo $currency->currency_tooltip; ?>" syle="padding:10px 0;">
                                        <img src="<?php echo $currency->icon; ?>" width="24"<?php echo $currency->abbr == 'tbtc' ? ' style="filter: grayscale(1)"' : '';  ?>>&nbsp;<?php echo $currency->name; ?>
                                    </span>
                                </label>
                                <div class="col-sm-10">
                                    <input type="text" name="address[<?php echo $currency->abbr; ?>]" value="<?php echo $currency->address; ?>" id="address_<?php echo $currency->abbr; ?>" class="form-control" />
                                    <?php if (property_exists($currency, 'error') && $currency->error) : ?>
                                    <div class=" text-danger"><?php echo $currency_address_incorrect; ?></div>
                                    <?php endif ?>
                                    <?php if ($currency->testnet) : ?>
                                    <label class="control-label" style="color: inherit !important;"><span data-toggle="tooltip" data-original-title="<?php echo $text_test_currency_tooltip; ?>"><?php echo $text_test_currency; ?></span></label>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="tab-pane" id="tab-statuses">
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-invoice-created"><?php echo $entry_invoice_created; ?></label>
                            <div class="col-sm-10">
                                <select name="apirone_mccp_invoice_created_status_id" id="input-invoice-created" class="form-control">
                                    <?php foreach ($order_statuses as $order_status) : ?>
                                    <?php if ($order_status['order_status_id'] == $apirone_mccp_invoice_created_status_id) :?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                                    <?php else : ?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-invoice-paid"><?php echo $entry_invoice_paid; ?></label>
                            <div class="col-sm-10">
                                <select name="apirone_mccp_invoice_paid_status_id" id="input-invoice-paid" class="form-control">
                                    <?php foreach ($order_statuses as $order_status) : ?>
                                    <?php if ($order_status['order_status_id'] == $apirone_mccp_invoice_paid_status_id) :?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                                    <?php else : ?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-invoice-partpaid"><?php echo $entry_invoice_partpaid; ?></label>
                            <div class="col-sm-10">
                                <select name="apirone_mccp_invoice_partpaid_status_id" id="input-invoice-partpaid" class="form-control">
                                    <?php foreach ($order_statuses as $order_status) : ?>
                                    <?php if ($order_status['order_status_id'] == $apirone_mccp_invoice_partpaid_status_id) :?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                                    <?php else : ?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-invoice-overpaid"><?php echo $entry_invoice_overpaid; ?></label>
                            <div class="col-sm-10">
                                <select name="apirone_mccp_invoice_overpaid_status_id" id="input-invoice-overpaid" class="form-control">
                                    <?php foreach ($order_statuses as $order_status) : ?>
                                    <?php if ($order_status['order_status_id'] == $apirone_mccp_invoice_overpaid_status_id) :?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                                    <?php else : ?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-invoice-completed"><?php echo $entry_invoice_completed; ?></label>
                            <div class="col-sm-10">
                                <select name="apirone_mccp_invoice_completed_status_id" id="input-invoice-completed" class="form-control">
                                    <?php foreach ($order_statuses as $order_status) : ?>
                                    <?php if ($order_status['order_status_id'] == $apirone_mccp_invoice_completed_status_id) :?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                                    <?php else : ?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-invoice-expired"><?php echo $entry_invoice_expired; ?></label>
                            <div class="col-sm-10">
                                <select name="apirone_mccp_invoice_expired_status_id" id="input-invoice-expired" class="form-control">
                                    <?php foreach ($order_statuses as $order_status) : ?>
                                    <?php if ($order_status['order_status_id'] == $apirone_mccp_invoice_expired_status_id) :?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                                    <?php else : ?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane" id="tab-info">
                        <div style="padding: 1rem; margin-bottom: 1rem">
                            <h4><?php echo $heading_testnet_hint; ?></h4>
                            <hr>
                            <p><?php echo $text_testnet_hint; ?></p>
                            <a target="_blank" href="https://coinfaucet.eu/en/btc-testnet/?lid=apirone">Coinfaucet</a><br>
                            <a target="_blank" href="https://bitcoinfaucet.uo1.net?lid=apirone">Bitcoinfaucet</a><br>
                            <a target="_blank" href="https://testnet-faucet.com/btc-testnet/?lid=apirone">Testnet faucet</a><br>
                            <a target="_blank" href="https://kuttler.eu/en/bitcoin/btc/faucet/?lid=apirone">Kuttler</a>
                            <hr>
                            <p class="mb-0"><strong>Read more:</strong> <a href="https://apirone.com/faq" target="_blank">https://apirone.com/faq</a></p>
                        </div>
                        <div class="_alert _alert-info" role="alert" style="padding: 1rem; margin-bottom: 1rem">
                            <h4><?php echo $heading_plugin_info; ?></h4>
                            <hr>
                            <p>
                                <strong><?php echo $text_apirone_account; ?>:</strong> <?php echo $apirone_mccp_account; ?><br/>
                                <strong><?php echo $text_plugin_version; ?>:</strong> <?php echo $apirone_mccp_version; ?><br/>
                                <strong><?php echo $text_php_version; ?>:</strong> <?php echo $phpversion; ?><br/>
                                <strong><?php echo $text_oc_version; ?></strong>: <?php echo $oc_version; ?><br/>
                            </p>
                            <hr>
                            <p class="mb-0"><strong>Apirone support:</strong> <a href="mailto:support@apirone.com">support@apirone.com</a></p>
                        </div>
                    </div>
                </div>
            </form>
            </div>
        </div>
    </div>
</div>
<?php echo $footer; ?>